import 'dart:convert';

import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:checkup/data/services/central_http_client.dart' as http;
import 'package:provider/provider.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/utils/media_url.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/rich_text_html.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_layout.dart';
import '../../../shared/widgets/bookmark_button.dart';
import '../../../shared/widgets/content_report_button.dart';
import '../../../shared/widgets/content_detail_section.dart';
import '../../../shared/widgets/reference_links_section.dart';
import '../../../shared/widgets/login_bottom_sheet.dart';
import '../../auth/providers/auth_provider.dart';

class ArticleDetailScreen extends StatefulWidget {
  final String articleId;

  const ArticleDetailScreen({super.key, required this.articleId});

  @override
  State<ArticleDetailScreen> createState() => _ArticleDetailScreenState();
}

class _ArticleDetailScreenState extends State<ArticleDetailScreen> {
  Map<String, dynamic>? _article;
  List<dynamic> _comments = [];
  bool _isLoading = true;
  bool _commentsLoading = true;
  bool _liked = false;
  bool _likeBusy = false;
  final Set<dynamic> _likedCommentIds = {};
  String? _error;
  String? _authToken;
  final _commentController = TextEditingController();
  final _commentFocusNode = FocusNode();
  final _scrollController = ScrollController();
  final _articleTitleKey = GlobalKey();
  final _commentsSectionKey = GlobalKey();
  bool _showTitleInAppBar = false;
  bool _commentSubmitting = false;
  final _startTime = DateTime.now();

  Map<String, String> get _headers {
    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (_authToken != null && _authToken!.isNotEmpty)
        'Authorization': 'Bearer $_authToken',
    };
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _authToken = context.read<AuthProvider>().token;
  }

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_updateAppBarTitle);
    _load();
    _loadComments();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadEngagement());
  }

  @override
  void dispose() {
    _scrollController
      ..removeListener(_updateAppBarTitle)
      ..dispose();
    _commentController.dispose();
    _commentFocusNode.dispose();
    _recordView();
    super.dispose();
  }

  void _updateAppBarTitle() {
    final titleContext = _articleTitleKey.currentContext;
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

  Future<void> _showComments() async {
    final commentsContext = _commentsSectionKey.currentContext;
    if (commentsContext == null) return;

    await Scrollable.ensureVisible(
      commentsContext,
      duration: const Duration(milliseconds: 350),
      curve: Curves.easeOutCubic,
      alignment: 0.05,
    );

    if (mounted && context.read<AuthProvider>().isAuthenticated) {
      _commentFocusNode.requestFocus();
    }
  }

  Future<void> _load() async {
    try {
      final response = await http.get(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.articleDetail(widget.articleId)}',
        ),
        headers: _headers,
      );
      if (!mounted) return;
      if (response.statusCode == 200) {
        setState(
          () => _article = jsonDecode(utf8.decode(response.bodyBytes))['data'],
        );
      } else {
        setState(() => _error = 'ไม่พบบทความ');
      }
    } catch (_) {
      if (mounted) setState(() => _error = 'ไม่สามารถโหลดบทความได้');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _loadEngagement() async {
    if (!context.read<AuthProvider>().isAuthenticated) return;
    try {
      final response = await http.get(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.articleEngagement(widget.articleId)}',
        ),
        headers: _headers,
      );
      if (!mounted || response.statusCode != 200) return;
      final data = jsonDecode(utf8.decode(response.bodyBytes));
      setState(() {
        _liked = data['liked'] == true;
        _article?['likes_count'] = data['likes_count'] ?? 0;
        _article?['comments_count'] = data['comments_count'] ?? 0;
      });
    } catch (_) {}
  }

  Future<void> _loadComments() async {
    _authToken ??= context.read<AuthProvider>().token;
    try {
      final response = await http.get(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.articleComments(widget.articleId)}',
        ),
        headers: _headers,
      );
      if (!mounted || response.statusCode != 200) return;
      final data = jsonDecode(utf8.decode(response.bodyBytes));
      setState(() {
        _comments = data['data'] as List? ?? [];
        _likedCommentIds.clear();
        for (final comment in _comments) {
          if (comment['liked'] == true) _likedCommentIds.add(comment['id']);
          for (final reply in (comment['replies'] as List? ?? const [])) {
            if (reply['liked'] == true) _likedCommentIds.add(reply['id']);
          }
        }
      });
    } finally {
      if (mounted) setState(() => _commentsLoading = false);
    }
  }

  Future<void> _toggleLike() async {
    if (!context.read<AuthProvider>().isAuthenticated) {
      LoginBottomSheet.show(context);
      return;
    }
    if (_likeBusy) return;
    setState(() => _likeBusy = true);
    try {
      final response = await http.post(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.articleLike(widget.articleId)}',
        ),
        headers: _headers,
      );
      if (!mounted || response.statusCode != 200) return;
      final data = jsonDecode(utf8.decode(response.bodyBytes));
      setState(() {
        _liked = data['liked'] == true;
        _article?['likes_count'] = data['likes_count'] ?? 0;
      });
    } finally {
      if (mounted) setState(() => _likeBusy = false);
    }
  }

  Future<void> _submitComment() async {
    if (!context.read<AuthProvider>().isAuthenticated) {
      LoginBottomSheet.show(context);
      return;
    }
    final content = _commentController.text.trim();
    if (content.isEmpty || _commentSubmitting) return;
    setState(() => _commentSubmitting = true);
    final response = await http.post(
      Uri.parse(
        '${ApiConstants.baseUrl}${ApiConstants.articleComments(widget.articleId)}',
      ),
      headers: _headers,
      body: jsonEncode({'content': content}),
    );
    if (!mounted) return;
    setState(() => _commentSubmitting = false);
    if (response.statusCode != 201) return;
    _commentController.clear();
    FocusScope.of(context).unfocus();
    setState(() {
      _article?['comments_count'] = (_article?['commentราs_count'] ?? 0) + 1;
    });
    await _loadComments();
  }

  Future<void> _deleteComment(dynamic id) async {
    if (!context.read<AuthProvider>().isAuthenticated) {
      LoginBottomSheet.show(context);
      return;
    }
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AppActionDialog(
        icon: Icons.delete_outline_rounded,
        iconColor: AppColors.danger,
        iconBackgroundColor: AppColors.surfaceDanger,
        title: 'ลบความคิดเห็น',
        message: 'ต้องการลบความคิดเห็นนี้ใช่หรือไม่?',
        primaryLabel: 'ลบความคิดเห็น',
        primaryColor: AppColors.danger,
        onPrimary: () => Navigator.pop(dialogContext, true),
        secondaryLabel: 'ยกเลิก',
        onSecondary: () => Navigator.pop(dialogContext, false),
      ),
    );
    if (confirmed != true || !mounted) return;
    final response = await http.delete(
      Uri.parse(
        '${ApiConstants.baseUrl}${ApiConstants.articleCommentDelete(id)}',
      ),
      headers: _headers,
    );
    if (!mounted || response.statusCode != 200) return;
    setState(() {
      _article?['comments_count'] = ((_article?['comments_count'] ?? 1) - 1)
          .clamp(0, 999999);
    });
    await _loadComments();
  }

  Future<void> _toggleCommentLike(dynamic id) async {
    if (!context.read<AuthProvider>().isAuthenticated) {
      LoginBottomSheet.show(context);
      return;
    }
    final response = await http.post(
      Uri.parse(
        '${ApiConstants.baseUrl}${ApiConstants.articleCommentLike(id)}',
      ),
      headers: _headers,
    );
    if (!mounted || response.statusCode != 200) return;
    final data = jsonDecode(utf8.decode(response.bodyBytes));
    setState(() {
      if (data['liked'] == true) {
        _likedCommentIds.add(id);
      } else {
        _likedCommentIds.remove(id);
      }
      for (final comment in _comments) {
        if (comment['id'] == id) {
          comment['likes_count'] = data['likes_count'] ?? 0;
        }
        for (final reply in (comment['replies'] as List? ?? const [])) {
          if (reply['id'] == id) {
            reply['likes_count'] = data['likes_count'] ?? 0;
          }
        }
      }
    });
  }

  Future<bool> _submitReply(dynamic parentId, String content) async {
    if (!context.read<AuthProvider>().isAuthenticated) {
      LoginBottomSheet.show(context);
      return false;
    }
    final response = await http.post(
      Uri.parse(
        '${ApiConstants.baseUrl}${ApiConstants.articleComments(widget.articleId)}',
      ),
      headers: _headers,
      body: jsonEncode({'content': content, 'parent_id': parentId}),
    );
    if (!mounted || response.statusCode != 201) return false;
    setState(() {
      _article?['comments_count'] = (_article?['comments_count'] ?? 0) + 1;
    });
    await _loadComments();
    return true;
  }

  Future<void> _reportComment(dynamic id) async {
    if (!context.read<AuthProvider>().isAuthenticated) {
      LoginBottomSheet.show(context);
      return;
    }
    const reasons = {
      'spam': 'สแปม',
      'inappropriate': 'เนื้อหาไม่เหมาะสม',
      'misleading': 'ข้อมูลสุขภาพที่อาจทำให้เข้าใจผิด',
      'harassment': 'คุกคามหรือใช้ถ้อยคำไม่สุภาพ',
      'other': 'อื่น ๆ',
    };
    final reason = await showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (sheetContext) => SafeArea(
        child: AppContentWidth(
          shrinkWrapHeight: true,
          child: ConstrainedBox(
            constraints: BoxConstraints(
              maxHeight: MediaQuery.sizeOf(sheetContext).height * .82,
            ),
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('รายงานความคิดเห็น', style: AppTextStyles.h3),
                  const SizedBox(height: 4),
                  Text(
                    'เลือกหัวข้อที่ตรงกับสิ่งที่คุณพบมากที่สุด',
                    style: AppTextStyles.body2.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                  ),
                  const SizedBox(height: 12),
                  for (final item in reasons.entries)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: Material(
                        color: Theme.of(context).colorScheme.surface,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                          side: BorderSide(
                            color: Theme.of(context).colorScheme.outlineVariant,
                          ),
                        ),
                        clipBehavior: Clip.antiAlias,
                        child: ListTile(
                          minTileHeight: 54,
                          leading: const Icon(
                            Icons.flag_outlined,
                            color: AppColors.primary,
                          ),
                          title: Text(
                            item.value,
                            style: AppTextStyles.body1Bold,
                          ),
                          trailing: const Icon(Icons.chevron_right_rounded),
                          onTap: () => Navigator.pop(sheetContext, item.key),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
    if (reason == null || !mounted) return;
    final details = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => _CommentReportDetailsSheet(reasonLabel: reasons[reason]!),
    );
    if (details == null || !mounted) return;
    final response = await http.post(
      Uri.parse(
        '${ApiConstants.baseUrl}${ApiConstants.articleCommentReport(id)}',
      ),
      headers: _headers,
      body: jsonEncode({
        'reason': reason,
        'details': details.isEmpty ? null : details,
      }),
    );
    if (mounted && response.statusCode == 201) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('ส่งรายงานให้ผู้ดูแลแล้ว')));
    }
  }

  Future<void> _recordView() async {
    if (_authToken == null || _authToken!.isEmpty) return;
    final duration = DateTime.now().difference(_startTime).inSeconds;
    try {
      await http.post(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.articleView(widget.articleId)}',
        ),
        headers: _headers,
        body: jsonEncode({
          'read_duration': duration,
          'is_completed': duration > 30 ? 'Y' : 'N',
        }),
      );
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: Theme.of(context).scaffoldBackgroundColor,
    appBar: AppBar(
      title: Text(
        _showTitleInAppBar &&
                _article?['title']?.toString().trim().isNotEmpty == true
            ? _article!['title'].toString()
            : 'รายละเอียดบทความ',
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: AppTextStyles.h4,
      ),
      bottom: const PreferredSize(
        preferredSize: Size.fromHeight(1),
        child: Divider(),
      ),
    ),
    body: _isLoading
        ? const AppLoadingView()
        : _error != null
        ? AppMessageView.error(message: _error!, onAction: _load)
        : _buildContent(),
  );

  Widget _buildContent() {
    final auth = context.watch<AuthProvider>();
    _authToken = auth.token;
    final article = _article!;
    return RefreshIndicator(
      onRefresh: () async {
        await Future.wait([_load(), _loadComments(), _loadEngagement()]);
      },
      child: ResponsiveBuilder(
        builder: (context) => AppContentWidth(
          maxWidth: 760,
          child: ListView(
            controller: _scrollController,
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.only(bottom: 32),
            children: [
              if (article['thumbnail'] != null)
                AspectRatio(
                  aspectRatio: 16 / 9,
                  child: Image.network(
                    resolveMediaUrl(article['thumbnail'])!,
                    fit: BoxFit.cover,
                    errorBuilder: (_, _, _) => ColoredBox(
                      color: Theme.of(context).colorScheme.surfaceContainer,
                    ),
                  ),
                ),
              Padding(
                padding: EdgeInsets.fromLTRB(
                  ContentDetailSpacing.horizontalPadding,
                  ContentDetailSpacing.headerTopPadding,
                  ContentDetailSpacing.horizontalPadding,
                  0,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (article['category'] != null)
                      Chip(
                        backgroundColor: AppColors.primaryLight,
                        side: BorderSide.none,
                        label: Text(
                          article['category']['category_name'],
                          style: AppTextStyles.body2.copyWith(
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                    const SizedBox(height: 10),
                    Text(
                      article['title'],
                      key: _articleTitleKey,
                      style: AppTextStyles.h2,
                    ),
                    const SizedBox(height: 12),
                    _buildArticleMeta(article),
                    const SizedBox(height: 18),
                    _buildActions(article),
                    const SizedBox(height: 22),
                    ContentDetailBodyCard(
                      child: _buildHtmlContent(
                        article['content']?.toString() ?? '',
                      ),
                    ),
                    ReferenceLinksSection(
                      links: ReferenceLinksSection.fromJson(
                        article['references'],
                      ),
                    ),
                    const Divider(height: 40),
                    _buildComments(),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildActions(Map<String, dynamic> article) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 6),
    decoration: BoxDecoration(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
    ),
    child: Row(
      children: [
        Expanded(
          child: _ActionItem(
            label: '',
            value: '${article['likes_count'] ?? 0}',
            icon: _liked ? Icons.thumb_up_rounded : Icons.thumb_up_outlined,
            selected: _liked,
            busy: _likeBusy,
            onTap: _toggleLike,
          ),
        ),
        const _ActionDivider(),
        Expanded(
          child: _ActionItem(
            label: 'ความคิดเห็น',
            value: '${article['comments_count'] ?? 0}',
            icon: Icons.chat_bubble_outline_rounded,
            onTap: _showComments,
          ),
        ),
        const _ActionDivider(),
        Expanded(
          child: ContentReportButton(
            targetType: 'article',
            targetId: widget.articleId,
            compact: true,
            label: 'รายงาน',
            labelStyle: AppTextStyles.body3,
          ),
        ),
        const _ActionDivider(),
        Expanded(
          child: BookmarkButton(
            type: 'App\\Models\\Article',
            itemId: widget.articleId,
            selectedColor: AppColors.warning,
            compact: true,
            label: 'บันทึก',
            labelStyle: AppTextStyles.body3,
          ),
        ),
      ],
    ),
  );

  Widget _buildComments() {
    final auth = context.watch<AuthProvider>();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('ความคิดเห็น', key: _commentsSectionKey, style: AppTextStyles.h4),
        const SizedBox(height: 14),
        TextField(
          controller: _commentController,
          focusNode: _commentFocusNode,
          readOnly: !auth.isAuthenticated,
          onTap: auth.isAuthenticated
              ? null
              : () => LoginBottomSheet.show(context),
          maxLines: 3,
          minLines: 1,
          decoration: InputDecoration(
            hintText: auth.isAuthenticated
                ? 'แบ่งปันความคิดเห็นเกี่ยวกับบทความนี้'
                : 'เข้าสู่ระบบเพื่อแสดงความคิดเห็น',
            suffixIcon: IconButton(
              tooltip: 'ส่งความคิดเห็น',
              onPressed: _commentSubmitting ? null : _submitComment,
              icon: _commentSubmitting
                  ? const Padding(
                      padding: EdgeInsets.all(12),
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.send_rounded),
            ),
          ),
        ),
        const SizedBox(height: 16),
        if (_commentsLoading)
          const AppLoadingView()
        else if (_comments.isEmpty)
          Text(
            'ยังไม่มีความคิดเห็น เป็นคนแรกที่แสดงความคิดเห็น',
            style: AppTextStyles.body2.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          )
        else
          ..._comments.map(
            (comment) => _CommentTile(
              comment: comment,
              canDelete:
                  auth.isAuthenticated &&
                  comment['user_id'] == auth.user?.userId,
              onDelete: () => _deleteComment(comment['id']),
              onLike: () => _toggleCommentLike(comment['id']),
              liked: _likedCommentIds.contains(comment['id']),
              onReport: () {
                if (comment['user_id'] == auth.user?.userId) {
                  showAppError(context, 'ไม่สามารถรายงานความคิดเห็นของตัวเองได้');
                  return;
                }
                _reportComment(comment['id']);
              },
              onSubmitReply: (content) => _submitReply(comment['id'], content),
              onReplyLike: _toggleCommentLike,
              onReplyReport: _reportComment,
              onReplyDelete: _deleteComment,
              likedCommentIds: _likedCommentIds,
              currentUserId: auth.user?.userId,
            ),
          ),
      ],
    );
  }

  Widget _buildHtmlContent(String content) => Html(
    data: RichTextHtml.resolveMediaUrls(content),
    extensions: RichTextHtml.imageExtensions,
    style: {
      'body': Style(
        margin: Margins.zero,
        padding: HtmlPaddings.zero,
        color: Theme.of(context).colorScheme.onSurface,
        fontSize: FontSize(16),
        lineHeight: const LineHeight(1.7),
      ),
      'p': Style(margin: Margins.only(bottom: 12)),
      'img': Style(margin: Margins.symmetric(vertical: 10)),
    },
  );

  Widget _buildArticleMeta(Map<String, dynamic> article) {
    final publishedText = _formatDate(
      article['published_at'] ?? article['created_at'],
    );
    final updatedText = _formatDate(
      article['updated_at'] ?? article['published_at'] ?? article['created_at'],
    );

    return ContentDetailMeta(
      items: [
        ContentDetailMetaItem(
          icon: Icons.calendar_today_outlined,
          label: 'วันที่เผยแพร่ $publishedText',
        ),
        if (updatedText != publishedText)
          ContentDetailMetaItem(
            icon: Icons.update_rounded,
            label: 'แก้ไขล่าสุด $updatedText',
          ),
        ContentDetailMetaItem(
          icon: Icons.visibility_outlined,
          label: '${article['view_count'] ?? 0} ครั้ง',
        ),
      ],
    );
  }

  String _formatDate(dynamic value) {
    final date = DateTime.tryParse(value?.toString() ?? '')?.toLocal();
    if (date == null) return '-';
    return formatThaiDate(date);
  }
}

class _CommentReportDetailsSheet extends StatefulWidget {
  final String reasonLabel;

  const _CommentReportDetailsSheet({required this.reasonLabel});

  @override
  State<_CommentReportDetailsSheet> createState() =>
      _CommentReportDetailsSheetState();
}

class _CommentReportDetailsSheetState
    extends State<_CommentReportDetailsSheet> {
  final _controller = TextEditingController();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => AnimatedPadding(
    duration: const Duration(milliseconds: 180),
    curve: Curves.easeOut,
    padding: EdgeInsets.fromLTRB(
      20,
      20,
      20,
      MediaQuery.viewInsetsOf(context).bottom + 20,
    ),
    child: Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('รายละเอียดเพิ่มเติม', style: AppTextStyles.h4),
        const SizedBox(height: 6),
        Text(
          'หัวข้อที่เลือก: ${widget.reasonLabel}',
          style: AppTextStyles.body2.copyWith(
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
        const SizedBox(height: 16),
        Text(
          'อธิบายสิ่งที่พบเพื่อช่วยให้ผู้ดูแลตรวจสอบได้ชัดเจนขึ้น (ไม่บังคับ)',
          style: AppTextStyles.body2Bold,
        ),
        const SizedBox(height: 8),
        TextField(
          controller: _controller,
          autofocus: true,
          minLines: 4,
          maxLines: 8,
          decoration: const InputDecoration(
            hintText: 'เขียนรายละเอียดที่ต้องการรายงาน',
          ),
        ),
        const SizedBox(height: 16),
        FilledButton(
          onPressed: () => Navigator.pop(context, _controller.text.trim()),
          style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(52)),
          child: const Text('ส่งรายงาน'),
        ),
      ],
    ),
  );
}

class _ActionItem extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final VoidCallback onTap;
  final bool selected;
  final bool busy;

  const _ActionItem({
    required this.label,
    required this.value,
    required this.icon,
    required this.onTap,
    this.selected = false,
    this.busy = false,
  });

  @override
  Widget build(BuildContext context) => Material(
    color: Colors.transparent,
    borderRadius: BorderRadius.circular(14),
    child: InkWell(
      onTap: busy ? null : onTap,
      borderRadius: BorderRadius.circular(14),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            busy
                ? const SizedBox.square(
                    dimension: 24,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : Icon(
                    icon,
                    size: 24,
                    color: selected
                        ? AppColors.primary
                        : Theme.of(context).colorScheme.onSurface,
                  ),
            const SizedBox(height: 4),
            Text(
              label.isEmpty
                  ? value
                  : value.isEmpty
                  ? label
                  : '$label $value',
              style: AppTextStyles.body3,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    ),
  );
}

class _ActionDivider extends StatelessWidget {
  const _ActionDivider();

  @override
  Widget build(BuildContext context) => Container(
    width: 1,
    height: 36,
    color: Theme.of(context).colorScheme.outlineVariant,
  );
}

class _CommentTile extends StatefulWidget {
  final dynamic comment;
  final bool canDelete;
  final bool liked;
  final VoidCallback onDelete;
  final VoidCallback onLike;
  final VoidCallback onReport;
  final Future<bool> Function(String content) onSubmitReply;
  final Future<void> Function(dynamic id) onReplyLike;
  final Future<void> Function(dynamic id) onReplyReport;
  final Future<void> Function(dynamic id) onReplyDelete;
  final Set<dynamic> likedCommentIds;
  final dynamic currentUserId;

  const _CommentTile({
    required this.comment,
    required this.canDelete,
    required this.liked,
    required this.onDelete,
    required this.onLike,
    required this.onReport,
    required this.onSubmitReply,
    required this.onReplyLike,
    required this.onReplyReport,
    required this.onReplyDelete,
    required this.likedCommentIds,
    required this.currentUserId,
  });

  @override
  State<_CommentTile> createState() => _CommentTileState();
}

class _CommentTileState extends State<_CommentTile> {
  final _replyController = TextEditingController();
  final _replyFocusNode = FocusNode();
  bool _showComposer = false;
  bool _submitting = false;
  bool _includeMention = false;
  dynamic _replyingToCommentId;
  String? _replyingToName;

  @override
  void dispose() {
    _replyController.dispose();
    _replyFocusNode.dispose();
    super.dispose();
  }

  void _openComposer(
    dynamic commentId,
    Map user, {
    bool includeMention = false,
  }) {
    if (!context.read<AuthProvider>().isAuthenticated) {
      LoginBottomSheet.show(context);
      return;
    }
    final name = '${user['first_name'] ?? ''} ${user['last_name'] ?? ''}'
        .trim();
    setState(() {
      _showComposer = true;
      _includeMention = includeMention;
      _replyingToCommentId = commentId;
      _replyingToName = name.isEmpty ? 'ผู้ใช้งาน' : name;
    });
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _replyFocusNode.requestFocus();
    });
  }

  Future<void> _sendReply() async {
    final content = _replyController.text.trim();
    if (content.isEmpty || _submitting) return;
    setState(() => _submitting = true);
    final mention = _includeMention && _replyingToName != null
        ? '@$_replyingToName '
        : '';
    final sent = await widget.onSubmitReply('$mention$content');
    if (!mounted) return;
    setState(() => _submitting = false);
    if (sent) {
      _replyController.clear();
      setState(() {
        _showComposer = false;
        _replyingToCommentId = null;
        _replyingToName = null;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final comment = widget.comment;
    final user = comment['user'] as Map? ?? const {};
    final name = '${user['first_name'] ?? ''} ${user['last_name'] ?? ''}'
        .trim();
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            backgroundColor: AppColors.primaryLight,
            foregroundImage:
                (user['profile_image']?.toString().isNotEmpty ?? false)
                ? NetworkImage(resolveMediaUrl(user['profile_image'])!)
                : null,
            child: Text(name.isEmpty ? '?' : name.characters.first),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(
                  color: Theme.of(context).colorScheme.outlineVariant,
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              name.isEmpty ? 'ผู้ใช้งาน' : name,
                              style: AppTextStyles.body2Bold,
                            ),
                            Text(
                              _formatCommentTime(comment['created_at']),
                              style: AppTextStyles.body3.copyWith(
                                color: Theme.of(
                                  context,
                                ).colorScheme.onSurfaceVariant,
                              ),
                            ),
                          ],
                        ),
                      ),
                      if (widget.canDelete)
                        IconButton(
                          tooltip: 'ลบความคิดเห็น',
                          onPressed: widget.onDelete,
                          icon: const Icon(
                            Icons.delete_outline_rounded,
                            size: 20,
                          ),
                        ),
                    ],
                  ),
                  _ExpandableCommentText(
                    text: comment['content']?.toString() ?? '',
                    style: AppTextStyles.body1.copyWith(
                      color: AppColors.textPrimary,
                      fontWeight: FontWeight.w500,
                      height: 1.45,
                    ),
                  ),
                  const SizedBox(height: 8),
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        TextButton.icon(
                          onPressed: widget.onLike,
                          style: TextButton.styleFrom(
                            foregroundColor: widget.liked
                                ? AppColors.primary
                                : Theme.of(
                                    context,
                                  ).colorScheme.onSurfaceVariant,
                          ),
                          icon: Icon(
                            widget.liked
                                ? Icons.thumb_up_rounded
                                : Icons.thumb_up_outlined,
                            size: 17,
                            color: widget.liked
                                ? AppColors.primary
                                : Theme.of(
                                    context,
                                  ).colorScheme.onSurfaceVariant,
                          ),
                          label: Text('${comment['likes_count'] ?? 0}'),
                        ),
                        TextButton(
                          onPressed: () => _openComposer(comment['id'], user),
                          style: TextButton.styleFrom(
                            foregroundColor: Theme.of(
                              context,
                            ).colorScheme.onSurfaceVariant,
                          ),
                          child: const Text('ตอบกลับ'),
                        ),
                        if (!widget.canDelete)
                          TextButton(
                            onPressed: widget.onReport,
                            style: TextButton.styleFrom(
                              foregroundColor: Theme.of(
                                context,
                              ).colorScheme.onSurfaceVariant,
                            ),
                            child: const Text('รายงาน'),
                          ),
                      ],
                    ),
                  ),
                  if (_showComposer && _replyingToCommentId == comment['id'])
                    _buildReplyComposer(),
                  _ReplyThread(
                    replies: comment['replies'] as List? ?? const [],
                    onReply: (replyId, replyUser) =>
                        _openComposer(replyId, replyUser, includeMention: true),
                    onLike: widget.onReplyLike,
                    onReport: widget.onReplyReport,
                    onDelete: widget.onReplyDelete,
                    likedCommentIds: widget.likedCommentIds,
                    currentUserId: widget.currentUserId,
                    replyingToCommentId: _showComposer
                        ? _replyingToCommentId
                        : null,
                    replyComposer: _buildReplyComposer(),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReplyComposer() => _InlineReplyComposer(
    controller: _replyController,
    focusNode: _replyFocusNode,
    replyingToName: _replyingToName,
    submitting: _submitting,
    onSend: _sendReply,
    onCancel: () => setState(() {
      _showComposer = false;
      _replyingToCommentId = null;
      _replyingToName = null;
      _replyController.clear();
    }),
  );
}

class _ReplyTile extends StatelessWidget {
  final dynamic reply;
  final VoidCallback onReply;
  final VoidCallback onLike;
  final VoidCallback? onReport;
  final VoidCallback? onDelete;
  final bool liked;

  const _ReplyTile({
    required this.reply,
    required this.onReply,
    required this.onLike,
    required this.onReport,
    required this.onDelete,
    required this.liked,
  });

  @override
  Widget build(BuildContext context) {
    final user = reply['user'] as Map? ?? const {};
    final name = '${user['first_name'] ?? ''} ${user['last_name'] ?? ''}'
        .trim();
    final displayName = name.isEmpty ? 'ผู้ใช้งาน' : name;

    return Padding(
      padding: const EdgeInsets.only(top: 10, left: 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 14,
            backgroundColor: AppColors.primaryLight,
            foregroundImage:
                (user['profile_image']?.toString().isNotEmpty ?? false)
                ? NetworkImage(resolveMediaUrl(user['profile_image'])!)
                : null,
            child: Text(
              displayName.characters.first,
              style: AppTextStyles.body3Bold,
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 9,
                  ),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.surface,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(displayName, style: AppTextStyles.body3Bold),
                      Text(
                        _formatCommentTime(reply['created_at']),
                        style: AppTextStyles.body3.copyWith(
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                      ),
                      const SizedBox(height: 2),
                      _ExpandableCommentText(
                        text: reply['content']?.toString() ?? '',
                        style: AppTextStyles.body1.copyWith(
                          color: AppColors.textPrimary,
                          fontWeight: FontWeight.w500,
                          height: 1.45,
                        ),
                      ),
                    ],
                  ),
                ),
                Wrap(
                  spacing: 2,
                  children: [
                    TextButton.icon(
                      onPressed: onLike,
                      style: TextButton.styleFrom(
                        foregroundColor: liked
                            ? AppColors.primary
                            : Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                      icon: Icon(
                        liked
                            ? Icons.thumb_up_rounded
                            : Icons.thumb_up_outlined,
                        size: 15,
                      ),
                      label: Text('${reply['likes_count'] ?? 0}'),
                    ),
                    TextButton(
                      onPressed: onReply,
                      style: TextButton.styleFrom(
                        foregroundColor: Theme.of(
                          context,
                        ).colorScheme.onSurfaceVariant,
                      ),
                      child: const Text('ตอบกลับ'),
                    ),
                    if (onReport != null)
                      TextButton(
                        onPressed: onReport,
                        style: TextButton.styleFrom(
                          foregroundColor: Theme.of(
                            context,
                          ).colorScheme.onSurfaceVariant,
                        ),
                        child: const Text('รายงาน'),
                      ),
                    if (onDelete != null)
                      IconButton(
                        tooltip: 'ลบความคิดเห็น',
                        onPressed: onDelete,
                        icon: const Icon(
                          Icons.delete_outline_rounded,
                          size: 18,
                        ),
                      ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ReplyThread extends StatefulWidget {
  final List replies;
  final void Function(dynamic id, Map user) onReply;
  final Future<void> Function(dynamic id) onLike;
  final Future<void> Function(dynamic id) onReport;
  final Future<void> Function(dynamic id) onDelete;
  final Set<dynamic> likedCommentIds;
  final dynamic currentUserId;
  final dynamic replyingToCommentId;
  final Widget replyComposer;

  const _ReplyThread({
    required this.replies,
    required this.onReply,
    required this.onLike,
    required this.onReport,
    required this.onDelete,
    required this.likedCommentIds,
    required this.currentUserId,
    required this.replyingToCommentId,
    required this.replyComposer,
  });

  @override
  State<_ReplyThread> createState() => _ReplyThreadState();
}

class _ReplyThreadState extends State<_ReplyThread> {
  bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    if (widget.replies.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: TextButton.icon(
            onPressed: () => setState(() => _expanded = !_expanded),
            style: TextButton.styleFrom(
              visualDensity: VisualDensity.compact,
              padding: const EdgeInsets.symmetric(horizontal: 8),
            ),
            icon: Icon(
              _expanded
                  ? Icons.keyboard_arrow_up_rounded
                  : Icons.keyboard_arrow_down_rounded,
              size: 18,
            ),
            label: Text(
              _expanded
                  ? 'ซ่อนการตอบกลับ'
                  : 'ดูการตอบกลับ ${widget.replies.length} รายการ',
            ),
          ),
        ),
        if (_expanded)
          for (final reply in widget.replies) ...[
            _ReplyTile(
              reply: reply,
              onReply: () => widget.onReply(
                reply['id'],
                reply['user'] as Map? ?? const {},
              ),
              onLike: () => widget.onLike(reply['id']),
              onReport: reply['user_id'] == widget.currentUserId
                  ? null
                  : () => widget.onReport(reply['id']),
              onDelete: reply['user_id'] == widget.currentUserId
                  ? () => widget.onDelete(reply['id'])
                  : null,
              liked: widget.likedCommentIds.contains(reply['id']),
            ),
            if (widget.replyingToCommentId == reply['id'])
              Padding(
                padding: const EdgeInsets.only(left: 38),
                child: widget.replyComposer,
              ),
          ],
      ],
    );
  }
}

class _InlineReplyComposer extends StatelessWidget {
  final TextEditingController controller;
  final FocusNode focusNode;
  final String? replyingToName;
  final bool submitting;
  final VoidCallback onSend;
  final VoidCallback onCancel;

  const _InlineReplyComposer({
    required this.controller,
    required this.focusNode,
    required this.replyingToName,
    required this.submitting,
    required this.onSend,
    required this.onCancel,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 8, left: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  'ตอบกลับ $replyingToName',
                  style: AppTextStyles.body3.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                ),
              ),
              IconButton(
                tooltip: 'ยกเลิกการตอบกลับ',
                onPressed: onCancel,
                icon: const Icon(Icons.close_rounded, size: 18),
              ),
            ],
          ),
          TextField(
            controller: controller,
            focusNode: focusNode,
            minLines: 1,
            maxLines: 4,
            textInputAction: TextInputAction.newline,
            decoration: InputDecoration(
              hintText: 'เขียนข้อความตอบกลับ...',
              counterText: '',
              suffixIcon: IconButton(
                tooltip: 'ส่งข้อความตอบกลับ',
                onPressed: submitting ? null : onSend,
                icon: submitting
                    ? const Padding(
                        padding: EdgeInsets.all(12),
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.send_rounded),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

String _formatCommentTime(dynamic value) {
  final date = DateTime.tryParse(value?.toString() ?? '');
  if (date == null) return '';
  return formatThaiDateTime(date.toLocal());
}

class _ExpandableCommentText extends StatefulWidget {
  final String text;
  final TextStyle style;

  const _ExpandableCommentText({required this.text, required this.style});

  @override
  State<_ExpandableCommentText> createState() => _ExpandableCommentTextState();
}

class _ExpandableCommentTextState extends State<_ExpandableCommentText> {
  bool _expanded = false;
  bool _overflowed = false;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final painter = TextPainter(
          text: TextSpan(text: widget.text, style: widget.style),
          maxLines: 3,
          textDirection: Directionality.of(context),
        )..layout(maxWidth: constraints.maxWidth);
        _overflowed = painter.didExceedMaxLines;

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.text,
              style: widget.style,
              maxLines: _expanded ? null : 3,
              overflow: _expanded
                  ? TextOverflow.visible
                  : TextOverflow.ellipsis,
            ),
            if (_overflowed)
              GestureDetector(
                onTap: () => setState(() => _expanded = !_expanded),
                child: Padding(
                  padding: const EdgeInsets.only(top: 2),
                  child: Text(
                    _expanded ? 'แสดงน้อยลง' : 'อ่านเพิ่มเติม',
                    style: widget.style.copyWith(fontWeight: FontWeight.w600),
                  ),
                ),
              ),
          ],
        );
      },
    );
  }
}
