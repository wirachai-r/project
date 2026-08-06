import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';

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

  int get _unreadCount =>
      _items.where((item) => item['is_read'] == 'N').length;

  int _unreadIn(List<Map<String, dynamic>> items) =>
      items.where((item) => item['is_read'] == 'N').length;

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    final systemItems = _systemItems;
    final personalItems = _personalItems;

    return Scaffold(
      backgroundColor: AppColors.surface,
      appBar: AppBar(
        backgroundColor: AppColors.white,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        title: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('การแจ้งเตือน', style: AppTextStyles.h4),
            if (_unreadCount > 0) ...[
              const SizedBox(width: 7),
              _CountBadge(count: _unreadCount),
            ],
          ],
        ),
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
          preferredSize: const Size.fromHeight(49),
          child: Column(
            children: [
              const Divider(height: 1, thickness: 1, color: AppColors.border),
              TabBar(
                controller: _tabController,
                labelColor: AppColors.primary,
                unselectedLabelColor: AppColors.textSecondary,
                labelStyle: AppTextStyles.body2Bold,
                unselectedLabelStyle: AppTextStyles.body2,
                indicatorColor: AppColors.primary,
                indicatorWeight: 3,
                tabs: [
                  _NotificationTab(label: 'ทั้งหมด', unread: _unreadCount),
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
    if (_isLoading) return const Center(child: CircularProgressIndicator());
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
          onTap: _markRead,
        ),
        _NotificationList(
          items: systemItems,
          emptyMessage: 'ยังไม่มีการแจ้งเตือนจากระบบ',
          onRefresh: _load,
          onTap: _markRead,
        ),
        _NotificationList(
          items: personalItems,
          emptyMessage: 'ยังไม่มีการแจ้งเตือนส่วนตัว',
          onRefresh: _load,
          onTap: _markRead,
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

  const _NotificationList({
    required this.items,
    required this.emptyMessage,
    required this.onRefresh,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return RefreshIndicator(
        onRefresh: onRefresh,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            SizedBox(height: MediaQuery.sizeOf(context).height * .22),
            const Icon(
              Icons.notifications_none_rounded,
              size: 56,
              color: AppColors.textHint,
            ),
            const SizedBox(height: 12),
            Text(
              emptyMessage,
              textAlign: TextAlign.center,
              style: AppTextStyles.body1.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
      );
    }
    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: onRefresh,
      child: ListView.separated(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.symmetric(vertical: Responsive.dp(10)),
        itemCount: items.length,
        separatorBuilder: (_, __) => const Divider(
          height: 1,
          thickness: 1,
          indent: 72,
          color: AppColors.border,
        ),
        itemBuilder: (_, index) => _NotificationTile(
          item: items[index],
          onTap: () => onTap(items[index]),
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
          ? AppColors.primaryLight.withValues(alpha: .5)
          : AppColors.white,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: EdgeInsets.symmetric(
            horizontal: Responsive.horizontalPadding,
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
                      ? AppColors.primaryLight
                      : AppColors.surface,
                  borderRadius: BorderRadius.circular(13),
                ),
                child: Icon(
                  isSystem
                      ? Icons.notifications_outlined
                      : Icons.person_outline_rounded,
                  color: isSystem
                      ? AppColors.primary
                      : AppColors.textSecondary,
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
                            style: (isUnread
                                    ? AppTextStyles.body2Bold
                                    : AppTextStyles.body2)
                                .copyWith(color: AppColors.textPrimary),
                          ),
                        ),
                        if (isUnread) ...[
                          const SizedBox(width: 8),
                          Container(
                            width: 8,
                            height: 8,
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
                      item['body']?.toString() ?? '',
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                      style: AppTextStyles.body3.copyWith(
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      _formatDate(item['created_at']),
                      style: AppTextStyles.body3.copyWith(
                        color: AppColors.textHint,
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
    return DateFormat('dd/MM/yyyy HH:mm').format(date);
  }
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
