import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../data/models/assessment_model.dart';
import '../../assessment/providers/assessment_provider.dart';
import '../../assessment/screens/symptom_select_screen.dart';
import '../../assessment/screens/assessment_screen.dart';

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

    final Map<String, DiseaseModel> diseaseMap = {};
    for (final r in results) {
      for (final d in r.diseases) {
        diseaseMap.putIfAbsent(d.diseaseId, () => d);
      }
    }
    final allDiseases = diseaseMap.values.toList();
    final Map<String, NextDiagramModel> nextDiagramMap = {};
    for (final result in results) {
      for (final diagram in result.nextDiagrams) {
        nextDiagramMap.putIfAbsent(diagram.diagramId, () => diagram);
      }
    }
    final nextDiagrams = nextDiagramMap.values.toList()
      ..sort((a, b) => a.order.compareTo(b.order));

    return Scaffold(
      backgroundColor: AppColors.white,
      appBar: AppBar(
        automaticallyImplyLeading: false,
        backgroundColor: AppColors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Text('ผลการประเมิน', style: AppTextStyles.h4),
        centerTitle: true,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(0.5),
          child: Divider(height: 0.5, thickness: 0.5, color: AppColors.border),
        ),
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

            // ข้อมูลโรคแบบละเอียด (accordion ขยาย/ย่อ) ตามตัวอย่างหน้าจอ
            if (allDiseases.isNotEmpty) ...[
              _SectionHeader(title: 'ดูข้อมูลโรค'),
              const SizedBox(height: 8),
              ...allDiseases.map((d) => _DiseaseDetailCard(disease: d)),
              const SizedBox(height: 16),
            ],

            if (nextDiagrams.isNotEmpty) ...[
              _SectionHeader(title: 'แนะนำให้ประเมินต่อ'),
              const SizedBox(height: 8),
              ...nextDiagrams.map(
                (diagram) => Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: ListTile(
                    leading: const Icon(Icons.account_tree_outlined),
                    title: Text(diagram.diagramName),
                    subtitle: diagram.promptText?.isNotEmpty == true
                        ? Text(diagram.promptText!)
                        : const Text('ต้องการประเมินแผนภูมินี้ต่อหรือไม่'),
                    trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                    onTap: () async {
                      final provider = context.read<AssessmentProvider>();
                      final continued = await provider.continueAssessment(
                        diagram.diagramId,
                      );
                      if (!context.mounted) return;
                      if (!continued) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(content: Text(provider.error ?? 'ไม่สามารถเริ่มการประเมินต่อได้')),
                        );
                        return;
                      }
                      Navigator.of(context).pushReplacement(
                        MaterialPageRoute(
                          builder: (_) => AssessmentScreen(
                            symptomId: provider.symptomId ?? '',
                            symptomName: symptomName,
                            resumeExisting: true,
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ),
              const SizedBox(height: 16),
            ],

            // Actions
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () {
                  // เคลียร์ค่าค้างใน provider ก่อนกลับหน้าหลัก เผื่อ navigation
                  // stack ยังเก็บ AssessmentScreen เดิมไว้ (state ไม่ถูก dispose
                  // ตามไปด้วยทันทีถ้าไม่ pop ผ่าน Navigator จริง ๆ)
                  context.read<AssessmentProvider>().reset();
                  Navigator.of(
                    context,
                  ).pushNamedAndRemoveUntil('/', (_) => false);
                },
                icon: const Icon(Icons.home_outlined),
                label: const Text('กลับหน้าหลัก'),
              ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: () {
                  context.read<AssessmentProvider>().reset();
                  Navigator.of(context).pushAndRemoveUntil(
                    MaterialPageRoute(
                      builder: (_) => const SymptomSelectScreen(),
                    ),
                    (route) =>
                        route.isFirst, // เก็บหน้าแรกสุด (home) ไว้ใน stack
                  );
                },
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

// หัวข้อ section แบบแถบสีพื้นอ่อน เหมือนตัวอย่างภาพ "ดูข้อมูลโรค"
class _SectionHeader extends StatelessWidget {
  final String title;
  const _SectionHeader({required this.title});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: BoxDecoration(
        color: AppColors.primary.withOpacity(0.08),
        borderRadius: BorderRadius.circular(8),
      ),
      alignment: Alignment.center,
      child: Text(
        title,
        style: AppTextStyles.body1Bold.copyWith(color: AppColors.primary),
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
    final diseaseNames = result.diseases.isNotEmpty
        ? result.diseaseNamesText
        : 'ไม่ระบุชื่อโรค';

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  margin: const EdgeInsets.only(top: 6),
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
                    diseaseNames,
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

// การ์ดข้อมูลโรคแบบ expandable ("ขยาย"/"ย่อ") ตามตัวอย่างภาพที่แนบมา
// แสดงชื่อโรค + คำอธิบายย่อเสมอ และเปิดดู cause/symptom_description/prevention เพิ่มได้
class _DiseaseDetailCard extends StatefulWidget {
  final DiseaseModel disease;
  const _DiseaseDetailCard({required this.disease});

  @override
  State<_DiseaseDetailCard> createState() => _DiseaseDetailCardState();
}

class _DiseaseDetailCardState extends State<_DiseaseDetailCard> {
  bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    final disease = widget.disease;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              disease.diseaseName,
              style: AppTextStyles.body1Bold.copyWith(color: AppColors.primary),
            ),
            if (disease.description != null &&
                disease.description!.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                disease.description!,
                style: AppTextStyles.body2,
                maxLines: _expanded ? null : 3,
                overflow: _expanded
                    ? TextOverflow.visible
                    : TextOverflow.ellipsis,
              ),
            ],
            if (_expanded) ...[
              if (disease.symptomDescription != null &&
                  disease.symptomDescription!.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text('อาการของโรค', style: AppTextStyles.body2Bold),
                const SizedBox(height: 4),
                Text(disease.symptomDescription!, style: AppTextStyles.body2),
              ],
              if (disease.cause != null && disease.cause!.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text('สาเหตุ', style: AppTextStyles.body2Bold),
                const SizedBox(height: 4),
                Text(disease.cause!, style: AppTextStyles.body2),
              ],
              if (disease.prevention != null &&
                  disease.prevention!.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text('การป้องกัน', style: AppTextStyles.body2Bold),
                const SizedBox(height: 4),
                Text(disease.prevention!, style: AppTextStyles.body2),
              ],
            ],
            if (disease.hasDetail) ...[
              const SizedBox(height: 8),
              Align(
                alignment: Alignment.centerRight,
                child: OutlinedButton(
                  onPressed: () => setState(() => _expanded = !_expanded),
                  style: OutlinedButton.styleFrom(
                    side: const BorderSide(color: AppColors.primary),
                    foregroundColor: AppColors.primary,
                    minimumSize: const Size(0, 32),
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                  ),
                  child: Text(_expanded ? 'ย่อ' : 'ขยาย'),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
