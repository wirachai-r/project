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

  static const _categories = {
    'inaccurate': 'ข้อมูลไม่ถูกต้อง',
    'outdated': 'ข้อมูลล้าสมัย',
    'unclear': 'ข้อมูลไม่ชัดเจน',
    'unsafe': 'ข้อมูลอาจไม่ปลอดภัย',
    'other': 'อื่น ๆ',
  };

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
    final category = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      builder: (sheetContext) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 4, 20, 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'รายงานข้อมูลผิด',
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: 12),
              for (final item in _categories.entries)
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(item.value),
                  onTap: () => Navigator.pop(sheetContext, item.key),
                ),
            ],
          ),
        ),
      ),
    );
    if (category == null || !context.mounted) return;

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      showDragHandle: true,
      builder: (_) => _ContentReportForm(
        token: token,
        targetType: targetType,
        targetId: targetId,
        category: category,
        categoryLabel: _categories[category]!,
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
    required this.category,
    required this.categoryLabel,
  });
  final String token;
  final String targetType;
  final String targetId;
  final String category;
  final String categoryLabel;

  @override
  State<_ContentReportForm> createState() => _ContentReportFormState();
}

class _ContentReportFormState extends State<_ContentReportForm> {
  final _controller = TextEditingController();
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
        'category': widget.category,
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
          const SizedBox(height: 6),
          Text(
            'หัวข้อที่เลือก: ${widget.categoryLabel}',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: 16),
          const Text('รายละเอียดเพิ่มเติม'),
          const SizedBox(height: 8),
          TextField(
            controller: _controller,
            autofocus: true,
            minLines: 4,
            maxLines: 8,
            onChanged: (_) => setState(() {}),
            decoration: const InputDecoration(
              hintText: 'อธิบายข้อมูลที่พบอย่างน้อย 5 ตัวอักษร',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: _saving || _controller.text.trim().length < 5
                ? null
                : _submit,
            style: FilledButton.styleFrom(
              minimumSize: const Size.fromHeight(52),
            ),
            child: Text(_saving ? 'กำลังส่ง...' : 'ส่งให้ทีมตรวจสอบ'),
          ),
        ],
      ),
    ),
  );
}
