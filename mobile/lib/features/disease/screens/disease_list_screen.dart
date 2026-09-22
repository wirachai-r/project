import 'dart:async';

import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:flutter/rendering.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/fuzzy_search.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_layout.dart';
import '../../../data/repositories/disease_repository.dart';
import '../../../data/models/disease_model.dart';
import '../../../data/models/disease_category_model.dart';
import 'disease_detail_screen.dart';

class DiseaseListScreen extends StatefulWidget {
  const DiseaseListScreen({super.key});

  @override
  State<DiseaseListScreen> createState() => _DiseaseListScreenState();
}

class _DiseaseListScreenState extends State<DiseaseListScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;
  final _searchCtrl = TextEditingController();
  final _alphabetScrollCtrl = ScrollController();
  final _alphabetBarCtrl = ScrollController();

  final Map<String, GlobalKey> _letterKeys = {};
  final Map<String, GlobalKey> _barLetterKeys = {};
  final Set<String> _expandedCategoryIds = {};

  String? _activeLetter;
  String _search = '';
  Timer? _searchDebounce;
  List<DiseaseModel> _searchResults = [];
  bool _isLoading = true;
  String? _error;
  bool _isScrollingToLetter = false;

  List<DiseaseModel> _allDiseases = [];
  List<DiseaseModel> _popularDiseases = [];
  Map<String, List<DiseaseModel>> _groupedByLetter = {};
  Map<DiseaseCategoryModel, List<DiseaseModel>> _groupedByCategory = {};
  List<String> _availableLetters = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadDiseases();
    _searchCtrl.addListener(_onSearchChanged);
    _alphabetScrollCtrl.addListener(_onListScroll);
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchCtrl.dispose();
    _searchDebounce?.cancel();
    _alphabetScrollCtrl.removeListener(_onListScroll);
    _alphabetScrollCtrl.dispose();
    _alphabetBarCtrl.dispose();
    super.dispose();
  }

  void _onSearchChanged() {
    final value = _searchCtrl.text.trim();
    _searchDebounce?.cancel();
    setState(() {
      _search = value;
      if (value.isEmpty) {
        _searchResults = [];
        _isLoading = false;
        _error = null;
      }
    });
    if (value.isEmpty) return;
    _searchDebounce = Timer(
      const Duration(milliseconds: 350),
      () => _runFuzzySearch(value),
    );
  }

  void _runFuzzySearch(String query) {
    if (!mounted || _searchCtrl.text.trim() != query) return;
    final results = _allDiseases.where((disease) {
      final searchable = [
        disease.diseaseName,
        disease.diseaseNameEn ?? '',
      ].join(' ');
      return fuzzyContains(searchable, query);
    }).toList();
    setState(() => _searchResults = results);
  }

  void _onListScroll() {
    if (_isScrollingToLetter) return;
    if (!_alphabetScrollCtrl.hasClients) return;

    final scrollOffset = _alphabetScrollCtrl.offset;
    String? candidate;
    double closestOffset = double.infinity;

    for (final entry in _letterKeys.entries) {
      final ctx = entry.value.currentContext;
      if (ctx == null) continue;
      final ro = ctx.findRenderObject();
      if (ro == null) continue;

      final viewport = RenderAbstractViewport.of(ro);
      final offsetToReveal = viewport.getOffsetToReveal(ro, 0.0).offset;

      if (offsetToReveal <= scrollOffset + 1) {
        final dist = scrollOffset - offsetToReveal;
        if (dist < closestOffset) {
          closestOffset = dist;
          candidate = entry.key;
        }
      }
    }

    if (candidate != null && candidate != _activeLetter) {
      setState(() => _activeLetter = candidate);
      _scrollBarToLetter(candidate);
    }
  }

  void _scrollBarToLetter(String letter) {
    final ctx = _barLetterKeys[letter]?.currentContext;
    if (ctx == null) return;
    if (!_alphabetBarCtrl.hasClients) return;

    final ro = ctx.findRenderObject();
    if (ro == null) return;

    final viewport = RenderAbstractViewport.of(ro);
    final targetOffset = viewport
        .getOffsetToReveal(ro, 0.5)
        .offset
        .clamp(
          _alphabetBarCtrl.position.minScrollExtent,
          _alphabetBarCtrl.position.maxScrollExtent,
        );

    _alphabetBarCtrl.animateTo(
      targetOffset,
      duration: const Duration(milliseconds: 200),
      curve: Curves.easeOut,
    );
  }

  Future<void> _scrollToLetter(String letter) async {
    final ctx = _letterKeys[letter]?.currentContext;
    if (ctx == null) return;

    final ro = ctx.findRenderObject();
    if (ro == null) return;

    final viewport = RenderAbstractViewport.of(ro);
    final targetOffset = viewport
        .getOffsetToReveal(ro, 0.0)
        .offset
        .clamp(
          _alphabetScrollCtrl.position.minScrollExtent,
          _alphabetScrollCtrl.position.maxScrollExtent,
        );

    setState(() {
      _activeLetter = letter;
      _isScrollingToLetter = true;
    });

    await _alphabetScrollCtrl.animateTo(
      targetOffset,
      duration: const Duration(milliseconds: 350),
      curve: Curves.easeInOut,
    );

    if (mounted) setState(() => _isScrollingToLetter = false);
  }

  String _firstConsonant(String text) {
    const consonants = [
      'ก',
      'ข',
      'ค',
      'ง',
      'จ',
      'ฉ',
      'ช',
      'ซ',
      'ญ',
      'ด',
      'ต',
      'ถ',
      'ท',
      'น',
      'บ',
      'ป',
      'ผ',
      'ฝ',
      'พ',
      'ฟ',
      'ภ',
      'ม',
      'ย',
      'ร',
      'ล',
      'ว',
      'ส',
      'ห',
      'อ',
      'ฮ',
    ];
    for (int i = 0; i < text.length; i++) {
      if (consonants.contains(text[i])) return text[i];
    }
    return '#';
  }

  Future<void> _loadDiseases() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final repo = context.read<DiseaseRepository>();
      final results = await Future.wait([
        repo.getCategories(),
        repo.getAllDiseases(),
      ]);
      if (!mounted) return;
      final categories = results[0] as List<DiseaseCategoryModel>;
      final diseases = results[1] as List<DiseaseModel>;

      final sorted = [...diseases]
        ..sort((a, b) => a.diseaseName.compareTo(b.diseaseName));

      final Map<String, List<DiseaseModel>> letterMap = {};
      for (final d in sorted) {
        final letter = _firstConsonant(d.diseaseName);
        letterMap.putIfAbsent(letter, () => []).add(d);
      }

      final categoryItems = <String, List<DiseaseModel>>{
        for (final category in categories) category.diseaseCategoryId: [],
      };
      for (final disease in sorted) {
        final categoryId = disease.category?.diseaseCategoryId;
        if (categoryId != null) {
          categoryItems[categoryId]?.add(disease);
        }
      }

      final Map<DiseaseCategoryModel, List<DiseaseModel>> categoryMap = {};
      for (final category in categories) {
        final items = categoryItems[category.diseaseCategoryId]!;
        if (items.isNotEmpty) categoryMap[category] = items;
      }

      for (final letter in letterMap.keys) {
        _letterKeys.putIfAbsent(letter, () => GlobalKey());
        _barLetterKeys.putIfAbsent(letter, () => GlobalKey());
      }

      setState(() {
        _allDiseases = diseases;
        _popularDiseases = diseases.where((d) => d.isPopular).toList();
        _groupedByLetter = letterMap;
        _groupedByCategory = categoryMap;
        _availableLetters = letterMap.keys.toList();
        if (_availableLetters.isNotEmpty) {
          _activeLetter = _availableLetters.first;
        }
      });
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  List<DiseaseModel> get _filtered {
    return _search.isEmpty ? [] : _searchResults;
  }

  @override
  Widget build(BuildContext context) {
    final hp = Responsive.horizontalPadding;

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Text('ข้อมูลโรค', style: AppTextStyles.h4),
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
      body: AppContentWidth(
        child: Column(
          children: [
            Padding(
              padding: EdgeInsets.fromLTRB(hp, 12, hp, 0),
              child: Column(
                children: [
                  TextField(
                    controller: _searchCtrl,
                    decoration: InputDecoration(
                      hintText: 'ค้นหาข้อมูลโรค',
                      prefixIcon: Icon(
                        Icons.search,
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                      suffixIcon: _search.isEmpty
                          ? null
                          : IconButton(
                              tooltip: 'ล้างคำค้นหา',
                              onPressed: _searchCtrl.clear,
                              icon: const Icon(Icons.close_rounded),
                            ),
                      filled: true,
                      fillColor: Theme.of(context).colorScheme.surface,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: BorderSide(
                          color: Theme.of(context).colorScheme.outlineVariant,
                        ),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: BorderSide(
                          color: Theme.of(context).colorScheme.outlineVariant,
                        ),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: const BorderSide(
                          color: AppColors.primary,
                          width: 1.5,
                        ),
                      ),
                    ),
                    textInputAction: TextInputAction.search,
                  ),
                  if (_search.isEmpty) ...[
                    SizedBox(height: Responsive.dp(12)),
                    TabBar(
                      controller: _tabController,
                      labelStyle: AppTextStyles.body2Bold,
                      unselectedLabelStyle: AppTextStyles.body2,
                      labelColor: AppColors.primary,
                      unselectedLabelColor: Theme.of(
                        context,
                      ).colorScheme.onSurfaceVariant,
                      indicatorColor: AppColors.primary,
                      indicatorSize: TabBarIndicatorSize.tab,
                      tabs: const [
                        Tab(text: 'ก–ฮ'),
                        Tab(text: 'ตามประเภท'),
                      ],
                    ),
                    const SizedBox(height: 8),
                  ],
                ],
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                color: AppColors.primary,
                backgroundColor: Theme.of(context).colorScheme.surface,
                elevation: 0,
                onRefresh: _loadDiseases,
                child: _buildBody(hp),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBody(double hp) {
    if (_isLoading) {
      return const AppLoadingView();
    }
    if (_error != null) {
      return AppMessageView.error(message: _error!, onAction: _loadDiseases);
    }
    if (_search.isNotEmpty) {
      return _buildSearchResult(hp);
    }
    if (_allDiseases.isEmpty) {
      return const AppMessageView.empty(
        title: 'ไม่พบข้อมูลโรค',
        message: 'ยังไม่มีข้อมูลที่พร้อมแสดงในขณะนี้',
      );
    }
    return TabBarView(
      controller: _tabController,
      children: [_buildByAlphabet(hp), _buildByCategory(hp)],
    );
  }

  Widget _buildByCategory(double hp) {
    final categories = _groupedByCategory.keys.toList();
    return ListView.builder(
      key: const PageStorageKey('disease_by_category_list'),
      physics: const AlwaysScrollableScrollPhysics(),
      padding: EdgeInsets.symmetric(horizontal: hp, vertical: 8),
      itemCount: categories.length,
      itemBuilder: (context, index) {
        final category = categories[index];
        final items = _groupedByCategory[category]!;
        final expanded = _expandedCategoryIds.contains(
          category.diseaseCategoryId,
        );
        return Padding(
          padding: EdgeInsets.only(bottom: Responsive.dp(8)),
          child: Theme(
              data: Theme.of(
                context,
              ).copyWith(dividerColor: Colors.transparent),
              child: ExpansionTile(
                key: PageStorageKey(category.diseaseCategoryId),
                onExpansionChanged: (value) => setState(() {
                  value
                      ? _expandedCategoryIds.add(category.diseaseCategoryId)
                      : _expandedCategoryIds.remove(category.diseaseCategoryId);
                }),
                tilePadding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 4,
                ),
                childrenPadding: const EdgeInsets.fromLTRB(12, 0, 12, 8),
                collapsedBackgroundColor: Theme.of(context).colorScheme.surface,
                backgroundColor: Theme.of(context).colorScheme.surface,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                  side: BorderSide(
                    color: AppColors.primary.withValues(alpha: 0.35),
                  ),
                ),
                collapsedShape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                  side: BorderSide(
                    color: Theme.of(context).colorScheme.outlineVariant,
                  ),
                ),
                textColor: AppColors.primary,
                collapsedTextColor: Theme.of(context).colorScheme.onSurface,
                iconColor: AppColors.primary,
                collapsedIconColor: Theme.of(
                  context,
                ).colorScheme.onSurfaceVariant,
                title: Text(
                  category.categoryName,
                  style: AppTextStyles.body1Bold.copyWith(
                    color: expanded
                        ? AppColors.primary
                        : Theme.of(context).colorScheme.onSurface,
                  ),
                ),
                subtitle: Text(
                  '${items.length} รายการ',
                  style: AppTextStyles.body3.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                ),
                children: items.map((d) => _DiseaseRow(disease: d)).toList(),
              ),
            ),
        );
      },
    );
  }

  Widget _buildSearchResult(double hp) {
    if (_filtered.isEmpty) {
      return const AppMessageView.empty(
        title: 'ไม่พบโรคที่ค้นหา',
        message: 'ลองตรวจคำสะกดหรือใช้คำค้นหาที่สั้นลง',
      );
    }
    return ListView.separated(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: EdgeInsets.symmetric(horizontal: hp),
      itemCount: _filtered.length,
      separatorBuilder: (_, _) => Divider(
        height: 1,
        color: Theme.of(context).colorScheme.outlineVariant,
      ),
      itemBuilder: (_, i) => _DiseaseRow(disease: _filtered[i]),
    );
  }

  Widget _buildByAlphabet(double hp) {
    if (_availableLetters.isEmpty) return const SizedBox.shrink();

    final showPopular = _popularDiseases.isNotEmpty;

    return Row(
      children: [
        Expanded(
          child: ListView.builder(
            controller: _alphabetScrollCtrl,
            physics: const AlwaysScrollableScrollPhysics(),
            padding: EdgeInsets.only(left: hp, right: 10, bottom: 16),
            itemCount: (showPopular ? 1 : 0) + _availableLetters.length,
            itemBuilder: (context, index) {
              if (showPopular && index == 0) {
                return _buildSection(
                  label: 'โรคที่พบบ่อย',
                  key: null,
                  items: _popularDiseases,
                );
              }

              final letterIndex = showPopular ? index - 1 : index;
              final letter = _availableLetters[letterIndex];
              final items = _groupedByLetter[letter]!;

              return _buildSection(
                label: letter,
                key: _letterKeys[letter],
                items: items,
              );
            },
          ),
        ),
        Container(
          width: 34,
          margin: EdgeInsets.only(right: hp / 2, bottom: 12),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(17),
            border: Border.all(
              color: Theme.of(context).colorScheme.outlineVariant,
            ),
          ),
          child: ListView.builder(
            controller: _alphabetBarCtrl,
            padding: const EdgeInsets.symmetric(vertical: 6),
            itemCount: _availableLetters.length,
            itemBuilder: (context, index) {
              final letter = _availableLetters[index];
              final isActive = _activeLetter == letter;
              return GestureDetector(
                key: _barLetterKeys[letter],
                onTap: () => _scrollToLetter(letter),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 150),
                  width: 28,
                  height: 28,
                  margin: const EdgeInsets.symmetric(
                    horizontal: 2,
                    vertical: 1,
                  ),
                  decoration: isActive
                      ? const BoxDecoration(
                          color: AppColors.primary,
                          shape: BoxShape.circle,
                        )
                      : null,
                  alignment: Alignment.center,
                  child: Text(
                    letter,
                    style: AppTextStyles.body3Bold.copyWith(
                      color: isActive ? AppColors.white : AppColors.primary,
                      fontSize: 12,
                    ),
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildSection({
    required String label,
    required GlobalKey? key,
    required List<DiseaseModel> items,
  }) {
    return Column(
      key: key,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          margin: const EdgeInsets.only(top: 8, bottom: 4),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
          decoration: BoxDecoration(
            color: AppColors.primary.withValues(alpha: 0.10),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Text(
            label,
            style: AppTextStyles.body2Bold.copyWith(
              color: AppColors.primary,
            ),
          ),
        ),
        ...items.map(
          (d) => Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _DiseaseRow(disease: d),
              Divider(
                height: 1,
                color: Theme.of(context).colorScheme.outlineVariant,
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _DiseaseRow extends StatelessWidget {
  final DiseaseModel disease;
  const _DiseaseRow({required this.disease});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () => Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => DiseaseDetailScreen(diseaseId: disease.diseaseId),
        ),
      ),
      borderRadius: BorderRadius.circular(12),
      child: SizedBox(
        width: double.infinity,
        child: Padding(
          padding: EdgeInsets.symmetric(
            vertical: Responsive.dp(14),
            horizontal: Responsive.dp(10),
          ),
          child: Row(
            children: [
              Expanded(
                child: Text(
                  disease.diseaseName,
                  style: AppTextStyles.body1,
                ),
              ),
              Icon(
                Icons.chevron_right_rounded,
                size: 20,
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
