import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/rich_text_html.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_layout.dart';
import 'dart:convert';
import 'package:mobile/data/services/central_http_client.dart' as http;
import 'package:flutter_html/flutter_html.dart';
import 'package:share_plus/share_plus.dart';
import '../../../shared/widgets/bookmark_button.dart';
import '../../../shared/widgets/content_report_button.dart';
import '../../../shared/widgets/reference_links_section.dart';
import '../../../data/services/first_aid_offline_service.dart';

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
  bool _isOffline = false;
  final _offlineService = FirstAidOfflineService();
  final _scrollController = ScrollController();
  final _titleKey = GlobalKey();
  bool _showTitleInAppBar = false;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_updateAppBarTitle);
    _load();
  }

  @override
  void dispose() {
    _scrollController
      ..removeListener(_updateAppBarTitle)
      ..dispose();
    super.dispose();
  }

  void _updateAppBarTitle() {
    final titleContext = _titleKey.currentContext;
    if (titleContext == null) return;
    final renderBox = titleContext.findRenderObject() as RenderBox?;
    if (renderBox == null || !renderBox.hasSize) return;

    final titleBottom =
        renderBox.localToGlobal(Offset.zero).dy + renderBox.size.height;
    final appBarBottom = MediaQuery.paddingOf(context).top + kToolbarHeight;
    final shouldShowTitle = titleBottom <= appBarBottom;
    if (shouldShowTitle != _showTitleInAppBar) {
      setState(() => _showTitleInAppBar = shouldShowTitle);
    }
  }

  Future<void> _load() async {
    try {
      final res = await http.get(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.firstAids}/${widget.firstAidId}',
        ),
        headers: {'Accept': 'application/json'},
      );
      if (!mounted) return;
      if (res.statusCode == 200) {
        setState(() {
          _item = jsonDecode(res.body)['data'];
          _isOffline = false;
        });
      } else {
        throw Exception('Unable to load first aid content');
      }
    } catch (e) {
      final cached = await _offlineService.read(widget.firstAidId);
      if (!mounted) return;
      if (cached == null) {
        setState(() => _error = e.toString());
      } else {
        setState(() {
          _item = cached;
          _isOffline = true;
          _error = null;
        });
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: Text(
        _showTitleInAppBar &&
                _item?['title']?.toString().trim().isNotEmpty == true
            ? _item!['title'].toString()
            : 'รายละเอียดปฐมพยาบาล',
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: AppTextStyles.h4,
      ),
      bottom: const PreferredSize(
        preferredSize: Size.fromHeight(1),
        child: Divider(),
      ),
      actions: [
        ContentReportButton(
          targetType: 'first_aid',
          targetId: widget.firstAidId,
        ),
      ],
    ),
    body: _isLoading
        ? const AppLoadingView()
        : _error != null
        ? AppMessageView.error(message: _error!, onAction: _load)
        : _buildContent(),
  );

  Widget _buildContent() {
    final item = _item!;
    return RefreshIndicator(
      color: AppColors.primary,
      backgroundColor: AppColors.white,
      elevation: 0,
      onRefresh: _load,
      child: ResponsiveBuilder(
        builder: (context) => AppContentWidth(
          maxWidth: 760,
          child: ListView(
            controller: _scrollController,
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.only(bottom: 32),
            children: [
              if (item['thumbnail'] != null)
                AspectRatio(
                  aspectRatio: 16 / 9,
                  child: Image.network(
                    item['thumbnail'],
                    fit: BoxFit.cover,
                    errorBuilder: (_, _, _) =>
                        const ColoredBox(color: AppColors.surface),
                  ),
                ),
              Padding(
                padding: EdgeInsets.fromLTRB(
                  Responsive.horizontalPadding,
                  22,
                  Responsive.horizontalPadding,
                  0,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (item['category'] != null)
                      Chip(label: Text(item['category']['category_name'])),
                    if (_isOffline) ...[
                      const SizedBox(height: 12),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: Colors.amber.shade100,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Text(
                          'ข้อมูลออฟไลน์ที่บันทึกไว้ล่าสุด อาจไม่ใช่ฉบับปัจจุบัน',
                        ),
                      ),
                    ],
                    const SizedBox(height: 10),
                    Text(
                      item['title'],
                      key: _titleKey,
                      style: AppTextStyles.h2,
                    ),
                    const SizedBox(height: 12),
                    _buildArticleMeta(item),
                    const SizedBox(height: 18),
                    _buildActions(item),
                    const Divider(height: 36),
                    _buildHtmlContent(item['content']?.toString() ?? ''),
                    ReferenceLinksSection(
                      links: ReferenceLinksSection.fromJson(item['references']),
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

  Widget _buildHtmlContent(String content) => Html(
    data: RichTextHtml.resolveMediaUrls(content),
    extensions: RichTextHtml.imageExtensions,
    style: {
      'body': Style(
        margin: Margins.zero,
        padding: HtmlPaddings.zero,
        color: AppColors.textPrimary,
        fontSize: FontSize(16),
        lineHeight: const LineHeight(1.8),
      ),
      'p': Style(margin: Margins.only(bottom: 12)),
      'img': Style(margin: Margins.symmetric(vertical: 10)),
      'ul': Style(margin: Margins.only(bottom: 10)),
      'ol': Style(margin: Margins.only(bottom: 10)),
      'strong': Style(fontWeight: FontWeight.w700),
    },
  );

  Widget _buildActions(Map<String, dynamic> item) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 6),
    decoration: BoxDecoration(
      color: AppColors.white,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: AppColors.border),
    ),
    child: Row(
      children: [
        Expanded(
          child: _ContentAction(
            label: 'แชร์',
            icon: Icons.ios_share_rounded,
            onTap: () => SharePlus.instance.share(
              ShareParams(
                text: 'คู่มือปฐมพยาบาล: ${item['title']}\nอ่านเพิ่มเติมในแอป',
              ),
            ),
          ),
        ),
        Container(width: 1, height: 36, color: AppColors.border),
        Expanded(
          child: BookmarkButton(
            type: 'App\\Models\\FirstAid',
            itemId: widget.firstAidId,
            selectedColor: AppColors.warning,
            compact: true,
            label: 'บันทึก',
            labelStyle: AppTextStyles.body3,
          ),
        ),
      ],
    ),
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
        _meta(Icons.calendar_today_outlined, 'วันที่เผยแพร่ $publishedText'),
        _meta(Icons.update_rounded, 'แก้ไขล่าสุด $updatedText'),
        _meta(Icons.visibility_outlined, '${item['view_count'] ?? 0} ครั้ง'),
      ],
    );
  }

  String _formatDate(dynamic value) {
    final date = DateTime.tryParse(value?.toString() ?? '')?.toLocal();
    if (date == null) return '-';
    return formatThaiDate(date);
  }
}

class _ContentAction extends StatelessWidget {
  final String label;
  final IconData icon;
  final VoidCallback onTap;

  const _ContentAction({
    required this.label,
    required this.icon,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) => Material(
    color: Colors.transparent,
    borderRadius: BorderRadius.circular(14),
    child: InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      hoverColor: AppColors.surface,
      splashColor: AppColors.primary.withValues(alpha: 0.10),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: AppColors.textPrimary, size: 24),
            const SizedBox(height: 4),
            Text(label, style: AppTextStyles.body3),
          ],
        ),
      ),
    ),
  );
}
