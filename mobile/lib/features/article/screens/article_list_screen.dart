import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import 'article_detail_screen.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class ArticleListScreen extends StatefulWidget {
  const ArticleListScreen({super.key});

  @override
  State<ArticleListScreen> createState() => _ArticleListScreenState();
}

class _ArticleListScreenState extends State<ArticleListScreen> {
  List<dynamic> _articles = [];
  List<dynamic> _categories = [];
  String? _selectedCategoryId;
  bool _isLoading = true;
  String? _error;
  final _searchCtrl = TextEditingController();
  int _page = 1;
  bool _hasMore = true;
  bool _loadingMore = false;
  final _scrollCtrl = ScrollController();

  @override
  void initState() {
    super.initState();
    _loadCategories();
    _loadArticles(refresh: true);
    _scrollCtrl.addListener(_onScroll);
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollCtrl.position.pixels >=
        _scrollCtrl.position.maxScrollExtent - 200) {
      if (!_loadingMore && _hasMore) _loadArticles();
    }
  }

  Future<void> _loadCategories() async {
    final res = await http.get(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.articleCategories}'),
      headers: {'Accept': 'application/json'},
    );
    if (res.statusCode == 200) {
      setState(() => _categories = jsonDecode(res.body)['data'] ?? []);
    }
  }

  Future<void> _loadArticles({bool refresh = false}) async {
    if (refresh) {
      setState(() {
        _page = 1;
        _hasMore = true;
        _isLoading = true;
        _error = null;
      });
    } else {
      setState(() => _loadingMore = true);
    }

    try {
      final uri = Uri.parse('${ApiConstants.baseUrl}${ApiConstants.articles}')
          .replace(
            queryParameters: {
              'page': '$_page',
              if (_selectedCategoryId != null)
                'article_category_id': _selectedCategoryId!,
              if (_searchCtrl.text.isNotEmpty) 'search': _searchCtrl.text,
            },
          );

      final res = await http.get(uri, headers: {'Accept': 'application/json'});
      final data = jsonDecode(res.body);
      final items = data['data'] as List? ?? [];

      setState(() {
        if (refresh)
          _articles = items;
        else
          _articles.addAll(items);
        _hasMore =
            data['meta']?['current_page'] < (data['meta']?['last_page'] ?? 1);
        _page++;
      });
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      setState(() {
        _isLoading = false;
        _loadingMore = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: AppColors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Text('บทความสุขภาพ', style: AppTextStyles.h4),
        centerTitle: true,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(0.5),
          child: Divider(
            height: 0.5,
            thickness: 0.5,
            color: AppColors.border,
          ),
        ),
      ),
      body: Column(
        children: [
          _buildSearchBar(),
          _buildCategories(),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildSearchBar() => Padding(
    padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
    child: TextField(
      controller: _searchCtrl,
      decoration: const InputDecoration(
        hintText: 'ค้นหาบทความ...',
        prefixIcon: Icon(Icons.search),
      ),
      onSubmitted: (_) => _loadArticles(refresh: true),
    ),
  );

  Widget _buildCategories() {
    if (_categories.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: SizedBox(
        height: 40,
        child: ListView.builder(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          itemCount: _categories.length + 1,
          itemBuilder: (_, i) {
            if (i == 0) return _catChip(null, 'ทั้งหมด');
            final cat = _categories[i - 1];
            return _catChip(cat['article_category_id'], cat['category_name']);
          },
        ),
      ),
    );
  }

  Widget _catChip(String? id, String label) {
    final selected = _selectedCategoryId == id;
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
            if (selected) return;
            setState(() => _selectedCategoryId = id);
            _loadArticles(refresh: true);
          },
          child: ConstrainedBox(
            constraints: const BoxConstraints(minWidth: 72),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14),
              child: Center(
                child: Text(
                  label,
                  maxLines: 1,
                  style: (selected
                          ? AppTextStyles.body3Bold
                          : AppTextStyles.body3)
                      .copyWith(
                    color: selected ? Colors.white : AppColors.textSecondary,
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) return const Center(child: CircularProgressIndicator());
    if (_error != null)
      return Center(child: Text(_error!, style: AppTextStyles.body2));
    if (_articles.isEmpty) return const Center(child: Text('ไม่พบบทความ'));

    return RefreshIndicator(
      color: AppColors.primary,
      backgroundColor: AppColors.white,
      elevation: 0,
      onRefresh: () => _loadArticles(refresh: true),
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        controller: _scrollCtrl,
        padding: const EdgeInsets.all(16),
        itemCount: _articles.length + (_loadingMore ? 1 : 0),
        itemBuilder: (_, i) {
          if (i == _articles.length)
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(16),
                child: CircularProgressIndicator(),
              ),
            );
          return _ArticleCard(article: _articles[i]);
        },
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
                    errorBuilder: (_, __, ___) => _placeholder(),
                  ),
                )
              else
                _placeholder(),
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
                          Icons.calendar_today_outlined,
                          _formatDate(article['published_at']),
                        ),
                        _meta(
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

  Widget _placeholder() => Container(
    width: 80,
    height: 80,
    decoration: BoxDecoration(
      color: AppColors.surface,
      borderRadius: BorderRadius.circular(8),
    ),
    child: const Icon(Icons.article_outlined, color: AppColors.textSecondary),
  );

  Widget _meta(IconData icon, String text) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Icon(icon, size: 14, color: AppColors.textSecondary),
      const SizedBox(width: 4),
      Text(
        text,
        style: AppTextStyles.body3.copyWith(color: AppColors.textSecondary),
      ),
    ],
  );

  String _formatDate(dynamic value) {
    final date = DateTime.tryParse(value?.toString() ?? '')?.toLocal();
    if (date == null) return '-';
    return '${date.day.toString().padLeft(2, '0')}/'
        '${date.month.toString().padLeft(2, '0')}/'
        '${date.year + 543}';
  }
}
