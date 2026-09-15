import 'dart:convert';

import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:checkup/data/services/central_http_client.dart' as http;

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_layout.dart';

class AccountActivityScreen extends StatefulWidget {
  const AccountActivityScreen({super.key, required this.token});

  final String token;

  @override
  State<AccountActivityScreen> createState() => _AccountActivityScreenState();
}

class _AccountActivityScreenState extends State<AccountActivityScreen> {
  bool _loading = true;
  String? _error;
  List<Map<String, dynamic>> _items = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final response = await http.get(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.accountActivities}?per_page=50',
        ),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer ${widget.token}',
        },
      );
      if (response.statusCode != 200) throw Exception();
      final data = jsonDecode(response.body)['data'] as List<dynamic>;
      if (mounted) setState(() => _items = data.cast<Map<String, dynamic>>());
    } catch (_) {
      if (mounted) setState(() => _error = 'โหลดประวัติไม่สำเร็จ กรุณาลองใหม่');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  ({String label, IconData icon, Color color}) _eventInfo(String? event) =>
      switch (event) {
        'account_registered' => (
          label: 'สร้างบัญชี',
          icon: Icons.person_add_outlined,
          color: AppColors.success,
        ),
        'login' => (
          label: 'เข้าสู่ระบบ',
          icon: Icons.login_rounded,
          color: AppColors.primary,
        ),
        'logout' => (
          label: 'ออกจากระบบ',
          icon: Icons.logout_rounded,
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
        'password_changed' => (
          label: 'เปลี่ยนรหัสผ่าน',
          icon: Icons.password_rounded,
          color: AppColors.warning,
        ),
        'personal_data_exported' => (
          label: 'ดาวน์โหลดข้อมูลส่วนบุคคล',
          icon: Icons.download_outlined,
          color: AppColors.primary,
        ),
        'session_revoked' => (
          label: 'ออกจากระบบอุปกรณ์',
          icon: Icons.phonelink_erase_rounded,
          color: AppColors.danger,
        ),
        'other_sessions_revoked' => (
          label: 'ออกจากระบบอุปกรณ์อื่นทั้งหมด',
          icon: Icons.devices_other_rounded,
          color: AppColors.danger,
        ),
        _ => (
          label: 'กิจกรรมบัญชี',
          icon: Icons.history_rounded,
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
      };

  String _dateLabel(dynamic value) {
    final date = DateTime.tryParse(value?.toString() ?? '')?.toLocal();
    if (date == null) return '-';
    return formatThaiDateTime(date);
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: Theme.of(context).scaffoldBackgroundColor,
    appBar: AppBar(
      title: Text('ประวัติการใช้งานบัญชี', style: AppTextStyles.h4),
      bottom: PreferredSize(
        preferredSize: Size.fromHeight(1),
        child: Divider(
          height: 1,
          thickness: 1,
          color: Theme.of(context).colorScheme.outlineVariant,
        ),
      ),
    ),
    body: ResponsiveBuilder(
      builder: (context) => AppContentWidth(
        child: _loading
            ? const AppLoadingView()
            : _error != null
            ? AppMessageView.error(message: _error!, onAction: _load)
            : RefreshIndicator(
                onRefresh: _load,
                child: _items.isEmpty
                    ? ListView(
                        children: const [
                          SizedBox(
                            height: 440,
                            child: AppMessageView.empty(
                              title: 'ยังไม่มีประวัติการใช้งาน',
                              message:
                                  'กิจกรรมด้านความปลอดภัยและการเข้าสู่ระบบจะแสดงที่นี่',
                            ),
                          ),
                        ],
                      )
                    : ListView.separated(
                        padding: EdgeInsets.fromLTRB(
                          Responsive.horizontalPadding,
                          20,
                          Responsive.horizontalPadding,
                          32,
                        ),
                        itemCount: _items.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 8),
                        itemBuilder: (context, index) {
                          final item = _items[index];
                          final info = _eventInfo(item['event']?.toString());
                          final device = item['device_name']?.toString().trim();
                          final ip = item['ip_address']?.toString().trim();
                          return AppPanel(
                            padding: EdgeInsets.zero,
                            child: ListTile(
                              leading: CircleAvatar(
                                backgroundColor: info.color.withValues(
                                  alpha: 0.12,
                                ),
                                child: Icon(info.icon, color: info.color),
                              ),
                              title: Text(info.label),
                              subtitle: Text(
                                [
                                  _dateLabel(item['created_at']),
                                  if (device?.isNotEmpty == true) device!,
                                  if (ip?.isNotEmpty == true) 'IP $ip',
                                ].join(' · '),
                              ),
                            ),
                          );
                        },
                      ),
              ),
      ),
    ),
  );
}
