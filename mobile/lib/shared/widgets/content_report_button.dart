import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';

import '../../core/constants/api_constants.dart';
import '../../features/auth/providers/auth_provider.dart';

class ContentReportButton extends StatelessWidget {
  const ContentReportButton({
    super.key,
    required this.targetType,
    required this.targetId,
  });

  final String targetType;
  final String targetId;

  @override
  Widget build(BuildContext context) => IconButton(
    tooltip: 'รายงานข้อมูลผิด',
    icon: const Icon(Icons.flag_outlined),
    onPressed: () => _open(context),
  );

  Future<void> _open(BuildContext context) async {
    final token = context.read<AuthProvider>().token;
    if (token == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('กรุณาเข้าสู่ระบบก่อนส่งรายงาน')),
      );
      return;
    }
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (_) => _ContentReportForm(
        token: token,
        targetType: targetType,
        targetId: targetId,
      ),
    );
    if (result == true && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('ส่งรายงานให้ทีมตรวจสอบแล้ว')),
      );
    }
  }
}

class _ContentReportForm extends StatefulWidget {
  const _ContentReportForm({
    required this.token,
    required this.targetType,
    required this.targetId,
  });
  final String token;
  final String targetType;
  final String targetId;

  @override
  State<_ContentReportForm> createState() => _ContentReportFormState();
}

class _ContentReportFormState extends State<_ContentReportForm> {
  final _controller = TextEditingController();
  String _category = 'inaccurate';
  bool _saving = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_controller.text.trim().length < 5) return;
    setState(() => _saving = true);
    final response = await http.post(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.feedback}'),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ${widget.token}',
      },
      body: jsonEncode({
        'feedback_type': 'content_error',
        'target_type': widget.targetType,
        'target_id': widget.targetId,
        'category': _category,
        'message': _controller.text.trim(),
      }),
    );
    if (!mounted) return;
    setState(() => _saving = false);
    if (response.statusCode == 201) {
      Navigator.pop(context, true);
    } else {
      final duplicate = response.statusCode == 409;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            duplicate
                ? 'ข้อมูลนี้มีรายงานที่กำลังตรวจสอบอยู่แล้ว'
                : 'ส่งรายงานไม่สำเร็จ กรุณาลองใหม่',
          ),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) => SafeArea(
    child: Padding(
      padding: EdgeInsets.fromLTRB(
        20,
        0,
        20,
        MediaQuery.viewInsetsOf(context).bottom + 20,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'รายงานข้อมูลผิด',
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            initialValue: _category,
            decoration: const InputDecoration(
              labelText: 'ประเภทปัญหา',
              border: OutlineInputBorder(),
            ),
            items: const [
              DropdownMenuItem(
                value: 'inaccurate',
                child: Text('ข้อมูลไม่ถูกต้อง'),
              ),
              DropdownMenuItem(value: 'outdated', child: Text('ข้อมูลล้าสมัย')),
              DropdownMenuItem(
                value: 'unclear',
                child: Text('ข้อมูลไม่ชัดเจน'),
              ),
              DropdownMenuItem(
                value: 'unsafe',
                child: Text('ข้อมูลอาจไม่ปลอดภัย'),
              ),
              DropdownMenuItem(value: 'other', child: Text('อื่น ๆ')),
            ],
            onChanged: (value) =>
                setState(() => _category = value ?? _category),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _controller,
            minLines: 3,
            maxLines: 6,
            maxLength: 2000,
            decoration: const InputDecoration(
              labelText: 'รายละเอียด (อย่างน้อย 5 ตัวอักษร)',
              border: OutlineInputBorder(),
            ),
          ),
          FilledButton(
            onPressed: _saving ? null : _submit,
            child: Text(_saving ? 'กำลังส่ง...' : 'ส่งให้ทีมตรวจสอบ'),
          ),
        ],
      ),
    ),
  );
}
