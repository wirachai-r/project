import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'package:latlong2/latlong.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';

class FacilityScreen extends StatefulWidget {
  const FacilityScreen({super.key});

  @override
  State<FacilityScreen> createState() => _FacilityScreenState();
}

class _FacilityScreenState extends State<FacilityScreen> {
  static const _thailandCenter = LatLng(13.7563, 100.5018);
  static const _cacheStorageKey = 'facility_screen_cache_v1';
  static const _cacheTtl = Duration(minutes: 30);
  static const _maxCacheEntries = 12;

  final _mapController = MapController();
  final _searchCtrl = TextEditingController();
  List<dynamic> _items = [];
  bool _isLoading = true;
  bool _showMap = true;
  bool _isMapReady = false;
  String? _error;
  String? _selectedType;
  dynamic _selectedFacility;
  Position? _position;
  LatLng? _pendingMapCenter;
  int _loadGeneration = 0;

  final _types = const [
    {'value': null, 'label': 'ทั้งหมด', 'icon': Icons.local_hospital},
    {
      'value': 'H',
      'label': 'โรงพยาบาล',
      'icon': Icons.local_hospital_outlined,
    },
    {
      'value': 'C',
      'label': 'คลินิก',
      'icon': Icons.medical_services_outlined,
    },
    {
      'value': 'P',
      'label': 'ร้านยา',
      'icon': Icons.local_pharmacy_outlined,
    },
  ];

  @override
  void initState() {
    super.initState();
    _initialise();
  }

  Future<void> _initialise() async {
    // Show the latest default result immediately while location is being read.
    await _restoreLatestDefaultCache();
    await _locate(moveMap: true);
    if (!mounted) return;
    await _load();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _mapController.dispose();
    super.dispose();
  }

  Future<void> _locate({bool moveMap = false}) async {
    if (!await Geolocator.isLocationServiceEnabled()) return;
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      return;
    }

    final position = await Geolocator.getCurrentPosition();
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
      final uri = Uri.parse(
        '${ApiConstants.baseUrl}${ApiConstants.facilities}',
      ).replace(
        queryParameters: {
          if (requestedType != null) 'facility_type': requestedType,
          if (requestedSearch.isNotEmpty) 'search': requestedSearch,
          if (requestedPosition != null) ...{
            'latitude': '${requestedPosition.latitude}',
            'longitude': '${requestedPosition.longitude}',
            'radius': '10000',
          },
        },
      );
      final res = await http.get(uri, headers: {'Accept': 'application/json'});
      if (res.statusCode != 200) throw Exception('โหลดสถานพยาบาลไม่สำเร็จ');
      final items = jsonDecode(res.body)['data'] as List? ?? [];
      await _saveRequestCache(
        key: cacheKey,
        type: requestedType,
        search: requestedSearch.toLowerCase(),
        items: items,
      );
      if (!mounted || generation != _loadGeneration) return;
      setState(() {
        _items = items;
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
        backgroundColor: AppColors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        centerTitle: true,
        title: Text('สถานพยาบาล', style: AppTextStyles.h4),
        actions: [
          IconButton(
            tooltip: 'ตำแหน่งของฉัน',
            onPressed: () => _locate(moveMap: true),
            icon: const Icon(Icons.my_location),
          ),
        ],
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(0.5),
          child: Divider(height: 0.5, color: AppColors.border),
        ),
      ),
      body: Column(
        children: [
          _buildSearchBar(),
          _buildTypeFilter(),
          _buildViewSwitch(),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildSearchBar() => Padding(
    padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
    child: TextField(
      controller: _searchCtrl,
      decoration: InputDecoration(
        hintText: 'ค้นหาสถานพยาบาล...',
        prefixIcon: const Icon(Icons.search),
        suffixIcon: _searchCtrl.text.isEmpty
            ? null
            : IconButton(
                onPressed: () {
                  _searchCtrl.clear();
                  _load();
                },
                icon: const Icon(Icons.close),
              ),
      ),
      onChanged: (_) => setState(() {}),
      onSubmitted: (_) => _load(),
    ),
  );

  Widget _buildTypeFilter() => SizedBox(
    height: 40,
    child: ListView.builder(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.symmetric(horizontal: 12),
      itemCount: _types.length,
      itemBuilder: (_, i) {
        final type = _types[i];
        final selected = _selectedType == type['value'];
        return Padding(
          padding: const EdgeInsets.only(right: 8),
          child: Material(
            color: selected ? AppColors.primary : AppColors.surface,
            shape: StadiumBorder(
              side: BorderSide(
                color: selected ? AppColors.primary : AppColors.border,
              ),
            ),
            clipBehavior: Clip.antiAlias,
            child: InkWell(
              onTap: () {
                setState(() => _selectedType = type['value'] as String?);
                _load();
              },
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                child: Row(
                  children: [
                    Icon(
                      type['icon'] as IconData,
                      size: 16,
                      color: selected ? Colors.white : AppColors.textSecondary,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      type['label'] as String,
                      style: AppTextStyles.body3.copyWith(
                        color: selected
                            ? Colors.white
                            : AppColors.textSecondary,
                        fontWeight: selected
                            ? FontWeight.w600
                            : FontWeight.w400,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        );
      },
    ),
  );

  Widget _buildViewSwitch() => Padding(
    padding: const EdgeInsets.fromLTRB(16, 6, 16, 6),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Text(
          '${_items.length} แห่ง',
          style: AppTextStyles.body3.copyWith(color: AppColors.textSecondary),
        ),
        const Spacer(),
        SegmentedButton<bool>(
          segments: const [
            ButtonSegment(
              value: true,
              icon: Icon(Icons.map_outlined, size: 17),
              label: Text('แผนที่'),
            ),
            ButtonSegment(
              value: false,
              icon: Icon(Icons.list, size: 17),
              label: Text('รายการ'),
            ),
          ],
          selected: {_showMap},
          showSelectedIcon: false,
          onSelectionChanged: (value) {
            setState(() {
              _showMap = value.first;
              _isMapReady = false;
              _selectedFacility = null;
            });
          },
          style: const ButtonStyle(
            visualDensity: VisualDensity(horizontal: -2, vertical: -2),
            padding: WidgetStatePropertyAll(
              EdgeInsets.symmetric(horizontal: 8),
            ),
            minimumSize: WidgetStatePropertyAll(Size(0, 40)),
            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
          ),
        ),
      ],
    ),
  );

  Widget _buildBody() {
    if (_isLoading && !_showMap && _items.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(_error!, textAlign: TextAlign.center),
            const SizedBox(height: 8),
            OutlinedButton(onPressed: _load, child: const Text('ลองอีกครั้ง')),
          ],
        ),
      );
    }
    // The base map is still useful when the API has no facilities yet. Only
    // the list view should use the empty-state screen.
    if (_showMap) return _buildMap();
    if (_items.isEmpty) {
      return const Center(child: Text('ไม่พบสถานพยาบาล'));
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
              urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
              userAgentPackageName: 'com.example.mobile',
              maxZoom: 19,
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
                        color: Colors.blue,
                        shape: BoxShape.circle,
                        border: Border.all(color: Colors.white, width: 4),
                        boxShadow: const [
                          BoxShadow(color: Colors.black26, blurRadius: 5),
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
              attributions: [TextSourceAttribution('OpenStreetMap contributors')],
            ),
          ],
        ),
        if (facilitiesWithLocation.isEmpty)
          Center(
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
                            'ยังไม่พบข้อมูลสถานพยาบาล',
                            style: AppTextStyles.body2,
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
        if (_isLoading && facilitiesWithLocation.isNotEmpty)
          const Positioned(
            top: 12,
            right: 12,
            child: Card(
              child: Padding(
                padding: EdgeInsets.all(8),
                child: SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
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
            backgroundColor: AppColors.white,
            foregroundColor: AppColors.primary,
            onPressed: () => _locate(moveMap: true),
            child: const Icon(Icons.my_location),
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

  Widget _buildList() => RefreshIndicator(
    color: AppColors.primary,
    backgroundColor: AppColors.white,
    elevation: 0,
    onRefresh: () => _load(forceRefresh: true),
    child: ListView.builder(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      itemCount: _items.length,
      itemBuilder: (_, i) => _FacilityCard(
        facility: _items[i],
        distanceKm: _distance(_items[i]),
      ),
    ),
  );
}

class _FacilityMarker extends StatelessWidget {
  final String? type;
  final bool selected;

  const _FacilityMarker({required this.type, required this.selected});

  Color get _color => switch (type) {
    'H' => const Color(0xFFE53935),
    'C' => const Color(0xFF2F27CE),
    'P' => const Color(0xFF16A34A),
    _ => AppColors.textSecondary,
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
      color: _color,
      shape: BoxShape.circle,
      border: Border.all(color: Colors.white, width: selected ? 4 : 3),
      boxShadow: const [BoxShadow(color: Colors.black38, blurRadius: 6)],
    ),
    child: Icon(_icon, color: Colors.white, size: selected ? 26 : 22),
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
                CircleAvatar(
                  backgroundColor: AppColors.primaryLight,
                  child: Icon(
                    _iconFor(facility['facility_type']),
                    color: AppColors.primary,
                    size: 20,
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
                  const Icon(
                    Icons.location_on_outlined,
                    size: 15,
                    color: AppColors.textSecondary,
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
                    onPressed: () => launchUrl(
                      Uri.parse('tel:${facility['phone']}'),
                    ),
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
