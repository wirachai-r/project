import 'dart:async';

import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_layout.dart';
import '../../../core/utils/fuzzy_search.dart';
import 'first_aid_detail_screen.dart';
import 'dart:convert';
import 'package:mobile/data/services/central_http_client.dart' as http;
import '../../../data/services/first_aid_offline_service.dart';

class FirstAidListScreen extends StatefulWidget {
  const FirstAidListScreen({super.key});

  @override
  State<FirstAidListScreen> createState() => _FirstAidListScreenState();
}

class _FirstAidListScreenState extends State<FirstAidListScreen> {
  List<dynamic> _items = [];
  List<dynamic> _categories = [];
  String? _selectedCategoryId;
  bool _isLoading = true;
  String? _error;
  final _searchCtrl = TextEditingController();
  Timer? _searchDebounce;
  final _offlineService = FirstAidOfflineService();
  bool _isOffline = false;

  @override
  void initState() {
    super.initState();
    _loadCategories();
    _load();
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _searchCtrl.dispose();
    super.dispose();
  }

  void _onSearchChanged(String value) {
    setState(() {});
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 350), _load);
  }

  Future<void> _loadCategories() async {
    final res = await http.get(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.firstAidCategories}'),
      headers: {'Accept': 'application/json'},
    );
    if (!mounted) return;
    if (res.statusCode == 200) {
      setState(() => _categories = jsonDecode(res.body)['data'] ?? []);
    }
  }

  Future<void> _load() async {
    if (!mounted) return;
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final uri = Uri.parse('${ApiConstants.baseUrl}${ApiConstants.firstAids}')
          .replace(
            queryParameters: {
              'first_aid_category_id': ?_selectedCategoryId,
              if (_searchCtrl.text.isNotEmpty) 'search': _searchCtrl.text,
            },
          );
      final res = await http.get(uri, headers: {'Accept': 'application/json'});
      if (!mounted) return;
      if (res.statusCode == 200) {
        setState(() {
          _items = jsonDecode(res.body)['data'] ?? [];
          _isOffline = false;
        });
      } else {
        throw Exception('Unable to load first aid content');
      }
    } catch (e) {
      final cached = await _offlineService.readAll();
      if (!mounted) return;
      if (cached.isEmpty) {
        setState(() => _error = e.toString());
      } else {
        final query = _searchCtrl.text.trim().toLowerCase();
        setState(() {
          _items = cached.where((item) {
            final matchesCategory =
                _selectedCategoryId == null ||
                item['first_aid_category_id']?.toString() ==
                    _selectedCategoryId;
            final searchable =
                '${item['title'] ?? ''} ${item['title_en'] ?? ''}'
                    .toLowerCase();
            return matchesCategory && fuzzyContains(searchable, query);
          }).toList();
          _isOffline = true;
        });
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _showFilters() async {
    var selectedCategoryId = _selectedCategoryId;
    final apply = await showModalBottomSheet<bool>(
      context: context,
      showDragHandle: true,
      useSafeArea: true,
      builder: (sheetContext) => StatefulBuilder(
        builder: (context, setSheetState) => Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('ตัวกรองปฐมพยาบาล', style: AppTextStyles.h4),
              const SizedBox(height: 18),
              Text('หมวดหมู่', style: AppTextStyles.body2Bold),
              const SizedBox(height: 10),
              ConstrainedBox(
                constraints: const BoxConstraints(maxHeight: 260),
                child: SingleChildScrollView(
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      ChoiceChip(
                        label: const Text('ทั้งหมด'),
                        selected: selectedCategoryId == null,
                        onSelected: (_) =>
                            setSheetState(() => selectedCategoryId = null),
                      ),
                      for (final category in _categories)
                        ChoiceChip(
                          label: Text(category['category_name']),
                          selected:
                              selectedCategoryId ==
                              category['first_aid_category_id'],
                          onSelected: (_) => setSheetState(
                            () => selectedCategoryId =
                                category['first_aid_category_id'],
                          ),
                        ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: () => Navigator.pop(sheetContext, true),
                  child: const Text('แสดงผลปฐมพยาบาล'),
                ),
              ),
            ],
          ),
        ),
      ),
    );

    if (apply != true || !mounted) return;
    setState(() => _selectedCategoryId = selectedCategoryId);
    _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Text('ปฐมพยาบาล', style: AppTextStyles.h4),
        centerTitle: true,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(0.5),
          child: Divider(
            height: 0.5,
            thickness: 0.5,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: ResponsiveBuilder(
        builder: (context) => AppContentWidth(
          child: Column(
            children: [
              if (_isOffline)
                Container(
                  width: double.infinity,
                  color: Colors.amber.shade100,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 8,
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.cloud_off_rounded, size: 18),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'กำลังแสดงข้อมูลปฐมพยาบาลที่บันทึกไว้ในเครื่อง',
                        ),
                      ),
                    ],
                  ),
                ),
              Padding(
                padding: EdgeInsets.fromLTRB(
                  Responsive.horizontalPadding,
                  12,
                  Responsive.horizontalPadding,
                  0,
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _searchCtrl,
                        decoration: InputDecoration(
                          hintText: 'ค้นหา...',
                          prefixIcon: const Icon(Icons.search_rounded),
                          suffixIcon: _searchCtrl.text.isEmpty
                              ? null
                              : IconButton(
                                  tooltip: 'ล้างคำค้นหา',
                                  onPressed: () {
                                    _searchDebounce?.cancel();
                                    _searchCtrl.clear();
                                    setState(() {});
                                    _load();
                                  },
                                  icon: const Icon(Icons.close_rounded),
                                ),
                        ),
                        textInputAction: TextInputAction.search,
                        onChanged: _onSearchChanged,
                        onSubmitted: (_) {
                          _searchDebounce?.cancel();
                          _load();
                        },
                      ),
                    ),
                    const SizedBox(width: 10),
                    Badge(
                      isLabelVisible: _selectedCategoryId != null,
                      smallSize: 8,
                      child: IconButton.filled(
                        tooltip: 'ตัวกรองปฐมพยาบาล',
                        onPressed: _showFilters,
                        style: IconButton.styleFrom(
                          minimumSize: const Size(54, 54),
                          backgroundColor: AppColors.primary,
                          foregroundColor: AppColors.white,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                        icon: const Icon(Icons.tune_rounded),
                      ),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: _isLoading
                    ? const AppLoadingView()
                    : _error != null
                    ? AppMessageView.error(message: _error!, onAction: _load)
                    : _items.isEmpty
                    ? const AppMessageView.empty(
                        title: 'ไม่พบข้อมูลปฐมพยาบาล',
                        message: 'ลองเปลี่ยนคำค้นหาหรือตัวกรอง',
                      )
                    : RefreshIndicator(
                        color: AppColors.primary,
                        backgroundColor: Theme.of(context).colorScheme.surface,
                        elevation: 0,
                        onRefresh: _load,
                        child: LayoutBuilder(
                          builder: (context, constraints) {
                            final textScale =
                                MediaQuery.textScalerOf(context).scale(14) / 14;
                            final singleColumn =
                                constraints.maxWidth < 360 || textScale > 1.2;
                            return GridView.builder(
                              physics: const AlwaysScrollableScrollPhysics(),
                              padding: EdgeInsets.fromLTRB(
                                Responsive.horizontalPadding,
                                16,
                                Responsive.horizontalPadding,
                                24,
                              ),
                              gridDelegate:
                                  SliverGridDelegateWithFixedCrossAxisCount(
                                    crossAxisCount: singleColumn ? 1 : 2,
                                    childAspectRatio: singleColumn
                                        ? 1.75
                                        : 0.85,
                                    crossAxisSpacing: 12,
                                    mainAxisSpacing: 12,
                                  ),
                              itemCount: _items.length,
                              itemBuilder: (_, i) =>
                                  _FirstAidCard(item: _items[i]),
                            );
                          },
                        ),
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _FirstAidCard extends StatelessWidget {
  final dynamic item;
  const _FirstAidCard({required this.item});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(20),
      onTap: () => Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) =>
              FirstAidDetailScreen(firstAidId: item['first_aid_id']),
        ),
      ),
      child: Container(
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
          boxShadow: [
            BoxShadow(
              color: Theme.of(
                context,
              ).colorScheme.onSurface.withValues(alpha: 0.05),
              blurRadius: 16,
              offset: const Offset(0, 7),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: ClipRRect(
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(20),
                ),
                child: item['thumbnail'] != null
                    ? Image.network(
                        item['thumbnail'],
                        width: double.infinity,
                        fit: BoxFit.cover,
                        errorBuilder: (_, _, _) => _placeholder(),
                      )
                    : _placeholder(),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 12, 10, 14),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      item['title'],
                      style: AppTextStyles.body2Bold,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  const SizedBox(width: 4),
                  const Icon(
                    Icons.arrow_forward_rounded,
                    size: 19,
                    color: AppColors.primary,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _placeholder() => Container(
    color: AppColors.primaryLight,
    child: const Center(
      child: Icon(
        Icons.medical_services_outlined,
        color: AppColors.primary,
        size: 40,
      ),
    ),
  );
}
