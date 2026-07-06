import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../data/models/treatment_order_model.dart';
import '../providers/disease_detail_provider.dart';

class DiseaseDetailScreen extends StatefulWidget {
  final String diseaseId;
  const DiseaseDetailScreen({super.key, required this.diseaseId});

  @override
  State<DiseaseDetailScreen> createState() => _DiseaseDetailScreenState();
}

class _DiseaseDetailScreenState extends State<DiseaseDetailScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DiseaseDetailProvider>().load(widget.diseaseId);
    });
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<DiseaseDetailProvider>();

    return Scaffold(
      appBar: AppBar(
        backgroundColor: AppColors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Text('ข้อมูลโรค', style: AppTextStyles.h4),
        centerTitle: true,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(0.5),
          child: Divider(height: 0.5, thickness: 0.5, color: AppColors.border),
        ),
      ),
      body: _buildBody(provider),
    );
  }

  Widget _buildBody(DiseaseDetailProvider provider) {
    if (provider.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (provider.error != null) {
      return Center(child: Text(provider.error!, style: AppTextStyles.body2));
    }

    final disease = provider.detail;
    if (disease == null) {
      return const Center(child: Text('ไม่พบข้อมูลโรค'));
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildHeader(
            disease.diseaseName,
            disease.diseaseNameEn,
            disease.diseaseImage,
          ),
          const SizedBox(height: 16),
          if (disease.description != null && disease.description!.isNotEmpty)
            Text(
              disease.description!,
              style: AppTextStyles.body2.copyWith(height: 1.6),
            ),
          const SizedBox(height: 24),

          if (disease.symptomList.isNotEmpty)
            _sectionCard(
              icon: Icons.assignment_outlined,
              iconColor: AppColors.primary,
              title: 'อาการ',
              child: Column(
                children: disease.symptomList
                    .map((s) => _bulletItem(s))
                    .toList(),
              ),
            ),

          if (disease.cause != null && disease.cause!.trim().isNotEmpty)
            _sectionCard(
              icon: Icons.coronavirus_outlined,
              iconColor: AppColors.primaryMid,
              title: 'สาเหตุ',
              child: Text(
                disease.cause!,
                style: AppTextStyles.body2.copyWith(height: 1.6),
              ),
            ),

          if (disease.treatmentOrders.isNotEmpty)
            _sectionCard(
              icon: Icons.local_hospital_outlined,
              iconColor: AppColors.primary,
              title: 'การรักษา',
              child: Column(
                children: disease.treatmentOrders
                    .map((t) => _treatmentItem(t))
                    .toList(),
              ),
            ),

          if (disease.preventionList.isNotEmpty)
            _sectionCard(
              icon: Icons.tips_and_updates_outlined,
              iconColor: AppColors.warning,
              title: 'การดูแลตนเอง',
              child: Column(
                children: disease.preventionList
                    .map((p) => _careItem(p))
                    .toList(),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildHeader(String name, String? nameEn, String? image) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 56,
          height: 56,
          decoration: BoxDecoration(
            color: AppColors.primaryLight,
            borderRadius: BorderRadius.circular(16),
          ),
          child: image != null
              ? ClipRRect(
                  borderRadius: BorderRadius.circular(16),
                  child: Image.network(image, fit: BoxFit.cover),
                )
              : const Icon(
                  Icons.medical_services_outlined,
                  color: AppColors.primary,
                  size: 26,
                ),
        ),
        const SizedBox(height: 12),
        Text(name, style: AppTextStyles.h3),
        if (nameEn != null && nameEn.isNotEmpty)
          Text(
            '($nameEn)',
            style: AppTextStyles.h4.copyWith(color: AppColors.textSecondary),
          ),
      ],
    );
  }

  Widget _sectionCard({
    required IconData icon,
    required Color iconColor,
    required String title,
    required Widget child,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: iconColor, size: 20),
              const SizedBox(width: 8),
              Text(title, style: AppTextStyles.body1Bold),
            ],
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }

  Widget _bulletItem(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 8),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.only(top: 6, right: 8),
          child: CircleAvatar(radius: 3, backgroundColor: AppColors.primary),
        ),
        Expanded(
          child: Text(
            text.trim(),
            style: AppTextStyles.body2.copyWith(height: 1.5),
          ),
        ),
      ],
    ),
  );

  Widget _treatmentItem(TreatmentOrderModel order) => Container(
    margin: const EdgeInsets.only(bottom: 10),
    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
    decoration: BoxDecoration(
      color: AppColors.surface,
      borderRadius: BorderRadius.circular(12),
    ),
    child: Row(
      children: [
        Icon(
          _treatmentIcon(order.orderName),
          color: AppColors.primary,
          size: 18,
        ),
        const SizedBox(width: 10),
        Expanded(child: Text(order.orderName, style: AppTextStyles.body2)),
      ],
    ),
  );

  Widget _careItem(String text) {
    // แยก "หัวข้อ: รายละเอียด" ถ้ามี ':' ในบรรทัด
    final parts = text.split(':');
    final title = parts.first.trim();
    final desc = parts.length > 1 ? parts.sublist(1).join(':').trim() : null;

    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(_careIcon(title), color: AppColors.primary, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: AppTextStyles.body2Bold),
                if (desc != null && desc.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(
                      desc,
                      style: AppTextStyles.body3.copyWith(
                        color: AppColors.textSecondary,
                        height: 1.5,
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  IconData _treatmentIcon(String name) {
    if (name.contains('ไข้') || name.contains('ปวด'))
      return Icons.medication_outlined;
    if (name.contains('น้ำมูก') || name.contains('แพ้'))
      return Icons.water_drop_outlined;
    if (name.contains('ไอ') || name.contains('เสมหะ'))
      return Icons.air_outlined;
    return Icons.medical_services_outlined;
  }

  IconData _careIcon(String title) {
    if (title.contains('น้ำ')) return Icons.local_drink_outlined;
    if (title.contains('พัก') || title.contains('นอน'))
      return Icons.bed_outlined;
    if (title.contains('อบอุ่น') || title.contains('อุณหภูมิ'))
      return Icons.thermostat_outlined;
    return Icons.tips_and_updates_outlined;
  }
}
