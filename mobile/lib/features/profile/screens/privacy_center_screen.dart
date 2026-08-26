import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:share_plus/share_plus.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';

class PrivacyCenterScreen extends StatefulWidget {
  final String token;
  final VoidCallback onAccountDeleted;

  const PrivacyCenterScreen({
    super.key,
    required this.token,
    required this.onAccountDeleted,
  });

  @override
  State<PrivacyCenterScreen> createState() => _PrivacyCenterScreenState();
}

class _PrivacyCenterScreenState extends State<PrivacyCenterScreen> {
  bool _exporting = false;
  bool _deleting = false;

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  Future<void> _exportData() async {
    setState(() => _exporting = true);
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.privacyExport}'),
        headers: _headers,
      );
      if (response.statusCode != 200) throw Exception();

      final date = DateTime.now().toIso8601String().split('T').first;
      await Share.shareXFiles([
        XFile.fromData(
          response.bodyBytes,
          mimeType: 'application/json',
          name: 'personal-data-$date.json',
        ),
      ], text: 'ข้อมูลส่วนบุคคลของฉัน');
    } catch (_) {
      if (mounted) _showMessage('ไม่สามารถดาวน์โหลดข้อมูลได้ กรุณาลองใหม่');
    } finally {
      if (mounted) setState(() => _exporting = false);
    }
  }

  Future<void> _confirmDelete() async {
    final passwordController = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('ลบบัญชีถาวร'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'บัญชีจะถูกปิดใช้งานและคุณจะออกจากระบบทุกอุปกรณ์ กรุณายืนยันด้วยรหัสผ่านปัจจุบัน',
            ),
            const SizedBox(height: 16),
            TextField(
              controller: passwordController,
              obscureText: true,
              autofillHints: const [AutofillHints.password],
              decoration: const InputDecoration(labelText: 'รหัสผ่านปัจจุบัน'),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('ยกเลิก'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: AppColors.danger),
            onPressed: () => Navigator.pop(dialogContext, true),
            child: const Text('ลบบัญชี'),
          ),
        ],
      ),
    );

    if (confirmed == true && passwordController.text.isNotEmpty) {
      await _deleteAccount(passwordController.text);
    }
    passwordController.dispose();
  }

  Future<void> _deleteAccount(String password) async {
    setState(() => _deleting = true);
    try {
      final response = await http.delete(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.privacyAccount}'),
        headers: _headers,
        body: jsonEncode({
          'current_password': password,
          'confirmation': 'DELETE',
        }),
      );
      if (response.statusCode == 422) {
        _showMessage('รหัสผ่านไม่ถูกต้อง');
        return;
      }
      if (response.statusCode != 200) throw Exception();

      widget.onAccountDeleted();
      if (mounted) Navigator.of(context).popUntil((route) => route.isFirst);
    } catch (_) {
      if (mounted) _showMessage('ไม่สามารถลบบัญชีได้ กรุณาลองใหม่');
    } finally {
      if (mounted) setState(() => _deleting = false);
    }
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('ความเป็นส่วนตัว', style: AppTextStyles.h4),
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, thickness: 1, color: AppColors.border),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text('ข้อมูลของคุณ', style: AppTextStyles.h4),
          const SizedBox(height: 8),
          Text(
            'คุณสามารถดาวน์โหลดสำเนาโปรไฟล์และประวัติสุขภาพที่บันทึกไว้ในระบบ',
            style: AppTextStyles.body2.copyWith(color: AppColors.textSecondary),
          ),
          const SizedBox(height: 16),
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: const Icon(Icons.download_rounded),
            title: const Text('ดาวน์โหลดข้อมูลส่วนบุคคล'),
            subtitle: const Text('ไฟล์ JSON สามารถเก็บหรือส่งต่อได้'),
            trailing: _exporting
                ? const SizedBox.square(
                    dimension: 22,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.chevron_right_rounded),
            onTap: _exporting ? null : _exportData,
          ),
          const Divider(height: 40),
          Text(
            'ลบบัญชี',
            style: AppTextStyles.h4.copyWith(color: AppColors.danger),
          ),
          const SizedBox(height: 8),
          Text(
            'ก่อนลบบัญชี แนะนำให้ดาวน์โหลดข้อมูลของคุณไว้ก่อน การดำเนินการนี้จะออกจากระบบทุกอุปกรณ์',
            style: AppTextStyles.body2.copyWith(color: AppColors.textSecondary),
          ),
          const SizedBox(height: 16),
          OutlinedButton.icon(
            style: OutlinedButton.styleFrom(foregroundColor: AppColors.danger),
            onPressed: _deleting ? null : _confirmDelete,
            icon: _deleting
                ? const SizedBox.square(
                    dimension: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.delete_outline_rounded),
            label: const Text('ลบบัญชีของฉัน'),
          ),
        ],
      ),
    );
  }
}
