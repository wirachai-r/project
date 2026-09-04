import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:mobile/data/services/central_http_client.dart' as http;
import 'package:provider/provider.dart';

import '../../core/constants/api_constants.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../features/auth/providers/auth_provider.dart';
import 'app_button.dart';
import 'app_feedback.dart';
import 'app_layout.dart';
import 'app_text_field.dart';

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
    final auth = context.read<AuthProvider>();
    final token = auth.token;
    if (!auth.isAuthenticated || token == null || token.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('กรุณาเข้าสู่ระบบก่อนส่งรายงาน')),
      );
      return;
    }
    final category = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      builder: (sheetContext) => SafeArea(
        child: AppContentWidth(
          shrinkWrapHeight: true,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('รายงานข้อมูลผิด', style: AppTextStyles.h3),
                const SizedBox(height: 4),
                Text(
                  'เลือกหัวข้อที่ตรงกับสิ่งที่คุณพบมากที่สุด',
                  style: AppTextStyles.body2.copyWith(
                    color: AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 12),
                for (final item in _categories.entries)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Material(
                      color: AppColors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                        side: const BorderSide(color: AppColors.border),
                      ),
                      clipBehavior: Clip.antiAlias,
                      child: ListTile(
                        minTileHeight: 54,
                        leading: const Icon(
                          Icons.flag_outlined,
                          color: AppColors.primary,
                        ),
                        title: Text(item.value, style: AppTextStyles.body1Bold),
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
      showAppSuccess(context, 'ส่งรายงานให้ทีมตรวจสอบแล้ว');
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
    try {
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
      if (response.statusCode == 201) {
        Navigator.pop(context, true);
        return;
      }
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
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('ไม่สามารถเชื่อมต่อได้ กรุณาลองใหม่อีกครั้ง'),
        ),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) => SafeArea(
    child: AppContentWidth(
      shrinkWrapHeight: true,
      child: SingleChildScrollView(
        padding: EdgeInsets.fromLTRB(
          16,
          0,
          16,
          MediaQuery.viewInsetsOf(context).bottom + 20,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('รายงานข้อมูลผิด', style: AppTextStyles.h3),
            const SizedBox(height: 6),
            Text(
              'หัวข้อที่เลือก: ${widget.categoryLabel}',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: 16),
            AppTextField(
              label: 'รายละเอียดเพิ่มเติม',
              hint: 'อธิบายข้อมูลที่พบอย่างน้อย 5 ตัวอักษร',
              controller: _controller,
              maxLines: 8,
              onChanged: (_) => setState(() {}),
            ),
            const SizedBox(height: 16),
            AppButton(
              label: 'ส่งให้ทีมตรวจสอบ',
              loading: _saving,
              icon: const Icon(Icons.send_rounded),
              onTap: _saving || _controller.text.trim().length < 5
                  ? null
                  : _submit,
            ),
          ],
        ),
      ),
    ),
  );
}
