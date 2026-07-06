import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class NotificationScreen extends StatefulWidget {
  final String token;
  const NotificationScreen({super.key, required this.token});

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen> {
  List<dynamic> _items = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  Future<void> _load() async {
    setState(() => _isLoading = true);
    final res = await http.get(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.notifications}'),
      headers: _headers,
    );
    if (res.statusCode == 200) {
      setState(() => _items = jsonDecode(res.body)['data'] ?? []);
    }
    setState(() => _isLoading = false);
  }

  Future<void> _markAllRead() async {
    await http.post(
      Uri.parse(
        '${ApiConstants.baseUrl}${ApiConstants.notifications}/mark-all-read',
      ),
      headers: _headers,
    );
    _load();
  }

  Future<void> _markRead(dynamic item) async {
    if (item['is_read'] == 'Y') return;
    await http.post(
      Uri.parse(
        '${ApiConstants.baseUrl}${ApiConstants.notifications}/${item['id']}/read',
      ),
      headers: _headers,
    );
    _load();
  }

  @override
  Widget build(BuildContext context) {
    final unreadCount = _items.where((i) => i['is_read'] == 'N').length;

    return Scaffold(
      appBar: AppBar(
        title: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('การแจ้งเตือน'),
            if (unreadCount > 0) ...[
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: AppColors.danger,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '$unreadCount',
                  style: AppTextStyles.body3.copyWith(color: Colors.white),
                ),
              ),
            ],
          ],
        ),
        actions: [
          if (unreadCount > 0)
            TextButton(
              onPressed: _markAllRead,
              child: const Text('อ่านทั้งหมด'),
            ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _items.isEmpty
          ? const Center(child: Text('ไม่มีการแจ้งเตือน'))
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView.builder(
                padding: const EdgeInsets.symmetric(vertical: 8),
                itemCount: _items.length,
                itemBuilder: (_, i) => _NotificationTile(
                  item: _items[i],
                  onTap: () => _markRead(_items[i]),
                ),
              ),
            ),
    );
  }
}

class _NotificationTile extends StatelessWidget {
  final dynamic item;
  final VoidCallback onTap;
  const _NotificationTile({required this.item, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final isUnread = item['is_read'] == 'N';
    final isSystem = item['type'] == 'S';

    return InkWell(
      onTap: onTap,
      child: Container(
        color: isUnread
            ? AppColors.primaryLight.withOpacity(0.5)
            : Colors.transparent,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CircleAvatar(
              backgroundColor: isSystem
                  ? AppColors.primaryLight
                  : AppColors.surface,
              radius: 20,
              child: Icon(
                isSystem ? Icons.notifications_outlined : Icons.person_outline,
                color: isSystem ? AppColors.primary : AppColors.textSecondary,
                size: 20,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          item['title'],
                          style: AppTextStyles.body2.copyWith(
                            fontWeight: isUnread
                                ? FontWeight.w700
                                : FontWeight.w400,
                            color: AppColors.textPrimary,
                          ),
                        ),
                      ),
                      if (isUnread)
                        Container(
                          width: 8,
                          height: 8,
                          decoration: const BoxDecoration(
                            color: AppColors.primary,
                            shape: BoxShape.circle,
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    item['body'],
                    style: AppTextStyles.body3,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    item['created_at'] ?? '',
                    style: AppTextStyles.body3.copyWith(fontSize: 11),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
