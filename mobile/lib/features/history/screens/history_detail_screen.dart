import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class HistoryDetailScreen extends StatefulWidget {
  final int assessmentId;
  final String token;
  const HistoryDetailScreen({
    super.key,
    required this.assessmentId,
    required this.token,
  });

  @override
  State<HistoryDetailScreen> createState() => _HistoryDetailScreenState();
}

class _HistoryDetailScreenState extends State<HistoryDetailScreen> {
  Map<String, dynamic>? _assessment;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await http.get(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.assessments}/${widget.assessmentId}',
        ),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer ${widget.token}',
        },
      );
      if (res.statusCode == 200) {
        setState(() => _assessment = jsonDecode(res.body)['data']);
      } else {
        setState(() => _error = 'ไม่พบข้อมูล');
      }
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('รายละเอียดการประเมิน')),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(child: Text(_error!))
          : _buildContent(),
    );
  }

  Widget _buildContent() {
    final a = _assessment!;
    final results = a['results'] as List? ?? [];
    final answers = a['answers'] as List? ?? [];

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Summary card
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(
                        Icons.calendar_today_outlined,
                        size: 16,
                        color: AppColors.textSecondary,
                      ),
                      const SizedBox(width: 6),
                      Text(a['created_at'] ?? '', style: AppTextStyles.body3),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'อาการ: ${a['symptom']?['symptom_name'] ?? '-'}',
                    style: AppTextStyles.body1.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Results
          if (results.isNotEmpty) ...[
            Text('ผลการวินิจฉัย', style: AppTextStyles.h3),
            const SizedBox(height: 8),
            ...results.map((r) {
              final color = AppColors.urgencyColor(r['urgency_level'] ?? 'W');
              return Card(
                margin: const EdgeInsets.only(bottom: 8),
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Row(
                    children: [
                      Container(
                        width: 4,
                        height: 40,
                        decoration: BoxDecoration(
                          color: color,
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              r['disease']?['disease_name'] ?? '-',
                              style: AppTextStyles.body1.copyWith(
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            Text(
                              AppColors.urgencyLabel(r['urgency_level'] ?? 'W'),
                              style: AppTextStyles.body3.copyWith(color: color),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }),
            const SizedBox(height: 16),
          ],

          // Answers summary
          if (answers.isNotEmpty) ...[
            Text('คำตอบที่เลือก', style: AppTextStyles.h3),
            const SizedBox(height: 8),
            ...answers.map(
              (ans) => Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(
                      Icons.check_circle_outline,
                      size: 16,
                      color: AppColors.success,
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        ans['choice']?['choice_text'] ?? '-',
                        style: AppTextStyles.body2,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
