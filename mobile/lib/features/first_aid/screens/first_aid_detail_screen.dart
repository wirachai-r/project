import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_html/flutter_html.dart';
import '../../../shared/widgets/bookmark_button.dart';

class FirstAidDetailScreen extends StatefulWidget {
  final String firstAidId;
  const FirstAidDetailScreen({super.key, required this.firstAidId});

  @override
  State<FirstAidDetailScreen> createState() => _FirstAidDetailScreenState();
}

class _FirstAidDetailScreenState extends State<FirstAidDetailScreen> {
  Map<String, dynamic>? _item;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await http.get(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.firstAids}/${widget.firstAidId}',
        ),
        headers: {'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        setState(() => _item = jsonDecode(res.body)['data']);
      } else {
        setState(() => _error = 'ไม่พบข้อมูล');
      }
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Scaffold(
              appBar: AppBar(),
              body: Center(child: Text(_error!)),
            )
          : _buildContent(),
    );
  }

  Widget _buildContent() {
    final item = _item!;
    return RefreshIndicator(
      color: AppColors.primary,
      backgroundColor: AppColors.white,
      elevation: 0,
      onRefresh: _load,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
        SliverAppBar(
          expandedHeight: item['thumbnail'] != null ? 220 : 0,
          pinned: true,
          actions: [BookmarkButton(type: 'App\\Models\\FirstAid', itemId: widget.firstAidId)],
          flexibleSpace: item['thumbnail'] != null
              ? FlexibleSpaceBar(
                  background: Image.network(
                    item['thumbnail'],
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) =>
                        Container(color: AppColors.surface),
                  ),
                )
              : null,
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (item['category'] != null)
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFEDD5),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      item['category']['category_name'],
                      style: AppTextStyles.body3.copyWith(
                        color: const Color(0xFFEA580C),
                      ),
                    ),
                  ),
                const SizedBox(height: 12),
                Text(item['title'], style: AppTextStyles.h2),
                const SizedBox(height: 8),
                _buildArticleMeta(item),
                const Divider(height: 24),
                _buildHtmlContent(item['content']?.toString() ?? ''),
              ],
            ),
          ),
        ),
        ],
      ),
    );
  }

  Widget _buildHtmlContent(String content) => Html(
    data: content,
    style: {
      'body': Style(
        margin: Margins.zero,
        padding: HtmlPaddings.zero,
        color: AppColors.textPrimary,
        fontSize: FontSize(16),
        lineHeight: const LineHeight(1.8),
      ),
      'p': Style(margin: Margins.only(bottom: 12)),
      'img': Style(
        width: Width(100, Unit.percent),
        margin: Margins.symmetric(vertical: 10),
      ),
      'ul': Style(margin: Margins.only(bottom: 10)),
      'ol': Style(margin: Margins.only(bottom: 10)),
      'strong': Style(fontWeight: FontWeight.w700),
    },
  );

  Widget _meta(IconData icon, String text) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Icon(icon, size: 16, color: AppColors.textSecondary),
      const SizedBox(width: 6),
      Text(
        text,
        style: AppTextStyles.body3.copyWith(color: AppColors.textSecondary),
      ),
    ],
  );

  Widget _buildArticleMeta(Map<String, dynamic> item) {
    final publishedText = _formatDate(
      item['published_at'] ?? item['created_at'],
    );
    final updatedText = _formatDate(
      item['updated_at'] ?? item['published_at'] ?? item['created_at'],
    );

    return Wrap(
      spacing: 16,
      runSpacing: 8,
      children: [
        _meta(
          Icons.calendar_today_outlined,
          'วันที่เผยแพร่ $publishedText',
        ),
        _meta(Icons.update_rounded, 'แก้ไขล่าสุด $updatedText'),
        _meta(
          Icons.visibility_outlined,
          '${item['view_count'] ?? 0} ครั้ง',
        ),
      ],
    );
  }

  String _formatDate(dynamic value) {
    final date = DateTime.tryParse(value?.toString() ?? '')?.toLocal();
    if (date == null) return '-';
    return '${date.day.toString().padLeft(2, '0')}/'
        '${date.month.toString().padLeft(2, '0')}/'
        '${date.year + 543}';
  }
}
