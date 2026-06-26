import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../data/models/assessment_model.dart';

class AssessmentResultScreen extends StatelessWidget {
  final List<AssessmentResultModel> results;
  final String symptomName;

  const AssessmentResultScreen({
    super.key,
    required this.results,
    required this.symptomName,
  });

  @override
  Widget build(BuildContext context) {
    final topResult = results.isNotEmpty ? results.first : null;
    final shouldSeeDoctor = topResult?.shouldSeeDoctor == 'Y';

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('ผลการประเมิน'),
        automaticallyImplyLeading: false,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top urgency banner
            if (topResult != null) _UrgencyBanner(result: topResult),
            const SizedBox(height: 16),

            // See doctor advice
            if (shouldSeeDoctor) ...[
              _SeeDocterCard(),
              const SizedBox(height: 16),
            ],

            // Results list
            Text('โรคที่อาจเกี่ยวข้อง', style: AppTextStyles.h3),
            const SizedBox(height: 8),
            ...results.map((r) => _ResultCard(result: r)),
            const SizedBox(height: 24),

            // Actions
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => Navigator.of(
                  context,
                ).pushNamedAndRemoveUntil('/', (_) => false),
                icon: const Icon(Icons.home_outlined),
                label: const Text('กลับหน้าหลัก'),
              ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: () => Navigator.of(context).pop(),
                icon: const Icon(Icons.refresh),
                label: const Text('ประเมินอีกครั้ง'),
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(50),
                  side: const BorderSide(color: AppColors.primary),
                  foregroundColor: AppColors.primary,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _UrgencyBanner extends StatelessWidget {
  final AssessmentResultModel result;
  const _UrgencyBanner({required this.result});

  @override
  Widget build(BuildContext context) {
    final color = AppColors.urgencyColor(result.urgencyLevel);
    final label = AppColors.urgencyLabel(result.urgencyLevel);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.4)),
      ),
      child: Column(
        children: [
          Icon(Icons.health_and_safety_outlined, color: color, size: 48),
          const SizedBox(height: 8),
          Text(label, style: AppTextStyles.h2.copyWith(color: color)),
          const SizedBox(height: 4),
          Text(
            'ระดับความเร่งด่วน: ${result.urgencyLevel}',
            style: AppTextStyles.body2,
          ),
        ],
      ),
    );
  }
}

class _SeeDocterCard extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.urgencyRed.withOpacity(0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.urgencyRed.withOpacity(0.3)),
      ),
      child: Row(
        children: [
          const Icon(
            Icons.local_hospital_outlined,
            color: AppColors.urgencyRed,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              'แนะนำให้พบแพทย์โดยเร็ว',
              style: AppTextStyles.body1.copyWith(
                color: AppColors.urgencyRed,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ResultCard extends StatelessWidget {
  final AssessmentResultModel result;
  const _ResultCard({required this.result});

  @override
  Widget build(BuildContext context) {
    final color = AppColors.urgencyColor(result.urgencyLevel);

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 8,
                  height: 8,
                  decoration: BoxDecoration(
                    color: color,
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    result.diseaseName ?? result.diseaseId,
                    style: AppTextStyles.body1.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: color.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    AppColors.urgencyLabel(result.urgencyLevel),
                    style: AppTextStyles.body3.copyWith(
                      color: color,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
            if (result.recommendation != null &&
                result.recommendation!.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(result.recommendation!, style: AppTextStyles.body2),
            ],
          ],
        ),
      ),
    );
  }
}
