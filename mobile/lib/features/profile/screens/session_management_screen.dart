import 'dart:convert';

import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:http/http.dart' as http;

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';

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
      appBar: AppBar(
        title: Text('อุปกรณ์และการเข้าสู่ระบบ', style: AppTextStyles.h4),
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, thickness: 1, color: AppColors.border),
        ),
      ),
      body: _loading
          ? const AppLoadingView()
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(20),
                children: [
                  const Text(
                    'อุปกรณ์ที่เข้าสู่ระบบ',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'หากพบอุปกรณ์ที่ไม่รู้จัก ให้นำอุปกรณ์ออกและเปลี่ยนรหัสผ่าน',
                  ),
                  const SizedBox(height: 16),
                  if (_sessions.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 32),
                      child: Center(child: Text('ไม่พบ session ที่ใช้งานอยู่')),
                    ),
                  ..._sessions.map(
                    (session) => Card(
                      child: ListTile(
                        leading: Icon(_icon(session['device_type'] as String?)),
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
                  OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.danger,
                    ),
                    onPressed: _sessions.length <= 1 || _revokingOthers
                        ? null
                        : _revokeOthers,
                    icon: _revokingOthers
                        ? const SizedBox.square(
                            dimension: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.phonelink_erase_rounded),
                    label: const Text('ออกจากระบบอุปกรณ์อื่นทั้งหมด'),
                  ),
                ],
              ),
            ),
    );
  }
}
