import 'dart:async';

import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:flutter/rendering.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/fuzzy_search.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/symptom_icon.dart';
import '../../../data/repositories/symptom_repository.dart';
import '../../../data/models/symptom_model.dart';
import 'assessment_screen.dart';

class SymptomSelectScreen extends StatefulWidget {
  const SymptomSelectScreen({super.key});

  @override
  State<SymptomSelectScreen> createState() => _SymptomSelectScreenState();
}

class _SymptomSelectScreenState extends State<SymptomSelectScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final _searchCtrl = TextEditingController();
  final _alphabetScrollCtrl = ScrollController();
  final _alphabetBarCtrl = ScrollController();

  final Map<String, GlobalKey> _letterKeys = {};
  final Map<String, GlobalKey> _barLetterKeys = {};
  final Set<String> _expandedCategoryIds = {};

  String? _selectedId;
  String? _activeLetter;
  String _search = '';
  Timer? _searchDebounce;
  List<SymptomModel> _searchResults = [];
  bool _isLoading = true;
  bool _isScrollingToLetter = false;

  // 🔹 ตัวแปรเก็บข้อมูลที่ "ประมวลผลเสร็จแล้ว" เพื่อเอาไปใช้วาดหน้าจอได้ทันที
  List<SymptomModel> _allSymptoms = [];
  Map<String, List<SymptomModel>> _groupedByLetter = {};
  List<String> _availableLetters = [];
  Map<SymptomCategoryModel, List<SymptomModel>> _groupedByCategory = {};

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadSymptoms();
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
    final results = _allSymptoms.where((symptom) {
      final searchable = [
        symptom.symptomName,
        symptom.symptomNameEn ?? '',
        symptom.description ?? '',
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
      _scrollBarToLetter(candidate!);
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

  Future<void> _loadSymptoms() async {
    setState(() => _isLoading = true);
    try {
      final repo = context.read<SymptomRepository>();
      final results = await Future.wait([
        repo.getCategories(),
        repo.getSymptoms(status: '1'),
      ]);

      if (!mounted)
        return; // ✅ เช็คทันทีหลัง await ก่อนแตะ context/setState ต่อ

      final categories = results[0] as List<SymptomCategoryModel>;
      final symptoms = results[1] as List<SymptomModel>;

      // ⚡ 1. จัดการข้อมูลแบบ ก-ฮ ไว้ล่วงหน้า (ทำครั้งเดียว)
      final sortedSymptoms = [...symptoms]
        ..sort((a, b) => a.symptomName.compareTo(b.symptomName));
      final Map<String, List<SymptomModel>> letterMap = {};
      for (final s in sortedSymptoms) {
        final letter = _firstConsonant(s.symptomName);
        letterMap.putIfAbsent(letter, () => []).add(s);
      }

      final categoryItems = <String, List<SymptomModel>>{
        for (final category in categories) category.symptomCategoryId: [],
      };
      for (final symptom in sortedSymptoms) {
        categoryItems[symptom.symptomCategoryId]?.add(symptom);
      }

      final Map<SymptomCategoryModel, List<SymptomModel>> catMap = {};
      for (final cat in categories) {
        final items = categoryItems[cat.symptomCategoryId]!;
        if (items.isNotEmpty) {
          catMap[cat] = items;
        }
      }

      for (final letter in letterMap.keys) {
        _letterKeys.putIfAbsent(letter, () => GlobalKey());
        _barLetterKeys.putIfAbsent(letter, () => GlobalKey());
      }

      if (!mounted)
        return; // ✅ เช็คอีกครั้งก่อน setState (กันเผื่อ dispose ระหว่าง process ข้างบน)
      setState(() {
        _allSymptoms = symptoms;
        _groupedByLetter = letterMap;
        _availableLetters = letterMap.keys.toList();
        _groupedByCategory = catMap;
        if (_availableLetters.isNotEmpty) {
          _activeLetter = _availableLetters.first;
        }
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('โหลดข้อมูลไม่สำเร็จ: $e')));
      }
    }

    if (!mounted) return; // ✅ เพิ่มตรงนี้ — จุดที่พังใน error log
    setState(() => _isLoading = false);
  }

  List<SymptomModel> get _filtered {
    return _search.isEmpty ? [] : _searchResults;
  }

  @override
  Widget build(BuildContext context) {
    final hp = Responsive.horizontalPadding;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        leading: IconButton(
          tooltip: 'ย้อนกลับ',
          onPressed: () => Navigator.maybePop(context),
          icon: const Icon(Icons.arrow_back_rounded),
        ),
        title: Text('ระบุอาการของคุณ', style: AppTextStyles.h4),
        centerTitle: true,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(0.5),
          child: Divider(height: 0.5, thickness: 0.5, color: AppColors.border),
        ),
      ),
      body: Column(
        children: [
          Padding(
            padding: EdgeInsets.symmetric(horizontal: hp),
            child: Column(
              children: [
                SizedBox(height: Responsive.dp(12)),
                SearchBar(
                  controller: _searchCtrl,
                  hintText: 'ค้นหาอาการ เช่น ปวดหัว ไข้',
                  leading: const Icon(
                    Icons.search_rounded,
                    color: AppColors.textSecondary,
                  ),
                  trailing: [
                    if (_search.isNotEmpty)
                      IconButton(
                        tooltip: 'ล้างคำค้นหา',
                        onPressed: _searchCtrl.clear,
                        icon: const Icon(Icons.close_rounded),
                      ),
                  ],
                ),
                if (_search.isEmpty) ...[
                  SizedBox(height: Responsive.dp(8)),
                  TabBar(
                    controller: _tabController,
                    labelStyle: AppTextStyles.body2Bold,
                    unselectedLabelStyle: AppTextStyles.body2,
                    labelColor: AppColors.primary,
                    unselectedLabelColor: AppColors.textSecondary,
                    indicatorColor: AppColors.primary,
                    indicatorSize: TabBarIndicatorSize.tab,
                    tabs: const [
                      Tab(text: 'ก-ฮ'),
                      Tab(text: 'ตามประเภท'),
                    ],
                  ),
                ],
              ],
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              color: AppColors.primary,
              backgroundColor: AppColors.white,
              elevation: 0,
              onRefresh: _loadSymptoms,
              child: _isLoading
                  ? const AppLoadingView()
                  : _search.isNotEmpty
                  ? _buildSearchResult(hp)
                  : TabBarView(
                      controller: _tabController,
                      children: [_buildByAlphabet(hp), _buildByCategory(hp)],
                    ),
            ),
          ),
          // Bottom Bar ปุ่มถัดไป
          Container(
            padding: EdgeInsets.fromLTRB(hp, 12, hp, 28),
            decoration: const BoxDecoration(
              color: AppColors.white,
              border: Border(top: BorderSide(color: AppColors.border)),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    _selectedId != null
                        ? 'เลือกแล้ว 1 อาการ'
                        : 'ยังไม่ได้เลือกอาการ',
                    style: AppTextStyles.body3.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                ),
                SizedBox(
                  width: 130,
                  child: AppButton(
                    label: 'ถัดไป →',
                    height: 48,
                    onTap: _selectedId == null
                        ? null
                        : () => Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) =>
                                  AssessmentScreen(symptomId: _selectedId!),
                            ),
                          ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSearchResult(double hp) {
    if (_filtered.isEmpty) {
      return Center(
        child: Text(
          'ไม่พบอาการที่ค้นหา',
          style: AppTextStyles.body2.copyWith(color: AppColors.textSecondary),
        ),
      );
    }
    return ListView.builder(
      padding: EdgeInsets.symmetric(horizontal: hp, vertical: 8),
      itemCount: _filtered.length,
      itemBuilder: (_, i) => _SymptomItem(
        symptom: _filtered[i],
        selected: _selectedId == _filtered[i].symptomId,
        onTap: () => setState(() => _selectedId = _filtered[i].symptomId),
      ),
    );
  }

  // ⚡ ปรับปรุง: ใช้ ListView.builder โหลดแบบ Lazy-loading ประสิทธิภาพสูง
  Widget _buildByCategory(double hp) {
    final categoriesList = _groupedByCategory.keys.toList();

    return ListView.builder(
      key: const PageStorageKey('symptom_by_category_list'),
      padding: EdgeInsets.symmetric(horizontal: hp, vertical: 8),
      itemCount: categoriesList.length,
      itemBuilder: (context, index) {
        final cat = categoriesList[index];
        final items = _groupedByCategory[cat]!;
        final isExpanded = _expandedCategoryIds.contains(cat.symptomCategoryId);

        return Padding(
          key: ValueKey(cat.symptomCategoryId),
          padding: EdgeInsets.only(bottom: Responsive.dp(8)),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(14),
            child: Theme(
              data: Theme.of(
                context,
              ).copyWith(dividerColor: Colors.transparent),
              child: ExpansionTile(
                key: PageStorageKey<String>(cat.symptomCategoryId),
                initiallyExpanded: false,
                onExpansionChanged: (expanded) {
                  setState(() {
                    if (expanded) {
                      _expandedCategoryIds.add(cat.symptomCategoryId);
                    } else {
                      _expandedCategoryIds.remove(cat.symptomCategoryId);
                    }
                  });
                },
                collapsedBackgroundColor: AppColors.surface,
                backgroundColor: AppColors.surface,
                textColor: AppColors.primary,
                collapsedTextColor: AppColors.primary,
                iconColor: AppColors.primary,
                collapsedIconColor: AppColors.textSecondary,
                tilePadding: EdgeInsets.symmetric(
                  horizontal: Responsive.dp(16),
                  vertical: Responsive.dp(4),
                ),
                childrenPadding: EdgeInsets.only(
                  left: Responsive.dp(8),
                  right: Responsive.dp(8),
                  bottom: Responsive.dp(8),
                ),
                title: Text(
                  cat.categoryName,
                  style: AppTextStyles.body1Bold.copyWith(
                    color: isExpanded
                        ? AppColors.primary
                        : AppColors.textPrimary, // เปลี่ยนสีตามสถานะ
                  ),
                ),
                children: items
                    .map(
                      (s) => _SymptomItem(
                        symptom: s,
                        selected: _selectedId == s.symptomId,
                        onTap: () => setState(() => _selectedId = s.symptomId),
                      ),
                    )
                    .toList(),
              ),
            ),
          ),
        );
      },
    );
  }

  // ⚡ ปรับปรุง: ใช้ ListView.builder และถอน cacheExtent ออกเพื่อให้ลื่นขึ้น
  Widget _buildByAlphabet(double hp) {
    if (_availableLetters.isEmpty) return const SizedBox.shrink();

    return Row(
      children: [
        Expanded(
          child: ListView.builder(
            controller: _alphabetScrollCtrl,
            padding: EdgeInsets.only(left: hp, right: 4, bottom: 16),
            itemCount: _availableLetters.length,
            itemBuilder: (context, index) {
              final letter = _availableLetters[index];
              final items = _groupedByLetter[letter]!;

              return Column(
                key: _letterKeys[letter],
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    margin: EdgeInsets.only(
                      top: Responsive.dp(12),
                      bottom: Responsive.dp(6),
                    ),
                    padding: EdgeInsets.symmetric(
                      horizontal: Responsive.dp(10),
                      vertical: Responsive.dp(4),
                    ),
                    decoration: BoxDecoration(
                      color: AppColors.primaryLight,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      letter,
                      style: AppTextStyles.body2Bold.copyWith(
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                  ...items.map(
                    (s) => _SymptomItem(
                      symptom: s,
                      selected: _selectedId == s.symptomId,
                      onTap: () => setState(() => _selectedId = s.symptomId),
                    ),
                  ),
                ],
              );
            },
          ),
        ),
        // แถบตัวอักษรด้านข้าง ก-ฮ
        SizedBox(
          width: 28,
          child: ListView.builder(
            controller: _alphabetBarCtrl,
            padding: const EdgeInsets.symmetric(vertical: 4),
            itemCount: _availableLetters.length,
            itemBuilder: (context, index) {
              final letter = _availableLetters[index];
              final isActive = _activeLetter == letter;
              return GestureDetector(
                key: _barLetterKeys[letter],
                onTap: () => _scrollToLetter(letter),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 150),
                  width: 24,
                  height: 24,
                  margin: const EdgeInsets.symmetric(vertical: 2),
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
                      fontSize: 11,
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
}

// วิดเจ็ตแสดงรายการอาการที่คงเดิมไว้
class _SymptomItem extends StatelessWidget {
  final SymptomModel symptom;
  final bool selected;
  final VoidCallback onTap;

  const _SymptomItem({
    required this.symptom,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: Responsive.dp(10)),
      child: Material(
        color: selected ? AppColors.primaryLight : AppColors.white,
        borderRadius: BorderRadius.circular(18),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(18),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 150),
            padding: EdgeInsets.symmetric(
              horizontal: Responsive.dp(14),
              vertical: Responsive.dp(13),
            ),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(18),
              border: Border.all(
                color: selected ? AppColors.primary : AppColors.border,
                width: selected ? 1.5 : 1,
              ),
            ),
            child: Row(
              children: [
                AnimatedContainer(
                  duration: const Duration(milliseconds: 150),
                  width: 42,
                  height: 42,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: selected
                        ? AppColors.primary.withValues(alpha: 0.14)
                        : AppColors.surfacePrimary,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: SymptomIcon(
                    iconName: symptom.symptomImage,
                    size: 21,
                    color: AppColors.primary,
                  ),
                ),
                const SizedBox(width: 13),
                Expanded(
                  child: Text(
                    symptom.symptomName,
                    style: AppTextStyles.body1Bold.copyWith(
                      color: selected
                          ? AppColors.primary
                          : AppColors.textPrimary,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                AnimatedContainer(
                  duration: const Duration(milliseconds: 150),
                  width: 24,
                  height: 24,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: selected ? AppColors.primary : AppColors.border,
                      width: 1.5,
                    ),
                    color: selected ? AppColors.primary : Colors.transparent,
                  ),
                  child: selected
                      ? const Icon(
                          Icons.check_rounded,
                          color: AppColors.white,
                          size: 16,
                        )
                      : null,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
