import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:geolocator/geolocator.dart';
import 'package:checkup/data/services/central_http_client.dart' as http;
import 'package:latlong2/latlong.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../shared/widgets/app_layout.dart';

const _facilityCacheStorageKey = 'facility_screen_cache_v1';
const _facilityRequestTimeout = Duration(seconds: 35);

/// Warms the default facility cache before the user opens the map screen.
Future<void> prefetchFacilities() async {
  final prefs = await SharedPreferences.getInstance();
  final raw = prefs.getString(_facilityCacheStorageKey);
  if (raw != null) {
    try {
      final cache = Map<String, dynamic>.from(jsonDecode(raw) as Map);
      final entry = cache['none:none:all:'];
      final savedAt = entry is Map ? entry['saved_at'] as int? : null;
      if (savedAt != null &&
          DateTime.now().difference(
                DateTime.fromMillisecondsSinceEpoch(savedAt),
              ) <
              const Duration(minutes: 30)) {
        return;
      }
    } catch (_) {
      // A malformed cache is replaced by a fresh response below.
    }
  }

  try {
    final uri = Uri.parse('${ApiConstants.baseUrl}${ApiConstants.facilities}');
    final res = await http
        .get(uri, headers: {'Accept': 'application/json'})
        .timeout(_facilityRequestTimeout);
    if (res.statusCode != 200) return;
    final items = jsonDecode(res.body)['data'] as List? ?? [];
    Map<String, dynamic> cache = {};
    if (raw != null) {
      try {
        cache = Map<String, dynamic>.from(jsonDecode(raw) as Map);
      } catch (_) {}
    }
    cache['none:none:all:'] = {
      'saved_at': DateTime.now().millisecondsSinceEpoch,
      'type': null,
      'search': '',
      'items': items,
    };
    await prefs.setString(_facilityCacheStorageKey, jsonEncode(cache));
  } catch (_) {
    // Prefetch is best-effort; the screen still has its normal retry flow.
  }
}

class FacilityScreen extends StatefulWidget {
  const FacilityScreen({super.key});

  @override
  State<FacilityScreen> createState() => _FacilityScreenState();
}

class _FacilityScreenState extends State<FacilityScreen> {
  static const _thailandCenter = LatLng(13.7563, 100.5018);
  static const _defaultRadiusMetres = 10000;
  static const _expandedRadiusMetres = 20000;
  static const _tileUrl = String.fromEnvironment(
    'MAP_TILE_URL',
    defaultValue: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
  );
  static const _mapUserAgent = String.fromEnvironment(
    'MAP_USER_AGENT',
    defaultValue: 'com.checkup.mobile',
  );
  static const _cacheStorageKey = _facilityCacheStorageKey;
  static const _cacheTtl = Duration(minutes: 30);
  static const _maxCacheEntries = 12;

  final _mapController = MapController();
  final _tileResetController = StreamController<void>.broadcast();
  final _searchCtrl = TextEditingController();
  List<dynamic> _items = [];
  bool _isLoading = true;
  bool _showMap = true;
  bool _isMapReady = false;
  String? _error;
  String? _selectedType;
  dynamic _selectedFacility;
  Position? _position;
  bool _isLocating = false;
  LatLng? _pendingMapCenter;
  int _loadGeneration = 0;
  Timer? _searchDebounce;
  Timer? _tileErrorDebounce;
  int _tileErrorCount = 0;
  bool _tileLoadFailed = false;

  final _types = const [
    {'value': null, 'label': 'ทั้งหมด', 'icon': Icons.local_hospital},
    {'value': 'H', 'label': 'โรงพยาบาล', 'icon': Icons.local_hospital_outlined},
    {'value': 'C', 'label': 'คลินิก', 'icon': Icons.medical_services_outlined},
    {'value': 'P', 'label': 'ร้านยา', 'icon': Icons.local_pharmacy_outlined},
  ];

  @override
  void initState() {
    super.initState();
    _initialise();
  }

  Future<void> _initialise() async {
    // Paint cached/API data immediately instead of blocking the whole page on
    // the location permission dialog and a potentially slow GPS fix.
    await _restoreLatestDefaultCache();
    if (!mounted) return;
    final initialLoad = _load();
    final located = await _locate(moveMap: true);
    if (!mounted) return;
    if (located) {
      // Do not wait for the generic, location-less request. The generation
      // guard safely ignores it once the nearby request starts.
      await _load();
    } else {
      await initialLoad;
    }
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _tileErrorDebounce?.cancel();
    _tileResetController.close();
    _searchCtrl.dispose();
    _mapController.dispose();
    super.dispose();
  }

  Future<bool> _locate({bool moveMap = false}) async {
    if (_isLocating) return false;
    if (mounted) setState(() => _isLocating = true);
    if (!await Geolocator.isLocationServiceEnabled()) {
      if (mounted) {
        setState(() => _isLocating = false);
        _showLocationMessage(
          'กรุณาเปิดบริการตำแหน่ง เพื่อแสดงจุดที่คุณอยู่บนแผนที่',
          actionLabel: kIsWeb ? null : 'เปิดการตั้งค่า',
          action: kIsWeb ? null : Geolocator.openLocationSettings,
        );
      }
      return false;
    }
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      if (mounted) {
        setState(() => _isLocating = false);
        _showLocationMessage(
          'ยังไม่ได้อนุญาตให้แอปเข้าถึงตำแหน่งของคุณ',
          actionLabel: !kIsWeb && permission == LocationPermission.deniedForever
              ? 'ตั้งค่าสิทธิ์'
              : null,
          action: !kIsWeb && permission == LocationPermission.deniedForever
              ? Geolocator.openAppSettings
              : null,
        );
      }
      return false;
    }

    // A cached device fix lets the map move immediately while a fresher GPS
    // fix is requested in the background.
    Position? position;
    if (!kIsWeb) {
      position = await Geolocator.getLastKnownPosition();
    }
    if (position != null) _applyPosition(position, moveMap: moveMap);
    try {
      final current = await Geolocator.getCurrentPosition(
        locationSettings: kIsWeb
            ? WebSettings(
                accuracy: LocationAccuracy.medium,
                maximumAge: const Duration(minutes: 5),
                timeLimit: const Duration(seconds: 7),
              )
            : const LocationSettings(
                accuracy: LocationAccuracy.high,
                timeLimit: Duration(seconds: 7),
              ),
      );
      position = current;
      _applyPosition(current, moveMap: moveMap);
    } catch (_) {}
    if (!mounted) return position != null;
    setState(() => _isLocating = false);
    if (position == null) {
      _showLocationMessage(
        'ยังระบุตำแหน่งปัจจุบันไม่ได้ โปรดลองในที่โล่งแล้วกดปุ่มตำแหน่งอีกครั้ง',
      );
    }
    return position != null;
  }

  void _applyPosition(Position position, {required bool moveMap}) {
    if (!mounted) return;
    setState(() {
      _position = position;
      _items.sort((a, b) => _distance(a).compareTo(_distance(b)));
    });
    if (moveMap) {
      final center = LatLng(position.latitude, position.longitude);
      if (!_showMap || !_isMapReady) {
        setState(() {
          _showMap = true;
          _isMapReady = false;
          _pendingMapCenter = center;
        });
      } else {
        _mapController.move(center, 14);
      }
    }
  }

  void _showLocationMessage(
    String message, {
    String? actionLabel,
    Future<bool> Function()? action,
  }) {
    final messenger = ScaffoldMessenger.of(context);
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          behavior: SnackBarBehavior.floating,
          content: Text(message),
          action: actionLabel == null || action == null
              ? null
              : SnackBarAction(
                  label: actionLabel,
                  onPressed: () {
                    action();
                  },
                ),
        ),
      );
  }

  void _onSearchChanged(String value) {
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 450), () {
      if (mounted) _load();
    });
  }

  void _clearSearch() {
    _searchDebounce?.cancel();
    _searchCtrl.clear();
    _load();
  }

  String _requestCacheKey() {
    // About 1 kilometre per bucket: GPS jitter does not trigger a new request.
    final lat = _position?.latitude.toStringAsFixed(2) ?? 'none';
    final lng = _position?.longitude.toStringAsFixed(2) ?? 'none';
    final type = _selectedType ?? 'all';
    final search = _searchCtrl.text.trim().toLowerCase();
    return '$lat:$lng:$type:$search';
  }

  Future<Map<String, dynamic>> _readCache() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_cacheStorageKey);
    if (raw == null) return {};
    try {
      return Map<String, dynamic>.from(jsonDecode(raw) as Map);
    } catch (_) {
      return {};
    }
  }

  Future<void> _restoreLatestDefaultCache() async {
    final cache = await _readCache();
    Map<String, dynamic>? latest;
    for (final value in cache.values) {
      if (value is! Map) continue;
      final entry = Map<String, dynamic>.from(value);
      if (entry['type'] != null || (entry['search'] ?? '') != '') continue;
      if (latest == null ||
          (entry['saved_at'] as int? ?? 0) >
              (latest['saved_at'] as int? ?? 0)) {
        latest = entry;
      }
    }
    final items = latest?['items'];
    if (!mounted || items is! List) return;
    setState(() {
      _items = List<dynamic>.from(items);
      _isLoading = false;
    });
  }

  Future<bool> _restoreRequestCache({required bool allowStale}) async {
    final cache = await _readCache();
    final rawEntry = cache[_requestCacheKey()];
    if (rawEntry is! Map) return false;
    final entry = Map<String, dynamic>.from(rawEntry);
    final savedAt = entry['saved_at'] as int?;
    final items = entry['items'];
    if (savedAt == null || items is! List) return false;
    final age = DateTime.now().difference(
      DateTime.fromMillisecondsSinceEpoch(savedAt),
    );
    if (!allowStale && age > _cacheTtl) return false;
    if (!mounted) return true;
    setState(() {
      _items = List<dynamic>.from(items);
      if (_position != null) {
        _items.sort((a, b) => _distance(a).compareTo(_distance(b)));
      }
      _isLoading = false;
      _error = null;
    });
    return age <= _cacheTtl;
  }

  Future<void> _saveRequestCache({
    required String key,
    required String? type,
    required String search,
    required List<dynamic> items,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    final cache = await _readCache();
    cache[key] = {
      'saved_at': DateTime.now().millisecondsSinceEpoch,
      'type': type,
      'search': search,
      'items': items,
    };
    if (cache.length > _maxCacheEntries) {
      final keys = cache.keys.toList()
        ..sort((a, b) {
          final aTime = (cache[a] as Map?)?['saved_at'] as int? ?? 0;
          final bTime = (cache[b] as Map?)?['saved_at'] as int? ?? 0;
          return aTime.compareTo(bTime);
        });
      for (final key in keys.take(cache.length - _maxCacheEntries)) {
        cache.remove(key);
      }
    }
    await prefs.setString(_cacheStorageKey, jsonEncode(cache));
  }

  double _distance(dynamic item) {
    final point = _pointOf(item);
    if (_position == null || point == null) return double.infinity;
    return Geolocator.distanceBetween(
          _position!.latitude,
          _position!.longitude,
          point.latitude,
          point.longitude,
        ) /
        1000;
  }

  LatLng? _pointOf(dynamic item) {
    final lat = double.tryParse('${item['latitude']}');
    final lng = double.tryParse('${item['longitude']}');
    if (lat == null || lng == null) return null;
    return LatLng(lat, lng);
  }

  Future<void> _load({bool forceRefresh = false}) async {
    final generation = ++_loadGeneration;
    if (!forceRefresh) {
      final fresh = await _restoreRequestCache(allowStale: true);
      if (fresh) return;
    }
    if (!mounted) return;
    final cacheKey = _requestCacheKey();
    final requestedType = _selectedType;
    final requestedSearch = _searchCtrl.text.trim();
    final requestedPosition = _position;
    setState(() {
      _isLoading = true;
      _error = null;
      _selectedFacility = null;
    });
    try {
      Future<http.Response> request(int radiusMetres) {
        final uri =
            Uri.parse(
              '${ApiConstants.baseUrl}${ApiConstants.facilities}',
            ).replace(
              queryParameters: {
                if (requestedType != null) 'facility_type': requestedType,
                if (requestedSearch.isNotEmpty) 'search': requestedSearch,
                if (requestedPosition != null) ...{
                  'latitude': '${requestedPosition.latitude}',
                  'longitude': '${requestedPosition.longitude}',
                  'radius': '$radiusMetres',
                },
              },
            );
        return http
            .get(uri, headers: {'Accept': 'application/json'})
            .timeout(_facilityRequestTimeout);
      }

      var responseRadius = _defaultRadiusMetres;
      var res = await request(responseRadius);
      if (res.statusCode != 200) throw Exception('โหลดสถานพยาบาลไม่สำเร็จ');
      var body = jsonDecode(res.body) as Map<String, dynamic>;
      var items = body['data'] as List? ?? [];
      var externalAvailable =
          body['meta']?['external_facilities_available'] != false;
      if (items.isEmpty &&
          requestedPosition != null &&
          requestedSearch.isEmpty &&
          externalAvailable) {
        responseRadius = _expandedRadiusMetres;
        res = await request(responseRadius);
        if (res.statusCode != 200) {
          throw Exception('โหลดสถานพยาบาลไม่สำเร็จ');
        }
        body = jsonDecode(res.body) as Map<String, dynamic>;
        items = body['data'] as List? ?? [];
        externalAvailable =
            body['meta']?['external_facilities_available'] != false;
      }
      if (items.isNotEmpty || externalAvailable) {
        await _saveRequestCache(
          key: cacheKey,
          type: requestedType,
          search: requestedSearch.toLowerCase(),
          items: items,
        );
      }
      if (!mounted || generation != _loadGeneration) return;
      setState(() {
        _items = items;
        _error = externalAvailable || items.isNotEmpty
            ? null
            : 'ยังเชื่อมต่อบริการค้นหาสถานพยาบาลไม่ได้ โปรดลองใหม่';
        if (_position != null) {
          _items.sort((a, b) => _distance(a).compareTo(_distance(b)));
        }
      });
    } catch (e) {
      if (mounted && generation == _loadGeneration && _items.isEmpty) {
        setState(() => _error = e.toString());
      }
    } finally {
      if (mounted && generation == _loadGeneration) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        centerTitle: true,
        title: Text('สถานพยาบาล', style: AppTextStyles.h4),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 8),
            child: IconButton(
              tooltip: _showMap ? 'แสดงแบบรายการ' : 'แสดงบนแผนที่',
              onPressed: _toggleView,
              style: IconButton.styleFrom(
                foregroundColor: Theme.of(context).colorScheme.onSurface,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              icon: AnimatedSwitcher(
                duration: const Duration(milliseconds: 180),
                transitionBuilder: (child, animation) => ScaleTransition(
                  scale: animation,
                  child: child,
                ),
                child: Icon(
                  _showMap ? Icons.list_rounded : Icons.map_outlined,
                  key: ValueKey(_showMap),
                ),
              ),
            ),
          ),
        ],
        bottom: PreferredSize(
          preferredSize: Size.fromHeight(0.5),
          child: Divider(
            height: 0.5,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: Column(
        children: [
          ColoredBox(
            color: Theme.of(context).scaffoldBackgroundColor,
            child: AppContentWidth(
              maxWidth: 760,
              child: Column(
                children: [
                  _buildSearchBar(),
                  _buildQuickFilters(),
                  _buildViewSwitch(),
                  if (_isLoading && _items.isNotEmpty)
                    const LinearProgressIndicator(
                      minHeight: 2,
                      color: AppColors.primary,
                      backgroundColor: AppColors.primaryLight,
                    ),
                ],
              ),
            ),
          ),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildSearchBar() => Padding(
    padding: const EdgeInsets.fromLTRB(16, 14, 16, 10),
    child: TextField(
      controller: _searchCtrl,
      textInputAction: TextInputAction.search,
      decoration: InputDecoration(
        hintText: 'ค้นหาชื่อสถานพยาบาล...',
        prefixIcon: const Padding(
          padding: EdgeInsets.only(left: 4),
          child: Icon(Icons.search_rounded, size: 23),
        ),
        suffixIcon: ValueListenableBuilder<TextEditingValue>(
          valueListenable: _searchCtrl,
          builder: (_, value, __) => value.text.isEmpty
              ? const SizedBox.shrink()
              : IconButton(
                  tooltip: 'ล้างคำค้นหา',
                  onPressed: _clearSearch,
                  icon: const Icon(Icons.close_rounded, size: 20),
                ),
        ),
        filled: true,
        fillColor: Theme.of(context).colorScheme.surfaceContainerLowest,
        contentPadding: const EdgeInsets.symmetric(vertical: 16),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: BorderSide(
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
        ),
      ),
      onChanged: _onSearchChanged,
      onSubmitted: (_) {
        _searchDebounce?.cancel();
        _load();
      },
    ),
  );

  Widget _buildQuickFilters() => SizedBox(
    height: 42,
    child: ListView.separated(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      scrollDirection: Axis.horizontal,
      itemCount: _types.length,
      separatorBuilder: (_, __) => const SizedBox(width: 8),
      itemBuilder: (context, index) {
        final type = _types[index];
        final value = type['value'] as String?;
        final selected = value == _selectedType;
        return ChoiceChip(
          avatar: Icon(
            type['icon'] as IconData,
            size: 17,
            color: selected
                ? AppColors.white
                : Theme.of(context).colorScheme.onSurfaceVariant,
          ),
          label: Text(type['label'] as String),
          selected: selected,
          showCheckmark: false,
          onSelected: (_) {
            if (selected) return;
            setState(() => _selectedType = value);
            _load();
          },
          selectedColor: AppColors.primary,
          labelStyle: AppTextStyles.body3.copyWith(
            color: selected
                ? AppColors.white
                : Theme.of(context).colorScheme.onSurface,
            fontWeight: FontWeight.w600,
          ),
          side: BorderSide(
            color: selected
                ? AppColors.primary
                : Theme.of(context).colorScheme.outlineVariant,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          visualDensity: VisualDensity.compact,
        );
      },
    ),
  );

  Widget _buildLoadingStatus() {
    if (!_isLoading || _items.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(left: 8),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const SizedBox.square(
            dimension: 13,
            child: CircularProgressIndicator(strokeWidth: 2),
          ),
          const SizedBox(width: 6),
          Text(
            'กำลังอัปเดต',
            style: AppTextStyles.body3.copyWith(
              color: Theme.of(context).colorScheme.primary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildViewSwitch() => Padding(
    padding: const EdgeInsets.fromLTRB(16, 10, 16, 12),
    child: Row(
      children: [
        Text(
          'พบ ${_items.length} แห่ง',
          style: AppTextStyles.body3.copyWith(
            color: Theme.of(context).colorScheme.onSurfaceVariant,
            fontWeight: FontWeight.w600,
          ),
        ),
        _buildLoadingStatus(),
      ],
    ),
  );

  void _toggleView() {
    setState(() {
      _showMap = !_showMap;
      _isMapReady = false;
      _selectedFacility = null;
    });
  }

  Widget _buildBody() {
    if (_isLoading && !_showMap && _items.isEmpty) {
      return const AppLoadingView();
    }
    // Facility lookup and device location are independent. Keep the map and
    // the user's location usable even when the facility API is unavailable.
    if (_showMap) return _buildMap();
    if (_error != null) {
      return AppMessageView.error(message: _error!, onAction: _load);
    }
    if (_items.isEmpty) {
      return const AppMessageView.empty(
        title: 'ไม่พบสถานบริการ',
        message: 'ลองเปลี่ยนประเภท ระยะทาง หรือคำค้นหา',
      );
    }
    return _buildList();
  }

  Widget _buildMap() {
    final facilitiesWithLocation = _items
        .where((item) => _pointOf(item) != null)
        .toList();
    final initialCenter = _position == null
        ? (facilitiesWithLocation.isEmpty
              ? _thailandCenter
              : _pointOf(facilitiesWithLocation.first)!)
        : LatLng(_position!.latitude, _position!.longitude);

    return Stack(
      children: [
        FlutterMap(
          mapController: _mapController,
          options: MapOptions(
            initialCenter: initialCenter,
            initialZoom: _position == null ? 11 : 14,
            onMapReady: () {
              _isMapReady = true;
              final pendingCenter = _pendingMapCenter;
              _pendingMapCenter = null;
              if (pendingCenter != null) {
                WidgetsBinding.instance.addPostFrameCallback((_) {
                  if (mounted && _showMap && _isMapReady) {
                    _mapController.move(pendingCenter, 14);
                  }
                });
              }
            },
            onTap: (_, __) => setState(() => _selectedFacility = null),
          ),
          children: [
            TileLayer(
              urlTemplate: _tileUrl,
              userAgentPackageName: _mapUserAgent,
              maxZoom: 19,
              panBuffer: 0,
              keepBuffer: 2,
              tileDisplay: const TileDisplay.instantaneous(),
              reset: _tileResetController.stream,
              evictErrorTileStrategy: EvictErrorTileStrategy.dispose,
              errorTileCallback: (_, __, ___) => _recordTileError(),
            ),
            MarkerLayer(
              markers: [
                if (_position != null)
                  Marker(
                    point: LatLng(_position!.latitude, _position!.longitude),
                    width: 28,
                    height: 28,
                    child: Container(
                      decoration: BoxDecoration(
                        color: AppColors.primary,
                        shape: BoxShape.circle,
                        border: Border.all(color: AppColors.white, width: 4),
                        boxShadow: [
                          BoxShadow(
                            color: Theme.of(context).colorScheme.onSurface,
                            blurRadius: 5,
                          ),
                        ],
                      ),
                    ),
                  ),
                ...facilitiesWithLocation.map((facility) {
                  final selected = identical(_selectedFacility, facility);
                  return Marker(
                    point: _pointOf(facility)!,
                    width: selected ? 52 : 44,
                    height: selected ? 52 : 44,
                    child: GestureDetector(
                      onTap: () => setState(() {
                        _selectedFacility = facility;
                        _mapController.move(_pointOf(facility)!, 15);
                      }),
                      child: _FacilityMarker(
                        type: facility['facility_type'],
                        selected: selected,
                      ),
                    ),
                  );
                }),
              ],
            ),
            const RichAttributionWidget(
              showFlutterMapAttribution: false,
              attributions: [
                TextSourceAttribution('OpenStreetMap contributors'),
              ],
            ),
          ],
        ),
        if (_tileLoadFailed)
          Positioned(
            left: 16,
            right: 16,
            bottom: _selectedFacility == null ? 98 : 220,
            child: Material(
              color: Theme.of(context).colorScheme.surface,
              elevation: 2,
              borderRadius: BorderRadius.circular(12),
              child: ListTile(
                dense: true,
                leading: const Icon(Icons.map_outlined),
                title: const Text('โหลดพื้นแผนที่ไม่สำเร็จ'),
                trailing: TextButton(
                  onPressed: _retryMapTiles,
                  child: const Text('ลองใหม่'),
                ),
              ),
            ),
          ),
        if (facilitiesWithLocation.isEmpty)
          Positioned(
            left: 16,
            right: 16,
            top: 16,
            child: Card(
              child: Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 18,
                  vertical: 14,
                ),
                child: _isLoading
                    ? Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          ),
                          const SizedBox(width: 10),
                          Text(
                            'กำลังโหลดข้อมูลสถานพยาบาล...',
                            style: AppTextStyles.body2,
                          ),
                        ],
                      )
                    : Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            _error == null
                                ? 'ยังไม่พบข้อมูลสถานพยาบาลในบริเวณนี้'
                                : 'โหลดสถานพยาบาลไม่สำเร็จ แต่ตำแหน่งของคุณยังใช้งานได้',
                            style: AppTextStyles.body2,
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 6),
                          TextButton.icon(
                            onPressed: _load,
                            icon: const Icon(Icons.refresh, size: 18),
                            label: Text(
                              'ลองใหม่',
                              style: AppTextStyles.body2Bold.copyWith(
                                color: AppColors.primary,
                              ),
                            ),
                          ),
                        ],
                      ),
              ),
            ),
          ),
        Positioned(
          right: 12,
          bottom: _selectedFacility == null ? 52 : 174,
          child: FloatingActionButton.small(
            heroTag: 'facility-current-location',
            tooltip: 'ไปยังตำแหน่งปัจจุบัน',
            backgroundColor: Theme.of(context).colorScheme.surface,
            foregroundColor: AppColors.primary,
            onPressed: _isLocating ? null : () => _locate(moveMap: true),
            child: _isLocating
                ? const SizedBox.square(
                    dimension: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.my_location),
          ),
        ),
        if (_selectedFacility != null)
          Positioned(
            left: 12,
            right: 12,
            bottom: 20,
            child: _FacilityCard(
              facility: _selectedFacility,
              distanceKm: _distance(_selectedFacility),
              compact: true,
            ),
          ),
      ],
    );
  }

  void _recordTileError() {
    _tileErrorCount++;
    _tileErrorDebounce?.cancel();
    _tileErrorDebounce = Timer(const Duration(milliseconds: 500), () {
      if (mounted && _tileErrorCount >= 3) {
        setState(() => _tileLoadFailed = true);
      }
    });
  }

  void _retryMapTiles() {
    _tileErrorDebounce?.cancel();
    setState(() {
      _tileErrorCount = 0;
      _tileLoadFailed = false;
    });
    _tileResetController.add(null);
  }

  Widget _buildList() => RefreshIndicator(
    color: AppColors.primary,
    backgroundColor: Theme.of(context).colorScheme.surface,
    elevation: 0,
    onRefresh: () => _load(forceRefresh: true),
    child: AppContentWidth(
      maxWidth: 760,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        itemCount: _items.length,
        itemBuilder: (_, i) => _FacilityCard(
          facility: _items[i],
          distanceKm: _distance(_items[i]),
        ),
      ),
    ),
  );
}

class _FacilityMarker extends StatelessWidget {
  final String? type;
  final bool selected;

  const _FacilityMarker({required this.type, required this.selected});

  Color _color(BuildContext context) => switch (type) {
    'H' => AppColors.danger,
    'C' => AppColors.primary,
    'P' => AppColors.success,
    _ => Theme.of(context).colorScheme.onSurfaceVariant,
  };

  IconData get _icon => switch (type) {
    'H' => Icons.local_hospital,
    'C' => Icons.medical_services,
    'P' => Icons.local_pharmacy,
    _ => Icons.location_on,
  };

  @override
  Widget build(BuildContext context) => AnimatedContainer(
    duration: const Duration(milliseconds: 180),
    decoration: BoxDecoration(
      color: _color(context),
      shape: BoxShape.circle,
      border: Border.all(color: AppColors.white, width: selected ? 4 : 3),
      boxShadow: [
        BoxShadow(
          color: Theme.of(
            context,
          ).colorScheme.onSurface.withValues(alpha: 0.28),
          blurRadius: 6,
        ),
      ],
    ),
    child: Icon(_icon, color: AppColors.white, size: selected ? 26 : 22),
  );
}

class _FacilityCard extends StatelessWidget {
  final dynamic facility;
  final double distanceKm;
  final bool compact;

  const _FacilityCard({
    required this.facility,
    required this.distanceKm,
    this.compact = false,
  });

  Future<void> _openMap() async {
    final lat = facility['latitude'];
    final lng = facility['longitude'];
    final query = lat != null && lng != null
        ? '$lat,$lng'
        : Uri.encodeComponent(facility['facility_name'] ?? '');
    await launchUrl(
      Uri.parse('https://www.google.com/maps/search/?api=1&query=$query'),
      mode: LaunchMode.externalApplication,
    );
  }

  IconData _iconFor(String? type) => switch (type) {
    'H' => Icons.local_hospital_outlined,
    'C' => Icons.medical_services_outlined,
    'P' => Icons.local_pharmacy_outlined,
    _ => Icons.business_outlined,
  };

  Color _colorFor(BuildContext context, String? type) => switch (type) {
    'H' => AppColors.danger,
    'C' => AppColors.primary,
    'P' => AppColors.success,
    _ => Theme.of(context).colorScheme.onSurfaceVariant,
  };

  String _labelFor(String? type) => switch (type) {
    'H' => 'โรงพยาบาล',
    'C' => 'คลินิก',
    'P' => 'ร้านยา',
    _ => 'สถานพยาบาล',
  };

  @override
  Widget build(BuildContext context) {
    final address = [
      facility['address'],
      facility['district'],
      facility['province'],
    ].where((value) => value != null && '$value'.trim().isNotEmpty).join(', ');

    return Card(
      margin: EdgeInsets.only(bottom: compact ? 0 : 10),
      elevation: compact ? 6 : null,
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [
                        _colorFor(context, facility['facility_type']),
                        _colorFor(
                          context,
                          facility['facility_type'],
                        ).withValues(alpha: 0.72),
                      ],
                    ),
                    borderRadius: BorderRadius.circular(15),
                    boxShadow: [
                      BoxShadow(
                        color: _colorFor(
                          context,
                          facility['facility_type'],
                        ).withValues(alpha: 0.22),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Icon(
                    _iconFor(facility['facility_type']),
                    color: AppColors.white,
                    size: 24,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        facility['facility_name'] ?? 'สถานพยาบาล',
                        style: AppTextStyles.body1.copyWith(
                          fontWeight: FontWeight.w600,
                        ),
                        maxLines: compact ? 1 : 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 3),
                      Text(
                        _labelFor(facility['facility_type']),
                        style: AppTextStyles.body3.copyWith(
                          color: AppColors.primary,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
                if (distanceKm.isFinite)
                  Text(
                    '${distanceKm.toStringAsFixed(1)} กม.',
                    style: AppTextStyles.body3.copyWith(
                      color: AppColors.primary,
                    ),
                  ),
              ],
            ),
            if (address.isNotEmpty) ...[
              const SizedBox(height: 8),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(
                    Icons.location_on_outlined,
                    size: 15,
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                  const SizedBox(width: 5),
                  Expanded(
                    child: Text(
                      address,
                      style: AppTextStyles.body3,
                      maxLines: compact ? 1 : 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _openMap,
                    icon: const Icon(Icons.directions_outlined, size: 18),
                    label: const Text('นำทาง'),
                  ),
                ),
                if (facility['phone'] != null) ...[
                  const SizedBox(width: 8),
                  IconButton.outlined(
                    tooltip: 'โทร ${facility['phone']}',
                    onPressed: () =>
                        launchUrl(Uri.parse('tel:${facility['phone']}')),
                    icon: const Icon(Icons.phone_outlined, size: 18),
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}
