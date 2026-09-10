import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../shared/widgets/app_layout.dart';
import 'article_detail_screen.dart';
import 'dart:async';

import 'dart:convert';
import 'package:mobile/data/services/central_http_client.dart' as http;

class ArticleListScreen extends StatefulWidget {
  const ArticleListScreen({super.key});

  @override
  State<ArticleListScreen> createState() => _ArticleListScreenState();
}

class _ArticleListScreenState extends State<ArticleListScreen> {
  List<dynamic> _articles = [];
  List<dynamic> _categories = [];
  String? _selectedCategoryId;
  String _sort = 'all';
  bool _isLoading = true;
  String? _error;
  final _searchCtrl = TextEditingController();
  Timer? _searchDebounce;
  int _page = 1;
  int _lastPage = 1;
  final _scrollCtrl = ScrollController();

  @override
  void initState() {
    super.initState();
    _loadCategories();
    _loadArticles(refresh: true);
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _searchDebounce?.cancel();
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _onSearchChanged(String value) {
    setState(() {});
    _searchDebounce?.cancel();
    _searchDebounce = Timer(
      const Duration(milliseconds: 350),
      () => _loadArticles(refresh: true),
    );
  }

  Future<void> _loadCategories() async {
    try {
      final res = await http.get(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.articleCategories}'),
        headers: {'Accept': 'application/json'},
      );
      if (!mounted) return;
      if (res.statusCode == 200) {
        setState(() => _categories = jsonDecode(res.body)['data'] ?? []);
      }
    } catch (_) {
      // The article request displays the page-level connection error.
    }
  }

  Future<void> _loadArticles({bool refresh = false, int? page}) async {
    final requestedPage = refresh ? 1 : (page ?? _page);
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final queryParameters = <String, String>{'page': '$requestedPage'};
      if (_sort != 'all') queryParameters['sort'] = _sort;
      if (_selectedCategoryId != null) {
        queryParameters['article_category_id'] = _selectedCategoryId!;
      }
      if (_searchCtrl.text.isNotEmpty) {
        queryParameters['search'] = _searchCtrl.text;
      }
      final uri = Uri.parse(
        '${ApiConstants.baseUrl}${ApiConstants.articles}',
      ).replace(queryParameters: queryParameters);

      final res = await http.get(uri, headers: {'Accept': 'application/json'});
      if (!mounted) return;
      final data = jsonDecode(res.body);
      final items = data['data'] as List? ?? [];

      setState(() {
        _articles = items;
        _page = data['meta']?['current_page'] ?? requestedPage;
        _lastPage = data['meta']?['last_page'] ?? 1;
      });
      if (_scrollCtrl.hasClients) _scrollCtrl.jumpTo(0);
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Text('บทความสุขภาพ', style: AppTextStyles.h4),
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
            _buildSearchBar(),
            _buildResultHeader(),
            Expanded(
              child: AnimatedSwitcher(
                duration: const Duration(milliseconds: 180),
                switchInCurve: Curves.easeOut,
                switchOutCurve: Curves.easeIn,
                child: KeyedSubtree(
                  key: ValueKey((_isLoading, _error, _articles.length, _page)),
                  child: _buildBody(),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSearchBar() => Padding(
    padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
    child: Row(
      children: [
        Expanded(
          child: TextField(
            controller: _searchCtrl,
            decoration: InputDecoration(
              hintText: 'ค้นหาเรื่องสุขภาพ...',
              prefixIcon: const Icon(Icons.search_rounded),
              suffixIcon: _searchCtrl.text.isEmpty
                  ? null
                  : IconButton(
                      tooltip: 'ล้างคำค้นหา',
                      onPressed: () {
                        _searchCtrl.clear();
                        setState(() {});
                        _loadArticles(refresh: true);
                      },
                      icon: const Icon(Icons.close_rounded),
                    ),
            ),
            textInputAction: TextInputAction.search,
            onChanged: _onSearchChanged,
            onSubmitted: (_) => _loadArticles(refresh: true),
          ),
        ),
        const SizedBox(width: 10),
        Badge(
          isLabelVisible: _selectedCategoryId != null || _sort != 'all',
          smallSize: 8,
          child: IconButton.filled(
            tooltip: 'ตัวกรองบทความ',
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
  );

  Widget _buildResultHeader() => Padding(
    padding: const EdgeInsets.fromLTRB(16, 20, 16, 8),
    child: Row(
      children: [
        Expanded(
          child: Text(
            _sort == 'all'
                ? 'บทความทั้งหมด'
                : (_sort == 'popular' ? 'บทความยอดนิยม' : 'บทความล่าสุด'),
            style: AppTextStyles.h4,
          ),
        ),
        if (_selectedCategoryId != null)
          TextButton(
            onPressed: () {
              setState(() => _selectedCategoryId = null);
              _loadArticles(refresh: true);
            },
            child: const Text('ล้างหมวดหมู่'),
          ),
      ],
    ),
  );

  Future<void> _showFilters() async {
    var pendingSort = _sort;
    var pendingCategoryId = _selectedCategoryId;

    final apply = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      showDragHandle: true,
      builder: (sheetContext) => StatefulBuilder(
        builder: (context, setSheetState) => Padding(
          padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text('ตัวกรองบทความ', style: AppTextStyles.h4),
                  ),
                  TextButton(
                    onPressed: () => setSheetState(() {
                      pendingSort = 'all';
                      pendingCategoryId = null;
                    }),
                    child: const Text('ล้างทั้งหมด'),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              Text('เรียงตาม', style: AppTextStyles.body2Bold),
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _filterChip(
                    label: 'ทั้งหมด',
                    selected: pendingSort == 'all',
                    onSelected: () => setSheetState(() => pendingSort = 'all'),
                  ),
                  _filterChip(
                    label: 'ยอดนิยม',
                    selected: pendingSort == 'popular',
                    onSelected: () =>
                        setSheetState(() => pendingSort = 'popular'),
                  ),
                  _filterChip(
                    label: 'ล่าสุด',
                    selected: pendingSort == 'latest',
                    onSelected: () =>
                        setSheetState(() => pendingSort = 'latest'),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              Text('หมวดหมู่', style: AppTextStyles.body2Bold),
              const SizedBox(height: 10),
              if (_categories.isEmpty)
                Text(
                  'ยังไม่มีหมวดหมู่ให้เลือก',
                  style: AppTextStyles.body2.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                )
              else
                ConstrainedBox(
                  constraints: const BoxConstraints(maxHeight: 220),
                  child: SingleChildScrollView(
                    child: Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        _filterChip(
                          label: 'ทั้งหมด',
                          selected: pendingCategoryId == null,
                          onSelected: () =>
                              setSheetState(() => pendingCategoryId = null),
                        ),
                        for (final category in _categories)
                          _filterChip(
                            label: category['category_name'],
                            selected:
                                pendingCategoryId ==
                                category['article_category_id'],
                            onSelected: () => setSheetState(
                              () => pendingCategoryId =
                                  category['article_category_id'],
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
              const SizedBox(height: 28),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: () => Navigator.pop(sheetContext, true),
                  child: const Text('แสดงผลบทความ'),
                ),
              ),
            ],
          ),
        ),
      ),
    );

    if (apply != true || !mounted) return;
    setState(() {
      _sort = pendingSort;
      _selectedCategoryId = pendingCategoryId;
    });
    _loadArticles(refresh: true);
  }

  Widget _filterChip({
    required String label,
    required bool selected,
    required VoidCallback onSelected,
  }) => ChoiceChip(
    label: Text(label),
    selected: selected,
    onSelected: (_) => onSelected(),
  );

  Widget _buildBody() {
    if (_isLoading) return const AppLoadingView();
    if (_error != null) {
      return AppMessageView.error(
        message: _error!,
        onAction: () => _loadArticles(refresh: true),
      );
    }
    if (_articles.isEmpty) {
      return const AppMessageView.empty(
        title: 'ไม่พบบทความ',
        message: 'ลองเปลี่ยนหมวดหมู่หรือตัวกรองแล้วค้นหาอีกครั้ง',
      );
    }

    return RefreshIndicator(
      color: AppColors.primary,
      backgroundColor: Theme.of(context).colorScheme.surface,
      elevation: 0,
      onRefresh: () => _loadArticles(refresh: true),
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        controller: _scrollCtrl,
        padding: const EdgeInsets.all(16),
        children: [
          for (final article in _articles) _ArticleCard(article: article),
          if (_lastPage > 1) _buildPagination(),
        ],
      ),
    );
  }

  Widget _buildPagination() {
    final firstPage = (_page - 2)
        .clamp(1, (_lastPage - 4).clamp(1, _lastPage))
        .toInt();
    final finalPage = (firstPage + 4).clamp(1, _lastPage).toInt();

    return Padding(
      padding: const EdgeInsets.only(top: 8, bottom: 16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          IconButton(
            tooltip: 'หน้าก่อนหน้า',
            onPressed: _page > 1 ? () => _loadArticles(page: _page - 1) : null,
            icon: const Icon(Icons.chevron_left_rounded),
          ),
          for (var page = firstPage; page <= finalPage; page++)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 2),
              child: page == _page
                  ? FilledButton(
                      onPressed: null,
                      style: FilledButton.styleFrom(
                        disabledBackgroundColor: AppColors.primary,
                        disabledForegroundColor: AppColors.white,
                        minimumSize: const Size(48, 48),
                        padding: EdgeInsets.zero,
                      ),
                      child: Text('$page'),
                    )
                  : TextButton(
                      onPressed: () => _loadArticles(page: page),
                      style: TextButton.styleFrom(
                        minimumSize: const Size(48, 48),
                        padding: EdgeInsets.zero,
                      ),
                      child: Text('$page'),
                    ),
            ),
          IconButton(
            tooltip: 'หน้าถัดไป',
            onPressed: _page < _lastPage
                ? () => _loadArticles(page: _page + 1)
                : null,
            icon: const Icon(Icons.chevron_right_rounded),
          ),
        ],
      ),
    );
  }
}

class _ArticleCard extends StatelessWidget {
  final dynamic article;
  const _ArticleCard({required this.article});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      clipBehavior: Clip.antiAlias,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: Theme.of(context).colorScheme.outline),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) =>
                ArticleDetailScreen(articleId: article['article_id']),
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (article['thumbnail'] != null)
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: Image.network(
                    article['thumbnail'],
                    width: 80,
                    height: 80,
                    fit: BoxFit.cover,
                    errorBuilder: (_, _, _) => _placeholder(context),
                  ),
                )
              else
                _placeholder(context),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (article['category'] != null)
                      Text(
                        article['category']['category_name'],
                        style: AppTextStyles.body3.copyWith(
                          color: AppColors.primary,
                        ),
                      ),
                    const SizedBox(height: 4),
                    Text(
                      article['title'],
                      style: AppTextStyles.body1Bold,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Wrap(
                      spacing: 12,
                      runSpacing: 4,
                      children: [
                        _meta(
                          context,
                          Icons.calendar_today_outlined,
                          _formatDate(article['published_at']),
                        ),
                        _meta(
                          context,
                          Icons.visibility_outlined,
                          '${article['view_count'] ?? 0} ครั้ง',
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _placeholder(BuildContext context) => Container(
    width: 80,
    height: 80,
    decoration: BoxDecoration(
      color: Theme.of(context).colorScheme.surfaceContainer,
      borderRadius: BorderRadius.circular(8),
    ),
    child: Icon(
      Icons.article_outlined,
      color: Theme.of(context).colorScheme.onSurfaceVariant,
    ),
  );

  Widget _meta(BuildContext context, IconData icon, String text) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Icon(
        icon,
        size: 14,
        color: Theme.of(context).colorScheme.onSurfaceVariant,
      ),
      const SizedBox(width: 4),
      Text(
        text,
        style: AppTextStyles.body3.copyWith(
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
      ),
    ],
  );

  String _formatDate(dynamic value) {
    final date = DateTime.tryParse(value?.toString() ?? '')?.toLocal();
    if (date == null) return '-';
    return formatThaiDate(date);
  }
}
