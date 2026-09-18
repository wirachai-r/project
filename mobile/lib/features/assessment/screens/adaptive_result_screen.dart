import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../data/models/adaptive_assessment_model.dart';
import '../../../shared/widgets/app_layout.dart';
import '../../disease/screens/disease_detail_screen.dart';

class AdaptiveResultScreen extends StatelessWidget {
  final dynamic assessmentId;
  final String symptomName;
  final List<AdaptiveDiseaseResultModel> results;
  const AdaptiveResultScreen({super.key, required this.assessmentId, required this.symptomName, required this.results});

  @override Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('ผลการประเมิน'), centerTitle: true),
    body: AppContentWidth(child: ListView(padding: const EdgeInsets.all(24), children: [
      const Icon(Icons.health_and_safety_outlined, size: 54, color: AppColors.primary),
      const SizedBox(height: 14), Text('ภาวะที่อาจเกี่ยวข้อง', textAlign: TextAlign.center, style: AppTextStyles.h3),
      const SizedBox(height: 8), Text('จากอาการ $symptomName และคำตอบของคุณ ผลนี้เป็นข้อมูลคัดกรองเบื้องต้น ไม่ใช่การวินิจฉัย', textAlign: TextAlign.center, style: AppTextStyles.body2.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant)),
      const SizedBox(height: 24),
      if (results.isEmpty) Container(padding: const EdgeInsets.all(20), child: const Text('ข้อมูลยังไม่เพียงพอที่จะระบุภาวะที่อาจเกี่ยวข้อง')),
      for (final item in results) Card(child: ListTile(
        contentPadding: const EdgeInsets.all(16),
        title: Text(item.diseaseName, style: AppTextStyles.body1Bold),
        subtitle: Padding(padding: const EdgeInsets.only(top: 8), child: Text('ความสอดคล้องกับคำตอบ ${item.matchPercent}%')),
        trailing: item.hasArticle ? const Icon(Icons.chevron_right_rounded) : null,
        onTap: item.hasArticle && item.diseaseId != null ? () => Navigator.push(context, MaterialPageRoute(builder: (_) => DiseaseDetailScreen(diseaseId: item.diseaseId!))) : null,
      )),
      const SizedBox(height: 12), Text('หากอาการรุนแรง แย่ลง หรือกังวล ควรติดต่อบุคลากรทางการแพทย์', style: AppTextStyles.body2.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant)),
    ])),
  );
}
