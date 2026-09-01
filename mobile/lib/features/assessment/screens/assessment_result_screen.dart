import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../data/models/assessment_model.dart';
import '../../../data/models/ai_assistance_model.dart';
import '../../../data/repositories/assessment_repository.dart';
import '../../assessment/providers/assessment_provider.dart';
import '../../assessment/screens/assessment_screen.dart';
import '../../history/providers/history_provider.dart';
import '../../home/screens/home_screen.dart';
import '../../auth/providers/auth_provider.dart';
import '../../health/screens/follow_up_screen.dart';
import '../../disease/screens/disease_detail_screen.dart';
import 'package:share_plus/share_plus.dart';

String _cleanRecommendation(String value) => value
    .replaceAll(
      RegExp(r'\s*\([A-Za-zก-๙]{0,4}\s*\d+(?:\.\d+)?\)', caseSensitive: false),
      '',
    )
    .replaceAll(RegExp(r'\s*/\s*'), ' หรือ ')
    .replaceAll(RegExp(r'\s{2,}'), ' ')
    .trim();

String _urgencyStatusText(String level) => switch (level) {
  'R' => 'ต้องรับการดูแลฉุกเฉิน',
  'P' => 'ควรได้รับการตรวจเร่งด่วน',
  'Y' => 'ควรพบแพทย์',
  'G' => 'ดูแลอาการเบื้องต้นได้',
  _ => 'ยังไม่พบสัญญาณเร่งด่วน',
};

String _plainLanguageSummary(String value, String? urgencyLevel) {
  final urgencyText = switch (urgencyLevel) {
    'R' => 'ต้องรับการดูแลฉุกเฉิน',
    'P' => 'ควรได้รับการตรวจอย่างเร่งด่วน',
    'Y' => 'ควรพบแพทย์',
    'G' => 'ยังสามารถดูแลอาการเบื้องต้นได้',
    _ => 'ยังไม่พบสัญญาณเร่งด่วน',
  };

  return _cleanRecommendation(value)
      .replaceAll(
        RegExp(r'(อยู่|จัดอยู่)?ใน?ระดับความเร่งด่วน\s*[RPYGW]', caseSensitive: false),
        urgencyText,
      )
      .replaceAll(RegExp(r'ระดับ\s*[RPYGW]', caseSensitive: false), urgencyText);
}

class AssessmentResultScreen extends StatefulWidget {
  final dynamic assessmentId;
  final List<AssessmentResultModel> results;
  final String symptomName;
  final bool isHistory;

  const AssessmentResultScreen({
    super.key,
    required this.assessmentId,
    required this.results,
    required this.symptomName,
    this.isHistory = false,
  });

  @override
  State<AssessmentResultScreen> createState() => _AssessmentResultScreenState();
}

class _AssessmentResultScreenState extends State<AssessmentResultScreen> {
  late List<AssessmentResultModel> _results;
  bool _saved = false;
  bool _saving = false;
  String? _saveError;
  late Future<AiGuidance> _aiGuidance;

  @override
  void initState() {
    super.initState();
    _results = widget.results;
    _saved = widget.isHistory;
    _aiGuidance = context.read<AssessmentRepository>().getAiGuidance(
      widget.assessmentId,
    );
  }

  Future<void> _refresh() async {
    final refreshed = await context.read<AssessmentRepository>().getResult(
      widget.assessmentId,
    );
    if (!mounted) return;
    setState(() => _results = refreshed.results);
  }

  Future<void> _saveResult() async {
    if (_saved || _saving) return;

    setState(() {
      _saving = true;
      _saveError = null;
    });

    try {
      await context.read<AssessmentRepository>().saveResult(
        widget.assessmentId,
      );
      await context.read<HistoryProvider>().load(refresh: true);
      if (!mounted) return;
      setState(() {
        _saved = true;
        _saving = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _saving = false;
        _saveError = 'บันทึกไม่สำเร็จ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองอีกครั้ง';
      });
      return;
    }

    await showDialog<void>(
      context: context,
      barrierColor: const Color(0xFF102A27).withOpacity(0.45),
      builder: (dialogContext) => _SaveSuccessDialog(
        onStay: () => Navigator.pop(dialogContext),
        onViewHistory: () {
          Navigator.pop(dialogContext);
          context.read<AssessmentProvider>().reset();
          Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(
              builder: (_) => const HomeScreen(
                initialTab: HomeScreen.historyTab,
              ),
            ),
            (_) => false,
          );
        },
      ),
    );
  }

  Future<void> _shareResult() async {
    final diseases = _results
        .expand((r) => r.diseases)
        .map((d) => d.diseaseName)
        .toSet()
        .join(', ');
    final advice = _results
        .map((r) => _cleanRecommendation(r.recommendation ?? ''))
        .where((text) => text.trim().isNotEmpty)
        .where((text) => !text.contains('ติดตามอาการ'))
        .join('\n');
    await Share.share(
      'สรุปผลประเมินอาการ: ${widget.symptomName}\n\n'
      '${diseases.isEmpty ? 'ยังไม่พบภาวะที่เกี่ยวข้องชัดเจน' : 'ภาวะที่อาจเกี่ยวข้อง: $diseases'}\n\n'
      '${advice.isEmpty ? 'ควรพบแพทย์หากอาการไม่ดีขึ้น' : advice}\n\n'
      'ผลนี้เป็นการประเมินเบื้องต้น ไม่ใช่การวินิจฉัยโรค',
      subject: 'ผลประเมินสุขภาพจาก Checkup',
    );
  }

  @override
  Widget build(BuildContext context) {
    final results = _results;
    final symptomName = widget.symptomName;
    final topResult = results.isNotEmpty ? results.first : null;
    final Map<String, NextDiagramModel> nextDiagramMap = {};
    for (final result in results) {
      for (final diagram in result.nextDiagrams) {
        nextDiagramMap.putIfAbsent(diagram.diagramId, () => diagram);
      }
    }
    final nextDiagrams = nextDiagramMap.values.toList()
      ..sort((a, b) => a.order.compareTo(b.order));

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        automaticallyImplyLeading: widget.isHistory,
        backgroundColor: AppColors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Text('ผลการประเมินสุขภาพ', style: AppTextStyles.h4),
        centerTitle: true,
        actions: [
          IconButton(
            tooltip: 'แชร์ผล',
            onPressed: _shareResult,
            icon: const Icon(Icons.ios_share_rounded),
          ),
          const SizedBox(width: 8),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(0.5),
          child: Divider(height: 0.5, thickness: 0.5, color: AppColors.border),
        ),
      ),
      body: RefreshIndicator(
        color: AppColors.primary,
        backgroundColor: AppColors.white,
        elevation: 0,
        onRefresh: _refresh,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(20, 24, 20, 40),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Top urgency banner
              if (topResult != null)
                _UrgencyBanner(result: topResult, symptomName: symptomName),
              const SizedBox(height: 18),

              const _AssessmentNotice(),
              const SizedBox(height: 16),
              FutureBuilder<AiGuidance>(
                future: _aiGuidance,
                builder: (context, snapshot) {
                  if (snapshot.connectionState == ConnectionState.waiting) {
                    return const LinearProgressIndicator(minHeight: 2);
                  }
                  if (!snapshot.hasData) return const SizedBox.shrink();
                  final guidance = snapshot.data!;
                  return _AiGuidanceCard(
                    guidance: guidance,
                    urgencyLevel: topResult?.urgencyLevel,
                  );
                },
              ),
              const SizedBox(height: 28),

              // Results list
              Row(
                children: [
                  Container(
                    width: 36,
                    height: 36,
                    decoration: BoxDecoration(
                      color: AppColors.surfacePrimary,
                      borderRadius: BorderRadius.circular(11),
                    ),
                    child: const Icon(
                      Icons.manage_search_rounded,
                      color: AppColors.primary,
                      size: 21,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'ข้อมูลที่อาจเกี่ยวข้อง',
                      style: AppTextStyles.h4,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                'อ่านเพื่อทำความเข้าใจเบื้องต้น ไม่ได้หมายความว่าคุณเป็นโรคนั้นแน่นอน',
                style: AppTextStyles.body1.copyWith(
                  color: AppColors.textSecondary,
                  height: 1.5,
                ),
              ),
              const SizedBox(height: 14),
              if (results.isEmpty)
                const _EmptyResultCard()
              else
                ...results.map(
                  (r) => _ResultCard(
                    result: r,
                    onDiseaseTap: (disease) => Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) =>
                            DiseaseDetailScreen(diseaseId: disease.diseaseId),
                      ),
                    ),
                  ),
                ),
              const SizedBox(height: 24),

              if (nextDiagrams.isNotEmpty) ...[
                _SectionHeader(title: 'แนะนำให้ประเมินต่อ'),
                const SizedBox(height: 8),
                ...nextDiagrams.map(
                  (diagram) => Card(
                    margin: const EdgeInsets.only(bottom: 8),
                    child: ListTile(
                      leading: const Icon(Icons.account_tree_outlined),
                      title: Text(
                        diagram.diagramName,
                        style: AppTextStyles.body1Bold,
                      ),
                      subtitle: diagram.promptText?.isNotEmpty == true
                          ? Text(
                              diagram.promptText!,
                              style: AppTextStyles.body1.copyWith(
                                color: AppColors.textSecondary,
                              ),
                            )
                          : Text(
                              'ต้องการประเมินอาการนี้ต่อหรือไม่',
                              style: AppTextStyles.body1.copyWith(
                                color: AppColors.textSecondary,
                              ),
                            ),
                      trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                      onTap: () async {
                        final provider = context.read<AssessmentProvider>();
                        final continued = await provider.continueAssessment(
                          diagram.diagramId,
                          parentAssessmentId: widget.assessmentId,
                          targetBoxId: diagram.targetBoxId,
                        );
                        if (!context.mounted) return;
                        if (!continued) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(
                                provider.error ??
                                    'ไม่สามารถเริ่มการประเมินต่อได้',
                              ),
                            ),
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

              if (context.watch<AuthProvider>().isAuthenticated) ...[
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: () => Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => FollowUpScreen(
                          assessmentId: widget.assessmentId,
                          symptomName: symptomName,
                        ),
                      ),
                    ),
                    icon: const Icon(Icons.monitor_heart_outlined),
                    label: const Text('เริ่มหรือติดตามอาการ'),
                  ),
                ),
                const SizedBox(height: 12),
              ],

              // A historical result has already been saved.
              if (!widget.isHistory)
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: (_saved || _saving) ? null : _saveResult,
                    icon: Icon(
                      _saved
                          ? Icons.check_circle
                          : _saving
                          ? Icons.sync_rounded
                          : Icons.bookmark_add_outlined,
                    ),
                    label: Text(
                      _saved
                          ? 'บันทึกในประวัติแล้ว'
                          : _saving
                          ? 'กำลังบันทึก...'
                          : 'บันทึกผลไว้ในประวัติ',
                    ),
                    style: ElevatedButton.styleFrom(
                      minimumSize: const Size.fromHeight(54),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                  ),
                ),
              if (!widget.isHistory && _saveError != null) ...[
                const SizedBox(height: 8),
                Text(
                  _saveError!,
                  style: AppTextStyles.body3.copyWith(color: AppColors.danger),
                  textAlign: TextAlign.center,
                ),
              ],
              if (!widget.isHistory) const SizedBox(height: 12),
              if (!widget.isHistory)
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
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
            ],
          ),
        ),
      ),
    );
  }
}

void _showDiseaseDetail(BuildContext context, DiseaseModel disease) {
  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (context) => DraggableScrollableSheet(
      initialChildSize: 0.82,
      minChildSize: 0.55,
      maxChildSize: 0.94,
      expand: false,
      builder: (context, scrollController) => Container(
        decoration: const BoxDecoration(
          color: AppColors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: ListView(
          controller: scrollController,
          padding: const EdgeInsets.fromLTRB(20, 10, 20, 32),
          children: [
            Center(
              child: Container(
                width: 42,
                height: 4,
                decoration: BoxDecoration(
                  color: AppColors.border,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 20),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: AppColors.primary.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: const Icon(
                    Icons.medical_information_outlined,
                    color: AppColors.primary,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(disease.diseaseName, style: AppTextStyles.h3),
                      if (disease.diseaseNameEn?.isNotEmpty == true)
                        Text(
                          disease.diseaseNameEn!,
                          style: AppTextStyles.body3.copyWith(
                            color: AppColors.textSecondary,
                          ),
                        ),
                    ],
                  ),
                ),
                IconButton(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close_rounded),
                ),
              ],
            ),
            const SizedBox(height: 20),
            _DiseaseDetailSection(
              title: 'เกี่ยวกับโรค',
              value: disease.description,
              icon: Icons.info_outline_rounded,
            ),
            _DiseaseDetailSection(
              title: 'อาการของโรค',
              value: disease.symptomDescription,
              icon: Icons.sick_outlined,
            ),
            _DiseaseDetailSection(
              title: 'สาเหตุ',
              value: disease.cause,
              icon: Icons.search_rounded,
            ),
            _DiseaseDetailSection(
              title: 'ภาวะแทรกซ้อน',
              value: disease.complications,
              icon: Icons.warning_amber_rounded,
            ),
            _DiseaseDetailSection(
              title: 'การวินิจฉัย',
              value: disease.diagnosis,
              icon: Icons.fact_check_outlined,
            ),
            _DiseaseDetailSection(
              title: 'การรักษา',
              value: disease.medicalTreatment,
              icon: Icons.medication_outlined,
            ),
            _DiseaseDetailSection(
              title: 'การดูแลตัวเอง',
              value: disease.selfCare,
              icon: Icons.self_improvement_rounded,
            ),
            _DiseaseDetailSection(
              title: 'ควรพบแพทย์เมื่อใด',
              value: disease.whenToSeeDoctor,
              icon: Icons.local_hospital_outlined,
            ),
            _DiseaseDetailSection(
              title: 'การป้องกัน',
              value: disease.prevention,
              icon: Icons.shield_outlined,
            ),
            _DiseaseDetailSection(
              title: 'คำแนะนำ',
              value: disease.recommendations,
              icon: Icons.lightbulb_outline_rounded,
            ),
          ],
        ),
      ),
    ),
  );
}

class _AiGuidanceCard extends StatelessWidget {
  final AiGuidance guidance;
  final String? urgencyLevel;

  const _AiGuidanceCard({required this.guidance, this.urgencyLevel});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFDCD9FF)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.auto_awesome_rounded,
                  color: AppColors.primary,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'สรุปคำตอบและสิ่งที่ควรทำ',
                      style: AppTextStyles.body1Bold,
                    ),
                    Text(
                      guidance.cached
                          ? 'AI ช่วยเรียบเรียงไว้จากการประเมินครั้งนี้'
                          : 'AI ช่วยเรียบเรียงจากคำตอบของคุณ',
                      style: AppTextStyles.body3.copyWith(
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFFF5F4FF),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Text(
              _plainLanguageSummary(guidance.summary, urgencyLevel),
              style: AppTextStyles.body2.copyWith(height: 1.55),
            ),
          ),
          if (guidance.assessmentOverview.isNotEmpty)
            _AiGuidanceSection(
              icon: Icons.fact_check_outlined,
              title: 'สิ่งที่สรุปจากคำตอบของคุณ',
              items: guidance.assessmentOverview,
              color: AppColors.primary,
            ),
          if (guidance.selfCare.isNotEmpty)
            _AiGuidanceSection(
              icon: Icons.health_and_safety_outlined,
              title: 'การดูแลเบื้องต้น',
              items: guidance.selfCare,
              color: AppColors.successText,
            ),
          if (guidance.warningSigns.isNotEmpty)
            _AiGuidanceSection(
              icon: Icons.warning_amber_rounded,
              title: 'อาการที่ควรเฝ้าระวัง',
              items: guidance.warningSigns,
              color: AppColors.danger,
            ),
          if (guidance.nextSteps.isNotEmpty)
            _AiGuidanceSection(
              icon: Icons.route_outlined,
              title: 'สิ่งที่ควรทำต่อ',
              items: guidance.nextSteps.map((step) => step.label).toList(),
              color: AppColors.primary,
            ),
          const SizedBox(height: 14),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              guidance.disclaimer,
              style: AppTextStyles.body2.copyWith(
                color: AppColors.textSecondary,
                height: 1.55,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _AiGuidanceSection extends StatelessWidget {
  final IconData icon;
  final String title;
  final List<String> items;
  final Color color;

  const _AiGuidanceSection({
    required this.icon,
    required this.title,
    required this.items,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 20, color: color),
              const SizedBox(width: 8),
              Expanded(child: Text(title, style: AppTextStyles.body2Bold)),
            ],
          ),
          const SizedBox(height: 8),
          ...items.map(
            (item) => Padding(
              padding: const EdgeInsets.only(left: 4, bottom: 7),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Padding(
                    padding: const EdgeInsets.only(top: 7),
                    child: Container(
                      width: 5,
                      height: 5,
                      decoration: BoxDecoration(color: color, shape: BoxShape.circle),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(item, style: AppTextStyles.body2.copyWith(height: 1.45)),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _DiseaseDetailSection extends StatelessWidget {
  final String title;
  final String? value;
  final IconData icon;

  const _DiseaseDetailSection({
    required this.title,
    required this.value,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    if (value?.trim().isNotEmpty != true) return const SizedBox.shrink();
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFFF8F8FC),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: AppColors.primary, size: 21),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: AppTextStyles.body1Bold),
                const SizedBox(height: 5),
                Text(
                  value!.trim(),
                  style: AppTextStyles.body1.copyWith(
                    color: AppColors.textPrimary,
                    height: 1.55,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptyResultCard extends StatelessWidget {
  const _EmptyResultCard();

  @override
  Widget build(BuildContext context) => Container(
    width: double.infinity,
    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 22),
    decoration: BoxDecoration(
      color: AppColors.white,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: AppColors.border),
    ),
    child: Row(
      children: [
        Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            color: AppColors.surfacePrimary,
            borderRadius: BorderRadius.circular(14),
          ),
          child: const Icon(
            Icons.check_circle_outline_rounded,
            color: AppColors.primary,
            size: 24,
          ),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Text(
            'ยังไม่พบภาวะที่เกี่ยวข้องอย่างชัดเจน',
            style: AppTextStyles.body1Bold.copyWith(height: 1.45),
          ),
        ),
      ],
    ),
  );
}

class _SaveSuccessDialog extends StatelessWidget {
  final VoidCallback onStay;
  final VoidCallback onViewHistory;

  const _SaveSuccessDialog({required this.onStay, required this.onViewHistory});

  @override
  Widget build(BuildContext context) {
    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 28),
      backgroundColor: Colors.transparent,
      child: Container(
        padding: const EdgeInsets.fromLTRB(22, 26, 22, 20),
        decoration: BoxDecoration(
          color: AppColors.white,
          borderRadius: BorderRadius.circular(28),
          boxShadow: const [
            BoxShadow(
              color: Color(0x26102A27),
              blurRadius: 36,
              offset: Offset(0, 16),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Stack(
              alignment: Alignment.center,
              children: [
                Container(
                  width: 88,
                  height: 88,
                  decoration: const BoxDecoration(
                    color: Color(0xFFE5F7F1),
                    shape: BoxShape.circle,
                  ),
                ),
                Container(
                  width: 62,
                  height: 62,
                  decoration: const BoxDecoration(
                    color: Color(0xFF2F9E7C),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.check_rounded,
                    color: Colors.white,
                    size: 36,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 18),
            Text('บันทึกเรียบร้อยแล้ว', style: AppTextStyles.h4),
            const SizedBox(height: 8),
            Text(
              'ผลการประเมินถูกเก็บไว้ในประวัติแล้ว\nคุณสามารถกลับมาดูได้ทุกเมื่อ',
              style: AppTextStyles.body1.copyWith(
                color: AppColors.textSecondary,
                height: 1.55,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 22),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: onViewHistory,
                icon: const Icon(Icons.history_rounded),
                label: const Text('ดูประวัติการประเมิน'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF267D70),
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(52),
                  elevation: 0,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 6),
            TextButton(
              onPressed: onStay,
              style: TextButton.styleFrom(
                foregroundColor: AppColors.textSecondary,
              ),
              child: const Text('อยู่หน้านี้ต่อ'),
            ),
          ],
        ),
      ),
    );
  }
}

class _AssessmentNotice extends StatelessWidget {
  const _AssessmentNotice();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: const BoxDecoration(
              color: AppColors.surfacePrimary,
              borderRadius: BorderRadius.all(Radius.circular(14)),
            ),
            child: const Icon(
              Icons.info_outline_rounded,
              color: AppColors.primary,
              size: 25,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'ข้อมูลสำคัญ',
                  style: AppTextStyles.body1Bold.copyWith(
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'ผลนี้เป็นเพียงการคัดกรองเบื้องต้น ไม่ใช่การวินิจฉัยโรค หากอาการรุนแรงขึ้นหรือไม่แน่ใจ ควรพบแพทย์',
                  style: AppTextStyles.body1.copyWith(
                    color: AppColors.textPrimary,
                    height: 1.55,
                  ),
                ),
              ],
            ),
          ),
        ],
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
  final String symptomName;

  const _UrgencyBanner({required this.result, required this.symptomName});

  @override
  Widget build(BuildContext context) {
    final requiresMedicalCare = const [
      'R',
      'P',
      'Y',
    ].contains(result.urgencyLevel);
    final emphasisColor = requiresMedicalCare
        ? AppColors.danger
        : AppColors.primary;
    final timeFrame = result.timeFrame?.trim() ?? '';
    final recommendation = result.recommendation?.trim() ?? '';
    final showRecommendation =
        recommendation.isNotEmpty && !recommendation.contains('ติดตามอาการ');
    final action = requiresMedicalCare
        ? 'ควรไปพบแพทย์'
        : 'สามารถดูแลอาการเบื้องต้นได้';
    final plainRecommendation = _cleanRecommendation(recommendation);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: const BoxDecoration(
                  color: AppColors.surfacePrimary,
                  borderRadius: BorderRadius.all(Radius.circular(14)),
                ),
                child: Icon(
                  requiresMedicalCare
                      ? Icons.local_hospital_rounded
                      : Icons.health_and_safety_outlined,
                  color: emphasisColor,
                  size: 25,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'ผลการคัดกรองเบื้องต้น',
                      style: AppTextStyles.h4.copyWith(
                        color: AppColors.textPrimary,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: emphasisColor.withOpacity(0.10),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              _urgencyStatusText(result.urgencyLevel),
              style: AppTextStyles.body2Bold.copyWith(color: emphasisColor),
            ),
          ),
          const SizedBox(height: 12),
          Text(
            timeFrame.isEmpty ? action : '$action $timeFrame',
            style: AppTextStyles.h4.copyWith(color: emphasisColor, height: 1.35),
          ),
          const SizedBox(height: 8),
          Text(
            requiresMedicalCare
                ? 'จากคำตอบของคุณ ควรให้แพทย์ตรวจประเมินเพิ่มเติมเพื่อหาสาเหตุที่ชัดเจน ผลนี้ยังไม่ใช่การวินิจฉัยโรค'
                : 'จากคำตอบของคุณ ยังไม่พบสัญญาณที่ต้องรับการดูแลเร่งด่วน หากอาการเปลี่ยนแปลงควรประเมินใหม่',
            style: AppTextStyles.body1.copyWith(
              color: AppColors.textSecondary,
              height: 1.55,
            ),
          ),
          if (symptomName.isNotEmpty) ...[
            const SizedBox(height: 10),
            Text(
              'อาการที่ประเมิน : $symptomName',
              style: AppTextStyles.body1.copyWith(color: AppColors.textPrimary),
            ),
          ],
          if (showRecommendation && plainRecommendation.isNotEmpty) ...[
            const SizedBox(height: 14),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(Icons.task_alt_rounded, color: AppColors.primary, size: 20),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'คำแนะนำเบื้องต้น',
                          style: AppTextStyles.body1.copyWith(
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          plainRecommendation,
                          style: AppTextStyles.body1.copyWith(height: 1.5),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _ResultCard extends StatelessWidget {
  final AssessmentResultModel result;
  final ValueChanged<DiseaseModel> onDiseaseTap;
  const _ResultCard({required this.result, required this.onDiseaseTap});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      child: Card(
        margin: const EdgeInsets.only(bottom: 12),
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: AppColors.border),
        ),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (result.diseases.isEmpty) ...[
                Text(
                  'ยังไม่พบภาวะที่เกี่ยวข้องอย่างชัดเจน',
                  style: AppTextStyles.body1,
                ),
              ],
              if (result.diseases.isNotEmpty)
                ...result.diseases.asMap().entries.map(
                  (entry) => Column(
                    children: [
                      ListTile(
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 2,
                          vertical: 6,
                        ),
                        minVerticalPadding: 6,
                        title: Text(
                          entry.value.diseaseName,
                          style: AppTextStyles.body1.copyWith(
                            fontWeight: FontWeight.w600,
                            height: 1.4,
                          ),
                        ),
                        subtitle: Text(
                          'แตะเพื่อดูข้อมูลโดยละเอียด',
                          style: AppTextStyles.body2.copyWith(
                            color: AppColors.textSecondary,
                            height: 1.45,
                          ),
                        ),
                        trailing: const Icon(
                          Icons.arrow_forward_ios_rounded,
                          size: 16,
                          color: AppColors.primary,
                        ),
                        onTap: () => onDiseaseTap(entry.value),
                      ),
                      if (entry.key < result.diseases.length - 1)
                        const Divider(height: 1, color: AppColors.border),
                    ],
                  ),
                ),
            ],
          ),
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
                style: AppTextStyles.body1,
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
                Text('อาการของโรค', style: AppTextStyles.body1Bold),
                const SizedBox(height: 4),
                Text(disease.symptomDescription!, style: AppTextStyles.body1),
              ],
              if (disease.cause != null && disease.cause!.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text('สาเหตุ', style: AppTextStyles.body1Bold),
                const SizedBox(height: 4),
                Text(disease.cause!, style: AppTextStyles.body1),
              ],
              if (disease.prevention != null &&
                  disease.prevention!.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text('การป้องกัน', style: AppTextStyles.body1Bold),
                const SizedBox(height: 4),
                Text(disease.prevention!, style: AppTextStyles.body1),
              ],
            ],
            if (disease.hasDetail) ...[
              const SizedBox(height: 8),
              Align(
                alignment: Alignment.centerRight,
                child: OutlinedButton(
                  onPressed: () => Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) =>
                          DiseaseDetailScreen(diseaseId: disease.diseaseId),
                    ),
                  ),
                  style: OutlinedButton.styleFrom(
                    side: const BorderSide(color: AppColors.primary),
                    foregroundColor: AppColors.primary,
                    minimumSize: const Size(0, 32),
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                  ),
                  child: const Text('ดูรายละเอียด'),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
