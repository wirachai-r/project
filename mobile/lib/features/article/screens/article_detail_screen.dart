import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class ArticleDetailScreen extends StatefulWidget {
  final String articleId;
  const ArticleDetailScreen({super.key, required this.articleId});

  @override
  State<ArticleDetailScreen> createState() => _ArticleDetailScreenState();
}

class _ArticleDetailScreenState extends State<ArticleDetailScreen> {
  Map<String, dynamic>? _article;
  bool _isLoading = true;
  String? _error;
  final _startTime = DateTime.now();

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _recordView();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final res = await http.get(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.articles}/${widget.articleId}'),
        headers: {'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        setState(() => _article = jsonDecode(res.body)['data']);
      } else {
        setState(() => _error = 'ไม่พบบทความ');
      }
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _recordView() async {
    final duration = DateTime.now().difference(_startTime).inSeconds;
    try {
      await http.post(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.articles}/${widget.articleId}/view'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'read_duration': duration, 'is_completed': duration > 30 ? 'Y' : 'N'}),
      );
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _isLoading
        ? const Center(child: CircularProgressIndicator())
        : _error != null
          ? Center(child: Text(_error!))
          : _buildContent(),
    );
  }

  Widget _buildContent() {
    final article = _article!;
    return CustomScrollView(
      slivers: [
        SliverAppBar(
          expandedHeight: article['thumbnail'] != null ? 220 : 0,
          pinned: true,
          flexibleSpace: article['thumbnail'] != null
            ? FlexibleSpaceBar(
                background: Image.network(article['thumbnail'], fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => Container(color: AppColors.divider)),
              )
            : null,
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (article['category'] != null)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: AppColors.primaryLight,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(article['category']['category_name'],
                      style: AppTextStyles.body3.copyWith(color: AppColors.primary)),
                  ),
                const SizedBox(height: 12),
                Text(article['title'], style: AppTextStyles.h2),
                const SizedBox(height: 8),
                Text(article['published_at'] ?? '', style: AppTextStyles.body3),
                const Divider(height: 24),
                Text(article['content'] ?? '', style: AppTextStyles.body1.copyWith(height: 1.7)),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
