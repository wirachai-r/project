import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/rich_text_html.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_layout.dart';

class NotificationDetailScreen extends StatefulWidget {
  final Map<String, dynamic> item;

  const NotificationDetailScreen({super.key, required this.item});

  @override
  State<NotificationDetailScreen> createState() =>
      _NotificationDetailScreenState();
}

class _NotificationDetailScreenState extends State<NotificationDetailScreen> {
  final _scrollController = ScrollController();
  final _titleKey = GlobalKey();
  bool _showTitleInAppBar = false;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_updateAppBarTitle);
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

  @override
  Widget build(BuildContext context) {
    final title = widget.item['title']?.toString() ?? 'การแจ้งเตือน';
    final body = widget.item['body']?.toString() ?? '';
    final isSystem = widget.item['type'] != 'U';

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text(
          _showTitleInAppBar ? title : 'รายละเอียดการแจ้งเตือน',
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: AppTextStyles.h4,
        ),
        bottom: PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(
            height: 1,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: ResponsiveBuilder(
        builder: (context) => AppContentWidth(
          child: ListView(
            controller: _scrollController,
            physics: const AlwaysScrollableScrollPhysics(),
            padding: EdgeInsets.fromLTRB(
              Responsive.horizontalPadding,
              22,
              Responsive.horizontalPadding,
              32,
            ),
            children: [
              Align(
                alignment: Alignment.centerLeft,
                child: Chip(
                  avatar: Icon(
                    isSystem
                        ? Icons.notifications_outlined
                        : Icons.person_outline_rounded,
                    size: 18,
                    color: AppColors.primary,
                  ),
                  label: Text(
                    isSystem ? 'แจ้งเตือนจากระบบ' : 'แจ้งเตือนส่วนตัว',
                  ),
                ),
              ),
              const SizedBox(height: 10),
              Text(title, key: _titleKey, style: AppTextStyles.h2),
              const SizedBox(height: 12),
              Row(
                children: [
                  Icon(
                    Icons.schedule_rounded,
                    size: 17,
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                  const SizedBox(width: 6),
                  Text(
                    _formatDate(widget.item['created_at']),
                    style: AppTextStyles.body3.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
              Divider(
                height: 36,
                color: Theme.of(context).colorScheme.outlineVariant,
              ),
              AppPanel(
                child: Html(
                  data: RichTextHtml.resolveMediaUrls(body),
                  extensions: RichTextHtml.imageExtensions,
                  style: {
                    'body': Style(
                      margin: Margins.zero,
                      padding: HtmlPaddings.zero,
                      color: Theme.of(context).colorScheme.onSurface,
                      fontSize: FontSize(16),
                      lineHeight: const LineHeight(1.8),
                    ),
                    'p': Style(margin: Margins.only(bottom: 12)),
                    'img': Style(margin: Margins.symmetric(vertical: 10)),
                    'ul': Style(margin: Margins.only(bottom: 10)),
                    'ol': Style(margin: Margins.only(bottom: 10)),
                    'strong': Style(fontWeight: FontWeight.w700),
                  },
                ),
              ),
              if ((widget.item['target_url']?.toString() ?? '').isNotEmpty) ...[
                const SizedBox(height: 20),
                FilledButton.icon(
                  onPressed: () =>
                      _openTarget(widget.item['target_url'].toString()),
                  icon: const Icon(Icons.open_in_new_rounded),
                  label: const Text('เปิดรายละเอียด'),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _openTarget(String target) async {
    final uri = Uri.tryParse(target);
    if (uri == null ||
        !await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('ไม่สามารถเปิดลิงก์นี้ได้')),
        );
      }
    }
  }

  String _formatDate(dynamic value) {
    final date = DateTime.tryParse(value?.toString() ?? '')?.toLocal();
    if (date == null) return '';
    return formatThaiDateTime(date);
  }
}
