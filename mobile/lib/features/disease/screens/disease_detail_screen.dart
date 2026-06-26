import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class DiseaseDetailScreen extends StatefulWidget {
  final String diseaseId;
  const DiseaseDetailScreen({super.key, required this.diseaseId});

  @override
  State<DiseaseDetailScreen> createState() => _DiseaseDetailScreenState();
}

class _DiseaseDetailScreenState extends State<DiseaseDetailScreen> {
  Map<String, dynamic>? _disease;
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
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.diseases}/${widget.diseaseId}'),
        headers: {'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        setState(() => _disease = jsonDecode(res.body)['data']);
      } else {
        setState(() => _error = 'ไม่พบข้อมูลโรค');
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
      appBar: AppBar(title: Text(_disease?['disease_name'] ?? 'โรค')),
      body: _isLoading
        ? const Center(child: CircularProgressIndicator())
        : _error != null
          ? Center(child: Text(_error!))
          : _buildContent(),
    );
  }

  Widget _buildContent() {
    final d = _disease!;
    final treatmentOrders = d['treatment_orders'] as List? ?? [];
    final urgencyColors = {'R': AppColors.urgencyRed, 'P': AppColors.urgencyPink,
      'Y': AppColors.urgencyYellow, 'G': AppColors.urgencyGreen, 'W': AppColors.urgencyWhite};

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Category badge
          if (d['category'] != null)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: AppColors.primaryLight, borderRadius: BorderRadius.circular(20),
              ),
              child: Text(d['category']['category_name'],
                style: AppTextStyles.body3.copyWith(color: AppColors.primary)),
            ),
          const SizedBox(height: 12),
          Text(d['disease_name'], style: AppTextStyles.h2),
          if (d['disease_name_en'] != null) ...[
            const SizedBox(height: 4),
            Text(d['disease_name_en'], style: AppTextStyles.body2),
          ],
          const Divider(height: 24),

          _section('คำอธิบาย', d['description']),
          _section('สาเหตุ', d['cause']),
          _section('อาการ', d['symptom_description']),
          _section('การป้องกัน', d['prevention']),

          // Treatment orders
          if (treatmentOrders.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text('แนวทางการรักษา', style: AppTextStyles.h3),
            const SizedBox(height: 8),
            ...treatmentOrders.map((order) {
              final color = urgencyColors[order['urgency_type']] ?? AppColors.urgencyWhite;
              return Container(
                margin: const EdgeInsets.only(bottom: 8),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  border: Border(left: BorderSide(color: color, width: 4)),
                  color: color.withOpacity(0.05),
                  borderRadius: const BorderRadius.horizontal(right: Radius.circular(8)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(order['order_name'], style: AppTextStyles.body1.copyWith(fontWeight: FontWeight.w600)),
                    if (order['description'] != null) ...[
                      const SizedBox(height: 4),
                      Text(order['description'], style: AppTextStyles.body2),
                    ],
                  ],
                ),
              );
            }),
          ],
        ],
      ),
    );
  }

  Widget _section(String title, String? content) {
    if (content == null || content.isEmpty) return const SizedBox.shrink();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: AppTextStyles.h3),
        const SizedBox(height: 6),
        Text(content, style: AppTextStyles.body1.copyWith(height: 1.7)),
        const SizedBox(height: 16),
      ],
    );
  }
}
