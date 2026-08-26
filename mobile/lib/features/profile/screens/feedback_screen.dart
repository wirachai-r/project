import 'dart:convert';

import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:http/http.dart' as http;

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/thai_date_formatter.dart';

class FeedbackScreen extends StatefulWidget {
  const FeedbackScreen({super.key, required this.token});

  final String token;

  @override
  State<FeedbackScreen> createState() => _FeedbackScreenState();
}

class _FeedbackScreenState extends State<FeedbackScreen> {
  final _formKey = GlobalKey<FormState>();
  final _messageController = TextEditingController();
  bool _submitting = false;
  bool _loading = true;
  List<Map<String, dynamic>> _items = [];

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.feedback}'),
        headers: _headers,
      );
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body)['data'] as List<dynamic>;
        if (mounted) {
          setState(() => _items = data.cast<Map<String, dynamic>>());
        }
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);
    try {
      final response = await http.post(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.feedback}'),
        headers: _headers,
        body: jsonEncode({
          'feedback_type': 'general',
          'message': _messageController.text.trim(),
        }),
      );
      if (!mounted) return;
      if (response.statusCode == 201) {
        _messageController.clear();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('ส่งความคิดเห็นเรียบร้อยแล้ว ขอบคุณครับ'),
          ),
        );
        await _load();
      } else {
        throw Exception();
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('ส่งความคิดเห็นไม่สำเร็จ กรุณาลองใหม่')),
        );
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  String _statusLabel(String? status) => switch (status) {
    'in_review' => 'กำลังตรวจสอบ',
    'resolved' => 'ดำเนินการแล้ว',
    'dismissed' => 'ปิดรายงาน',
    _ => 'รอตรวจสอบ',
  };

  Color _statusColor(String? status) => switch (status) {
    'resolved' => AppColors.success,
    'dismissed' => AppColors.textSecondary,
    'in_review' => AppColors.primary,
    _ => AppColors.warning,
  };

  String _submittedDate(dynamic value) {
    final date = DateTime.tryParse(value?.toString() ?? '')?.toLocal();
    return date == null ? '' : formatThaiDate(date);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('ความคิดเห็นและรายงานข้อมูลผิด', style: AppTextStyles.h4),
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, thickness: 1, color: AppColors.border),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: AppColors.primaryLight.withValues(alpha: 0.55),
                borderRadius: BorderRadius.circular(18),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: AppColors.white,
                      borderRadius: BorderRadius.circular(13),
                    ),
                    child: const Icon(
                      Icons.forum_outlined,
                      color: AppColors.primary,
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('ช่วยให้เราปรับปรุงแอป', style: AppTextStyles.h4),
                        const SizedBox(height: 4),
                        Text(
                          'แจ้งสิ่งที่พบหรือเสนอสิ่งที่อยากให้ปรับปรุง ทีมงานจะนำไปตรวจสอบ',
                          style: AppTextStyles.body2.copyWith(
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.surfaceElevated,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: AppColors.border),
              ),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('ส่งความคิดเห็น', style: AppTextStyles.body1Bold),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _messageController,
                      minLines: 4,
                      maxLines: 8,
                      maxLength: 2000,
                      decoration: const InputDecoration(
                        hintText: 'บอกสิ่งที่พบหรือสิ่งที่อยากให้ปรับปรุง',
                        alignLabelWithHint: true,
                      ),
                      validator: (value) => (value?.trim().length ?? 0) < 5
                          ? 'กรุณากรอกอย่างน้อย 5 ตัวอักษร'
                          : null,
                    ),
                    const SizedBox(height: 4),
                    FilledButton.icon(
                      onPressed: _submitting ? null : _submit,
                      style: FilledButton.styleFrom(
                        minimumSize: const Size.fromHeight(50),
                      ),
                      icon: _submitting
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: AppColors.white,
                              ),
                            )
                          : const Icon(Icons.send_rounded, size: 19),
                      label: Text(
                        _submitting ? 'กำลังส่ง...' : 'ส่งความคิดเห็น',
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 10),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(
                  Icons.info_outline_rounded,
                  size: 18,
                  color: AppColors.textSecondary,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'ช่องทางนี้ไม่เหมาะสำหรับเหตุฉุกเฉินทางการแพทย์',
                    style: AppTextStyles.body3.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 30),
            Text('ประวัติที่ส่ง', style: AppTextStyles.h4),
            const SizedBox(height: 12),
            if (_loading)
              const AppLoadingView()
            else if (_items.isEmpty)
              const _EmptyHistory()
            else
              ..._items.map(
                (item) => _FeedbackHistoryCard(
                  message: item['message']?.toString() ?? '-',
                  status: _statusLabel(item['status']?.toString()),
                  statusColor: _statusColor(item['status']?.toString()),
                  submittedDate: _submittedDate(item['created_at']),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _FeedbackHistoryCard extends StatelessWidget {
  final String message;
  final String status;
  final Color statusColor;
  final String submittedDate;

  const _FeedbackHistoryCard({
    required this.message,
    required this.status,
    required this.statusColor,
    required this.submittedDate,
  });

  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.only(bottom: 10),
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: AppColors.surfaceElevated,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: AppColors.border),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(message, style: AppTextStyles.body1, maxLines: 4),
        const SizedBox(height: 12),
        Row(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: statusColor.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 7,
                    height: 7,
                    decoration: BoxDecoration(
                      color: statusColor,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    status,
                    style: AppTextStyles.body3Bold.copyWith(color: statusColor),
                  ),
                ],
              ),
            ),
            const Spacer(),
            if (submittedDate.isNotEmpty)
              Text(
                submittedDate,
                style: AppTextStyles.body3.copyWith(
                  color: AppColors.textSecondary,
                ),
              ),
          ],
        ),
      ],
    ),
  );
}

class _EmptyHistory extends StatelessWidget {
  const _EmptyHistory();

  @override
  Widget build(BuildContext context) => Container(
    width: double.infinity,
    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 30),
    decoration: BoxDecoration(
      color: AppColors.surfaceElevated,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: AppColors.border),
    ),
    child: Column(
      children: [
        const Icon(
          Icons.mark_chat_unread_outlined,
          size: 38,
          color: AppColors.textHint,
        ),
        const SizedBox(height: 10),
        Text(
          'ยังไม่มีความคิดเห็นที่ส่ง',
          style: AppTextStyles.body2.copyWith(color: AppColors.textSecondary),
        ),
      ],
    ),
  );
}
