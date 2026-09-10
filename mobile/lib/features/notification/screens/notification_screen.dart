import 'dart:convert';

import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:mobile/data/services/central_http_client.dart' as http;

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../shared/widgets/app_layout.dart';
import 'notification_detail_screen.dart';

class NotificationScreen extends StatefulWidget {
  final String token;

  const NotificationScreen({super.key, required this.token});

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;
  List<Map<String, dynamic>> _items = [];
  bool _isLoading = true;
  bool _isMarkingAll = false;
  String? _error;

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _load();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    if (mounted) {
      setState(() {
        _isLoading = true;
        _error = null;
      });
    }
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.notifications}'),
        headers: _headers,
      );
      if (!mounted) return;
      if (response.statusCode == 200) {
        final body = jsonDecode(utf8.decode(response.bodyBytes));
        final data = body['data'] as List? ?? const [];
        setState(() {
          _items = data.map((item) => Map<String, dynamic>.from(item)).toList();
        });
      } else {
        setState(() => _error = 'ไม่สามารถโหลดการแจ้งเตือนได้');
      }
    } catch (_) {
      if (mounted) setState(() => _error = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _markAllRead() async {
    if (_isMarkingAll || _unreadCount == 0) return;
    setState(() => _isMarkingAll = true);
    try {
      final response = await http.post(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.notificationsReadAll}',
        ),
        headers: _headers,
        body: '{}',
      );
      if (!mounted) return;
      if (response.statusCode == 200) {
        setState(() {
          for (final item in _items) {
            item['is_read'] = 'Y';
          }
        });
      } else {
        _showError('ไม่สามารถทำเครื่องหมายว่าอ่านทั้งหมดได้');
      }
    } catch (_) {
      _showError('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
    } finally {
      if (mounted) setState(() => _isMarkingAll = false);
    }
  }

  Future<void> _markRead(Map<String, dynamic> item) async {
    if (item['is_read'] == 'Y') return;
    final id = item['id'];
    try {
      final response = await http.patch(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.notificationRead(id)}',
        ),
        headers: _headers,
        body: '{}',
      );
      if (!mounted) return;
      if (response.statusCode == 200) {
        setState(() => item['is_read'] = 'Y');
      } else {
        _showError('ไม่สามารถทำเครื่องหมายว่าอ่านแล้วได้');
      }
    } catch (_) {
      _showError('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
    }
  }

  Future<bool> _requestDismiss(Map<String, dynamic> item) async {
    try {
      final response = await http.patch(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.notificationDismiss(item['id'])}',
        ),
        headers: _headers,
        body: '{}',
      );
      if (!mounted) return false;
      if (response.statusCode == 200) return true;
      _showError('ไม่สามารถนำการแจ้งเตือนออกได้');
    } catch (_) {
      _showError('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
    }
    return false;
  }

  void _completeDismiss(Map<String, dynamic> item) {
    final originalIndex = _items.indexWhere(
      (value) => value['id'] == item['id'],
    );
    setState(() => _items.removeWhere((value) => value['id'] == item['id']));
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: const Text('นำการแจ้งเตือนออกแล้ว'),
          action: SnackBarAction(
            label: 'เลิกทำ',
            onPressed: () => _restore(item, originalIndex),
          ),
        ),
      );
  }

  Future<void> _restore(Map<String, dynamic> item, int originalIndex) async {
    try {
      final response = await http.patch(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.notificationRestore(item['id'])}',
        ),
        headers: _headers,
        body: '{}',
      );
      if (!mounted) return;
      if (response.statusCode != 200) {
        _showError('ไม่สามารถคืนการแจ้งเตือนได้');
        return;
      }
      final restored = Map<String, dynamic>.from(
        jsonDecode(utf8.decode(response.bodyBytes))['data'] ?? item,
      );
      setState(() {
        final index = originalIndex.clamp(0, _items.length).toInt();
        _items.insert(index, restored);
      });
    } catch (_) {
      _showError('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
    }
  }

  Future<void> _openDetail(Map<String, dynamic> item) async {
    await _markRead(item);
    if (!mounted) return;
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => NotificationDetailScreen(item: item)),
    );
  }

  void _showError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: AppColors.danger),
    );
  }

  bool _isSystem(Map<String, dynamic> item) => item['type'] != 'U';

  List<Map<String, dynamic>> get _systemItems =>
      _items.where(_isSystem).toList();

  List<Map<String, dynamic>> get _personalItems =>
      _items.where((item) => !_isSystem(item)).toList();

  int get _unreadCount => _items.where((item) => item['is_read'] == 'N').length;

  int _unreadIn(List<Map<String, dynamic>> items) =>
      items.where((item) => item['is_read'] == 'N').length;

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    final systemItems = _systemItems;
    final personalItems = _personalItems;

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        title: Text('การแจ้งเตือน', style: AppTextStyles.h4),
        actions: [
          if (_unreadCount > 0)
            TextButton(
              onPressed: _isMarkingAll ? null : _markAllRead,
              child: _isMarkingAll
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('อ่านทั้งหมด'),
            ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(61),
          child: Column(
            children: [
              Divider(
                height: 1,
                thickness: 1,
                color: Theme.of(context).colorScheme.outlineVariant,
              ),
              AppContentWidth(
                shrinkWrapHeight: true,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 6, 16, 6),
                  child: Container(
                    height: 48,
                    padding: const EdgeInsets.all(3),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.surfaceContainer,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: TabBar(
                      controller: _tabController,
                      labelColor: AppColors.primary,
                      unselectedLabelColor: Theme.of(
                        context,
                      ).colorScheme.onSurface,
                      labelStyle: AppTextStyles.body2Bold,
                      unselectedLabelStyle: AppTextStyles.body2,
                      indicatorSize: TabBarIndicatorSize.tab,
                      indicator: BoxDecoration(
                        color: AppColors.primaryLight,
                        borderRadius: BorderRadius.circular(13),
                      ),
                      dividerColor: Colors.transparent,
                      splashBorderRadius: BorderRadius.circular(13),
                      tabs: [
                        _NotificationTab(
                          label: 'ทั้งหมด',
                          unread: _unreadCount,
                        ),
                        _NotificationTab(
                          label: 'ระบบ',
                          unread: _unreadIn(systemItems),
                        ),
                        _NotificationTab(
                          label: 'ส่วนตัว',
                          unread: _unreadIn(personalItems),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
      body: _buildBody(systemItems, personalItems),
    );
  }

  Widget _buildBody(
    List<Map<String, dynamic>> systemItems,
    List<Map<String, dynamic>> personalItems,
  ) {
    if (_isLoading) return const AppLoadingView();
    if (_error != null && _items.isEmpty) {
      return _ErrorView(message: _error!, onRetry: _load);
    }
    return TabBarView(
      controller: _tabController,
      children: [
        _NotificationList(
          items: _items,
          emptyMessage: 'ยังไม่มีการแจ้งเตือน',
          onRefresh: _load,
          onTap: _openDetail,
          onDismissRequest: _requestDismiss,
          onDismissed: _completeDismiss,
        ),
        _NotificationList(
          items: systemItems,
          emptyMessage: 'ยังไม่มีการแจ้งเตือนจากระบบ',
          onRefresh: _load,
          onTap: _openDetail,
          onDismissRequest: _requestDismiss,
          onDismissed: _completeDismiss,
        ),
        _NotificationList(
          items: personalItems,
          emptyMessage: 'ยังไม่มีการแจ้งเตือนส่วนตัว',
          onRefresh: _load,
          onTap: _openDetail,
          onDismissRequest: _requestDismiss,
          onDismissed: _completeDismiss,
        ),
      ],
    );
  }
}

class _NotificationTab extends StatelessWidget {
  final String label;
  final int unread;

  const _NotificationTab({required this.label, required this.unread});

  @override
  Widget build(BuildContext context) => Tab(
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(label),
        if (unread > 0) ...[
          const SizedBox(width: 5),
          _CountBadge(count: unread, compact: true),
        ],
      ],
    ),
  );
}

class _CountBadge extends StatelessWidget {
  final int count;
  final bool compact;

  const _CountBadge({required this.count, this.compact = false});

  @override
  Widget build(BuildContext context) => Container(
    constraints: BoxConstraints(minWidth: compact ? 18 : 20),
    padding: EdgeInsets.symmetric(
      horizontal: compact ? 4 : 6,
      vertical: compact ? 1 : 2,
    ),
    decoration: BoxDecoration(
      color: AppColors.danger,
      borderRadius: BorderRadius.circular(10),
    ),
    child: Text(
      count > 99 ? '99+' : '$count',
      textAlign: TextAlign.center,
      style: AppTextStyles.body3.copyWith(
        color: AppColors.white,
        fontSize: compact ? 10 : null,
      ),
    ),
  );
}

class _NotificationList extends StatelessWidget {
  final List<Map<String, dynamic>> items;
  final String emptyMessage;
  final Future<void> Function() onRefresh;
  final Future<void> Function(Map<String, dynamic>) onTap;
  final Future<bool> Function(Map<String, dynamic>) onDismissRequest;
  final void Function(Map<String, dynamic>) onDismissed;

  const _NotificationList({
    required this.items,
    required this.emptyMessage,
    required this.onRefresh,
    required this.onTap,
    required this.onDismissRequest,
    required this.onDismissed,
  });

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return AppContentWidth(
        child: LayoutBuilder(
          builder: (context, constraints) => RefreshIndicator(
            color: AppColors.primary,
            onRefresh: onRefresh,
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              children: [
                SizedBox(
                  height: constraints.maxHeight,
                  child: AppMessageView(
                    icon: Icons.notifications_none_rounded,
                    title: emptyMessage,
                    message: 'การแจ้งเตือนใหม่จะแสดงที่หน้านี้',
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    }
    return AppContentWidth(
      child: RefreshIndicator(
        color: AppColors.primary,
        onRefresh: onRefresh,
        child: ListView.separated(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: EdgeInsets.fromLTRB(
            Responsive.horizontalPadding,
            Responsive.dp(12),
            Responsive.horizontalPadding,
            Responsive.dp(28),
          ),
          itemCount: items.length,
          separatorBuilder: (_, _) => SizedBox(height: Responsive.dp(10)),
          itemBuilder: (_, index) {
            final item = items[index];
            return _SwipeActionTile(
              key: ValueKey('notification-${item['id']}'),
              onRemove: () async {
                if (await onDismissRequest(item)) onDismissed(item);
              },
              child: _NotificationTile(item: item, onTap: () => onTap(item)),
            );
          },
        ),
      ),
    );
  }
}

class _NotificationTile extends StatelessWidget {
  final Map<String, dynamic> item;
  final VoidCallback onTap;

  const _NotificationTile({required this.item, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final isUnread = item['is_read'] == 'N';
    final isSystem = item['type'] != 'U';
    return Material(
      color: isUnread
          ? Color.alphaBlend(
              AppColors.primary.withValues(alpha: .11),
              Theme.of(context).colorScheme.surface,
            )
          : Theme.of(context).colorScheme.surface,
      clipBehavior: Clip.antiAlias,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(18),
        side: BorderSide(
          color: isUnread
              ? AppColors.primary.withValues(alpha: .42)
              : Theme.of(context).colorScheme.outlineVariant,
          width: isUnread ? 1.4 : 1,
        ),
      ),
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: EdgeInsets.symmetric(
            horizontal: Responsive.dp(16),
            vertical: Responsive.dp(14),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: isSystem
                      ? Theme.of(context).colorScheme.secondaryContainer
                      : Theme.of(context).colorScheme.surfaceContainer,
                  borderRadius: BorderRadius.circular(13),
                ),
                child: Icon(
                  isSystem
                      ? Icons.notifications_outlined
                      : Icons.person_outline_rounded,
                  color: isSystem
                      ? Theme.of(context).colorScheme.onSecondaryContainer
                      : Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Text(
                            item['title']?.toString() ?? 'การแจ้งเตือน',
                            style:
                                (isUnread
                                        ? AppTextStyles.body2Bold
                                        : AppTextStyles.body2)
                                    .copyWith(
                                      color: isUnread
                                          ? AppColors.primaryDark
                                          : Theme.of(
                                              context,
                                            ).colorScheme.onSurface,
                                    ),
                          ),
                        ),
                        if (isUnread) ...[
                          const SizedBox(width: 8),
                          Container(
                            width: 9,
                            height: 9,
                            margin: const EdgeInsets.only(top: 5),
                            decoration: const BoxDecoration(
                              color: AppColors.primary,
                              shape: BoxShape.circle,
                            ),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      item['body_text']?.toString() ??
                          item['body']?.toString() ??
                          '',
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                      style: AppTextStyles.body3.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      _formatDate(item['created_at']),
                      style: AppTextStyles.body3.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                        fontSize: 11,
                      ),
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

  String _formatDate(dynamic value) {
    final date = DateTime.tryParse(value?.toString() ?? '')?.toLocal();
    if (date == null) return '';
    return formatThaiDateTime(date);
  }
}

class _SwipeActionTile extends StatefulWidget {
  final Widget child;
  final Future<void> Function() onRemove;

  const _SwipeActionTile({
    super.key,
    required this.child,
    required this.onRemove,
  });

  @override
  State<_SwipeActionTile> createState() => _SwipeActionTileState();
}

class _SwipeActionTileState extends State<_SwipeActionTile>
    with SingleTickerProviderStateMixin {
  static const _actionWidth = 88.0;
  late final AnimationController _controller;
  bool _removing = false;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 180),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _onDragUpdate(DragUpdateDetails details) {
    final next = _controller.value - details.delta.dx / _actionWidth;
    _controller.value = next.clamp(0.0, 1.0).toDouble();
  }

  void _onDragEnd(DragEndDetails details) {
    final velocity = details.primaryVelocity ?? 0;
    if (velocity < -250 || _controller.value >= 0.35) {
      _controller.animateTo(1, curve: Curves.easeOut);
    } else {
      _controller.animateBack(0, curve: Curves.easeOut);
    }
  }

  Future<void> _remove() async {
    if (_removing) return;
    setState(() => _removing = true);
    await widget.onRemove();
    if (mounted) setState(() => _removing = false);
  }

  @override
  Widget build(BuildContext context) => ClipRect(
    child: Stack(
      children: [
        Positioned.fill(
          child: Align(
            alignment: Alignment.centerRight,
            child: AnimatedBuilder(
              animation: _controller,
              builder: (context, _) => IgnorePointer(
                ignoring: _controller.value < 0.01,
                child: SizedBox(
                  width: _actionWidth,
                  child: Material(
                    color: _controller.value < 0.01
                        ? Colors.transparent
                        : AppColors.danger,
                    child: InkWell(
                      onTap: _removing ? null : _remove,
                      child: Center(
                        child: _removing
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: AppColors.white,
                                ),
                              )
                            : const Column(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(
                                    Icons.delete_outline_rounded,
                                    color: AppColors.white,
                                  ),
                                  SizedBox(height: 3),
                                  Text(
                                    'นำออก',
                                    style: TextStyle(
                                      color: AppColors.white,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ],
                              ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
        AnimatedBuilder(
          animation: _controller,
          child: widget.child,
          builder: (context, child) => Transform.translate(
            offset: Offset(-_actionWidth * _controller.value, 0),
            child: GestureDetector(
              behavior: HitTestBehavior.translucent,
              onHorizontalDragUpdate: _onDragUpdate,
              onHorizontalDragEnd: _onDragEnd,
              child: child,
            ),
          ),
        ),
      ],
    ),
  );
}

class _ErrorView extends StatelessWidget {
  final String message;
  final Future<void> Function() onRetry;

  const _ErrorView({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) => Center(
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(message, style: AppTextStyles.body1),
        const SizedBox(height: 12),
        OutlinedButton(onPressed: onRetry, child: const Text('ลองใหม่')),
      ],
    ),
  );
}
