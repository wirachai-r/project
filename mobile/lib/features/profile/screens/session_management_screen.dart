import 'dart:convert';

import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:mobile/data/services/central_http_client.dart' as http;

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../shared/widgets/app_layout.dart';
import '../../../shared/widgets/app_button.dart';

class SessionManagementScreen extends StatefulWidget {
  final String token;
  final VoidCallback onCurrentSessionRevoked;

  const SessionManagementScreen({
    super.key,
    required this.token,
    required this.onCurrentSessionRevoked,
  });

  @override
  State<SessionManagementScreen> createState() =>
      _SessionManagementScreenState();
}

class _SessionManagementScreenState extends State<SessionManagementScreen> {
  List<Map<String, dynamic>> _sessions = [];
  bool _loading = true;
  bool _revokingOthers = false;

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (mounted) setState(() => _loading = true);
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.sessions}'),
        headers: _headers,
      );
      if (response.statusCode != 200) throw Exception();
      final body = jsonDecode(utf8.decode(response.bodyBytes));
      if (mounted) {
        setState(() {
          _sessions = List<Map<String, dynamic>>.from(body['data'] ?? []);
        });
      }
    } catch (_) {
      if (mounted) _message('ไม่สามารถโหลดรายการอุปกรณ์ได้');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _revoke(Map<String, dynamic> session) async {
    final isCurrent = session['is_current'] == true;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(isCurrent ? 'ออกจากระบบอุปกรณ์นี้?' : 'นำอุปกรณ์ออก?'),
        content: Text(
          isCurrent
              ? 'คุณจะต้องเข้าสู่ระบบใหม่บนอุปกรณ์นี้'
              : 'อุปกรณ์นี้จะไม่สามารถเข้าถึงบัญชีได้อีก',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('ยกเลิก'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('ยืนยัน'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    final response = await http.delete(
      Uri.parse(
        '${ApiConstants.baseUrl}${ApiConstants.session(session['id'])}',
      ),
      headers: _headers,
    );
    if (response.statusCode != 200) {
      _message('ไม่สามารถนำอุปกรณ์ออกได้');
      return;
    }
    if (isCurrent) {
      widget.onCurrentSessionRevoked();
      if (mounted) Navigator.of(context).popUntil((route) => route.isFirst);
      return;
    }
    await _load();
  }

  Future<void> _revokeOthers() async {
    setState(() => _revokingOthers = true);
    try {
      final response = await http.delete(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.sessionsOthers}'),
        headers: _headers,
      );
      if (response.statusCode != 200) throw Exception();
      _message('ออกจากระบบอุปกรณ์อื่นทั้งหมดแล้ว');
      await _load();
    } catch (_) {
      if (mounted) _message('ไม่สามารถออกจากระบบอุปกรณ์อื่นได้');
    } finally {
      if (mounted) setState(() => _revokingOthers = false);
    }
  }

  void _message(String message) {
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }

  IconData _icon(String? type) => switch (type) {
    'android' || 'ios' => Icons.smartphone_rounded,
    'web' => Icons.language_rounded,
    _ => Icons.computer_rounded,
  };

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text('อุปกรณ์และการเข้าสู่ระบบ', style: AppTextStyles.h4),
        bottom: PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(
            height: 1,
            thickness: 1,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: _loading
          ? const AppLoadingView()
          : RefreshIndicator(
              onRefresh: _load,
              child: AppContentWidth(
                child: ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.fromLTRB(16, 20, 16, 32),
                  children: [
                    Text('อุปกรณ์ที่เข้าสู่ระบบ', style: AppTextStyles.h4),
                    const SizedBox(height: 8),
                    Text(
                      'หากพบอุปกรณ์ที่ไม่รู้จัก ให้นำอุปกรณ์ออกและเปลี่ยนรหัสผ่าน',
                      style: AppTextStyles.body2.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                        height: 1.5,
                      ),
                    ),
                    const SizedBox(height: 16),
                    if (_sessions.isEmpty)
                      const SizedBox(
                        height: 260,
                        child: AppMessageView.empty(
                          title: 'ไม่พบอุปกรณ์ที่เข้าสู่ระบบ',
                          message:
                              'เมื่อมีอุปกรณ์ที่ใช้งาน รายการจะแสดงที่หน้านี้',
                        ),
                      ),
                    ..._sessions.map(
                      (session) => AppPanel(
                        padding: EdgeInsets.zero,
                        margin: const EdgeInsets.only(bottom: 10),
                        child: ListTile(
                          leading: Icon(
                            _icon(session['device_type'] as String?),
                          ),
                          title: Text(session['device_name'] as String),
                          subtitle: Text(
                            [
                              if (session['is_current'] == true) 'อุปกรณ์นี้',
                              if (session['ip_address'] != null)
                                'IP ${session['ip_address']}',
                            ].join(' • '),
                          ),
                          trailing: IconButton(
                            tooltip: 'ออกจากระบบอุปกรณ์นี้',
                            onPressed: () => _revoke(session),
                            icon: const Icon(Icons.logout_rounded),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),
                    AppButton(
                      label: 'ออกจากระบบอุปกรณ์อื่นทั้งหมด',
                      outlined: true,
                      backgroundColor: AppColors.danger,
                      loading: _revokingOthers,
                      onTap: _sessions.length <= 1 || _revokingOthers
                          ? null
                          : _revokeOthers,
                      icon: const Icon(Icons.phonelink_erase_rounded),
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}
