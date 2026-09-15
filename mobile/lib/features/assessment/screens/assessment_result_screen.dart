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
import '../../../shared/widgets/app_layout.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../data/services/local_notification_service.dart';

String _cleanRecommendation(String value) => value
    .replaceAll(
      RegExp(r'\s*\([A-Za-zก-๙]{0,4}\s*\d+(?:\.\d+)?\)', caseSensitive: false),
      '',
    )
    .replaceAll(RegExp(r'\s*[•●◦▪▫‣⁃⊕]\s*'), ' ')
    .replaceAll(RegExp(r'\s*/\s*'), ' หรือ ')
    .replaceAll(RegExp(r'\s{2,}'), ' ')
    .trim();

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
        RegExp(
          r'(อยู่|จัดอยู่)?ใน?ระดับความเร่งด่วน\s*[RPYGW]',
          caseSensitive: false,
        ),
        urgencyText,
      )
      .replaceAll(
        RegExp(r'ระดับ\s*[RPYGW]', caseSensitive: false),
        urgencyText,
      );
}

List<String> _guidanceDisplayItems(List<String> items) => items
    .expand((item) => item.split(RegExp(r'[\r\n]+')))
    .map(
      (item) => item
          .replaceFirst(
            RegExp(r'^\s*(?:[•●◦▪▫‣⁃+⊕-]|\d+[.)])\s*'),
            '',
          )
          .trim(),
    )
    .where((item) => item.isNotEmpty)
    .toList();

class AssessmentResultScreen extends StatefulWidget {
  final dynamic assessmentId;
  final String symptomId;
  final List<AssessmentResultModel> results;
  final String symptomName;
  final bool isHistory;
  final dynamic healthEpisodeId;
  final DateTime? completedAt;

  const AssessmentResultScreen({
    super.key,
    required this.assessmentId,
    required this.symptomId,
    required this.results,
    required this.symptomName,
    this.isHistory = false,
    this.healthEpisodeId,
    this.completedAt,
  });

  @override
  State<AssessmentResultScreen> createState() => _AssessmentResultScreenState();
}

class _AssessmentResultScreenState extends State<AssessmentResultScreen> {
  late List<AssessmentResultModel> _results;
  bool _saved = false;
  bool _saving = false;
  bool _trackingStarted = false;
  String? _saveError;
  late Future<AiGuidance> _aiGuidance;

  @override
  void initState() {
    super.initState();
    _results = widget.results;
    _saved = widget.isHistory;
    _trackingStarted = widget.healthEpisodeId != null;
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

    final assessmentRepository = context.read<AssessmentRepository>();
    final historyProvider = context.read<HistoryProvider>();
    try {
      await assessmentRepository.saveResult(widget.assessmentId);
      await historyProvider.load(refresh: true);
      if (!mounted) return;
      setState(() {
        _saved = true;
        _saving = false;
      });
      await LocalNotificationService.instance.showActivity(
        title: 'บันทึกประวัติเรียบร้อยแล้ว',
        body: 'แตะเพื่อดูรายละเอียดผลการประเมิน',
        payload: 'assessment:${widget.assessmentId}',
      );
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _saving = false;
        _saveError = 'บันทึกไม่สำเร็จ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองอีกครั้ง';
      });
      return;
    }

    if (!mounted) return;
    await showDialog<void>(
      context: context,
      barrierColor: Theme.of(
        context,
      ).colorScheme.onSurface.withValues(alpha: 0.45),
      builder: (dialogContext) => _SaveSuccessDialog(
        onStay: () => Navigator.pop(dialogContext),
        onViewHistory: () {
          Navigator.pop(dialogContext);
          context.read<AssessmentProvider>().reset();
          Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(
              builder: (_) =>
                  const HomeScreen(initialTab: HomeScreen.historyTab),
            ),
            (_) => false,
          );
        },
      ),
    );
  }

  int? get _assessmentAgeDays {
    final completedAt = widget.completedAt?.toLocal();
    if (completedAt == null) return null;
    final today = DateUtils.dateOnly(DateTime.now());
    final completedDate = DateUtils.dateOnly(completedAt);

    final days = today.difference(completedDate).inDays;

    return days < 0 ? 0 : days;
  }

  Future<void> _openTracking() async {
    if (!_saved) return;

    final ageDays = _assessmentAgeDays;
    if (!_trackingStarted) {
      final isOldAssessment = ageDays != null && ageDays > 7;
      final action = await showDialog<_OldAssessmentAction>(
        context: context,
        builder: (dialogContext) => _StartTrackingDialog(
          ageDays: ageDays,
          showReassessOption: isOldAssessment,
        ),
      );
      if (!mounted || action == null) return;
      if (action == _OldAssessmentAction.reassess) {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (_) => AssessmentScreen(
              symptomId: widget.symptomId,
              symptomName: widget.symptomName,
            ),
          ),
        );
        return;
      }
    }

    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => FollowUpScreen(
          assessmentId: widget.assessmentId,
          symptomName: widget.symptomName,
        ),
      ),
    );
    if (mounted) setState(() => _trackingStarted = true);
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
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        automaticallyImplyLeading: widget.isHistory,
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Text('ผลการประเมินสุขภาพ', style: AppTextStyles.h4),
        centerTitle: true,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(0.5),
          child: Divider(
            height: 0.5,
            thickness: 0.5,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: RefreshIndicator(
        color: AppColors.primary,
        backgroundColor: Theme.of(context).colorScheme.surface,
        elevation: 0,
        onRefresh: _refresh,
        child: AppContentWidth(
          maxWidth: 720,
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 40),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _AssessmentCompleteHeader(symptomName: symptomName),
                const SizedBox(height: 14),
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
                        color: Theme.of(
                          context,
                        ).colorScheme.surfaceContainerLow,
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
                        'อ่านข้อมูลเพิ่มเติม',
                        style: AppTextStyles.h4,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  'ดูข้อมูลโรคหรือภาวะที่ผลคัดกรองระบุว่าอาจเกี่ยวข้อง เพื่อทำความเข้าใจอาการและการดูแลเบื้องต้น',
                  style: AppTextStyles.body1.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
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
                    style: AppTextStyles.body3.copyWith(
                      color: AppColors.danger,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ],
                if (!widget.isHistory) const SizedBox(height: 16),

                if (context.watch<AuthProvider>().isAuthenticated) ...[
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.surfaceContainerLow,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: AppColors.primary.withValues(alpha: 0.18),
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            CircleAvatar(
                              backgroundColor: Theme.of(
                                context,
                              ).colorScheme.surface,
                              child: Icon(
                                Icons.monitor_heart_outlined,
                                color: AppColors.primary,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'ติดตามการเปลี่ยนแปลงของอาการ',
                                    style: AppTextStyles.body1Bold,
                                  ),
                                  Text(
                                    'บันทึกอาการในแต่ละวันเพื่อดูว่าอาการดีขึ้น คงที่ หรือแย่ลง',
                                    style: AppTextStyles.body1.copyWith(
                                      color: Theme.of(
                                        context,
                                      ).colorScheme.onSurfaceVariant,
                                      height: 1.45,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Theme.of(context).colorScheme.surface,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            _assessmentAgeDays != null &&
                                    _assessmentAgeDays! > 7 &&
                                    !_trackingStarted
                                ? 'ผลประเมินนี้ทำไว้เมื่อ ${_assessmentAgeDays!} วันที่แล้ว ระบบจะให้คุณยืนยันว่าอาการยังเหมือนเดิมก่อนเริ่มติดตาม'
                                : _saved
                                ? 'เริ่มเมื่อพร้อมและหยุดติดตามได้ทุกเมื่อ คุณกลับมาดูบันทึกย้อนหลังได้จากหน้าแนวโน้มสุขภาพ'
                                : 'บันทึกผลประเมินไว้ในประวัติก่อน แล้วจึงเริ่มติดตามอาการได้',
                            style: AppTextStyles.body1.copyWith(
                              color: Theme.of(
                                context,
                              ).colorScheme.onSurfaceVariant,
                              height: 1.55,
                            ),
                          ),
                        ),
                        const SizedBox(height: 14),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed: !_saved ? null : _openTracking,
                            icon: const Icon(Icons.arrow_forward_rounded),
                            label: Text(
                              !_saved
                                  ? 'บันทึกผลลงประวัติก่อน'
                                  : _trackingStarted
                                  ? 'ดูการติดตามอาการ'
                                  : 'เริ่มติดตามอาการนี้',
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                ],

                if (nextDiagrams.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  _SectionHeader(title: 'ประเมินอาการที่เกี่ยวข้องต่อ'),
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
                        subtitle: Text(
                          diagram.promptText?.isNotEmpty == true
                              ? diagram.promptText!
                              : 'ทำแบบประเมินเพิ่มเติมสำหรับอาการที่เกี่ยวข้อง',
                          style: AppTextStyles.body1.copyWith(
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurfaceVariant,
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
                ],
                if (nextDiagrams.isNotEmpty) const SizedBox(height: 12),
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
      ),
    );
  }
}

enum _OldAssessmentAction { startTracking, reassess }

class _StartTrackingDialog extends StatelessWidget {
  const _StartTrackingDialog({
    required this.ageDays,
    required this.showReassessOption,
  });

  final int? ageDays;
  final bool showReassessOption;

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 420),
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(24, 26, 24, 18),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 58,
                height: 58,
                decoration: const BoxDecoration(
                  color: AppColors.primaryLight,
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.monitor_heart_outlined,
                  color: AppColors.primary,
                  size: 29,
                ),
              ),
              const SizedBox(height: 16),
              Text(
                showReassessOption
                    ? 'อาการยังเหมือนเดิมหรือไม่?'
                    : 'เริ่มติดตามอาการนี้',
                textAlign: TextAlign.center,
                style: AppTextStyles.h3,
              ),
              const SizedBox(height: 8),
              Text(
                showReassessOption
                    ? 'ผลประเมินนี้บันทึกไว้เมื่อ $ageDays วันที่แล้ว กรุณายืนยันก่อนนำมาเริ่มติดตาม'
                    : 'การติดตามจะเริ่มนับเป็นวันแรกตั้งแต่วันนี้',
                textAlign: TextAlign.center,
                style: AppTextStyles.body2.copyWith(
                  color: colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 18),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: colorScheme.surfaceContainerLow,
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(
                      Icons.fact_check_outlined,
                      color: AppColors.primary,
                      size: 22,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'หน้าถัดไปจะให้คุณบันทึก',
                            style: AppTextStyles.body2Bold,
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'อาการที่สังเกตได้ในวันนี้ ตอบคำถามติดตาม และเพิ่มบันทึกได้ตามต้องการ',
                            style: AppTextStyles.body3.copyWith(
                              color: colorScheme.onSurfaceVariant,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                height: 52,
                child: FilledButton.icon(
                  onPressed: () => Navigator.pop(
                    context,
                    _OldAssessmentAction.startTracking,
                  ),
                  icon: const Icon(Icons.arrow_forward_rounded),
                  label: Text(
                    showReassessOption
                        ? 'ยังเป็นอาการเดิม — เริ่มติดตาม'
                        : 'เริ่มติดตามอาการ',
                  ),
                ),
              ),
              if (showReassessOption) ...[
                const SizedBox(height: 10),
                SizedBox(
                  width: double.infinity,
                  height: 52,
                  child: OutlinedButton.icon(
                    onPressed: () =>
                        Navigator.pop(context, _OldAssessmentAction.reassess),
                    icon: const Icon(Icons.refresh_rounded),
                    label: const Text('อาการเปลี่ยนไป — ประเมินใหม่'),
                  ),
                ),
              ],
              const SizedBox(height: 6),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('ไว้ภายหลัง'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
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
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.primaryLight),
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
                      'AI ช่วยสรุปผลประเมิน',
                      style: AppTextStyles.body1Bold,
                    ),
                    Text(
                      guidance.cached
                          ? 'สรุปจากผลและคำตอบในการประเมินครั้งนี้'
                          : 'กำลังแสดงสรุปจากผลและคำตอบของคุณ',
                      style: AppTextStyles.body3.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
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
              color: AppColors.primaryLight.withValues(alpha: 0.45),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Text(
              _plainLanguageSummary(guidance.summary, urgencyLevel),
              style: AppTextStyles.body1.copyWith(height: 1.55),
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
              title: 'คำแนะนำในการดูแลตัวเอง',
              items: guidance.selfCare,
              color: AppColors.successText,
            ),
          if (guidance.warningSigns.isNotEmpty)
            _AiGuidanceSection(
              icon: Icons.warning_amber_rounded,
              title: 'เมื่อใดควรพบแพทย์',
              items: guidance.warningSigns,
              color: AppColors.danger,
            ),
          const SizedBox(height: 14),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surfaceContainer,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              guidance.disclaimer,
              style: AppTextStyles.body1.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
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
    final displayItems = _guidanceDisplayItems(items);

    return Padding(
      padding: const EdgeInsets.only(top: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 20, color: color),
              const SizedBox(width: 8),
              Expanded(child: Text(title, style: AppTextStyles.body1Bold)),
            ],
          ),
          const SizedBox(height: 8),
          ...displayItems.map(
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
                      decoration: BoxDecoration(
                        color: color,
                        shape: BoxShape.circle,
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      item,
                      style: AppTextStyles.body1.copyWith(height: 1.5),
                    ),
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

class _EmptyResultCard extends StatelessWidget {
  const _EmptyResultCard();

  @override
  Widget build(BuildContext context) => Container(
    width: double.infinity,
    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 22),
    decoration: BoxDecoration(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
    ),
    child: Row(
      children: [
        Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surfaceContainerLow,
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

class _AssessmentCompleteHeader extends StatelessWidget {
  final String symptomName;

  const _AssessmentCompleteHeader({required this.symptomName});

  @override
  Widget build(BuildContext context) => Semantics(
    container: true,
    label: 'ประเมินเสร็จแล้ว${symptomName.isEmpty ? '' : ' $symptomName'}',
    child: Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.primaryLight,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          const Icon(
            Icons.check_circle_rounded,
            color: AppColors.primary,
            size: 22,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              symptomName.isEmpty
                  ? 'ประเมินเสร็จแล้ว กรุณาอ่านผลและคำแนะนำด้านล่าง'
                  : 'ประเมิน “$symptomName” เสร็จแล้ว',
              style: AppTextStyles.body2Bold.copyWith(
                color: AppColors.primary,
                height: 1.4,
              ),
            ),
          ),
        ],
      ),
    ),
  );
}

class _SaveSuccessDialog extends StatelessWidget {
  final VoidCallback onStay;
  final VoidCallback onViewHistory;

  const _SaveSuccessDialog({required this.onStay, required this.onViewHistory});

  @override
  Widget build(BuildContext context) {
    return AppActionDialog(
      icon: Icons.check_rounded,
      iconColor: AppColors.successText,
      iconBackgroundColor: AppColors.success.withValues(alpha: 0.14),
      title: 'บันทึกเรียบร้อยแล้ว',
      message:
          'ผลการประเมินถูกเก็บไว้ในประวัติแล้ว\nคุณสามารถกลับมาดูได้ทุกเมื่อ',
      primaryLabel: 'ดูประวัติการประเมิน',
      primaryIcon: Icons.history_rounded,
      onPrimary: onViewHistory,
      secondaryLabel: 'อยู่หน้านี้ต่อ',
      onSecondary: onStay,
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
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surfaceContainerLow,
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
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'ผลนี้เป็นเพียงการคัดกรองเบื้องต้น ไม่ใช่การวินิจฉัยโรค หากอาการรุนแรงขึ้นหรือไม่แน่ใจ ควรพบแพทย์',
                  style: AppTextStyles.body1.copyWith(
                    color: Theme.of(context).colorScheme.onSurface,
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
        color: AppColors.primary.withValues(alpha: 0.08),
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
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.surfaceContainerLow,
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
                        color: Theme.of(context).colorScheme.onSurface,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Text(
            timeFrame.isEmpty ? action : '$action $timeFrame',
            style: AppTextStyles.h4.copyWith(
              color: emphasisColor,
              height: 1.35,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            requiresMedicalCare
                ? 'จากคำตอบของคุณ ควรให้แพทย์ตรวจประเมินเพิ่มเติมเพื่อหาสาเหตุที่ชัดเจน ผลนี้ยังไม่ใช่การวินิจฉัยโรค'
                : 'จากคำตอบของคุณ ยังไม่พบสัญญาณที่ต้องรับการดูแลเร่งด่วน หากอาการเปลี่ยนแปลงควรประเมินใหม่',
            style: AppTextStyles.body1.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
              height: 1.55,
            ),
          ),
          if (symptomName.isNotEmpty) ...[
            const SizedBox(height: 10),
            Text(
              'อาการที่ประเมิน : $symptomName',
              style: AppTextStyles.body1.copyWith(
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
          ],
          if (showRecommendation && plainRecommendation.isNotEmpty) ...[
            const SizedBox(height: 14),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surfaceContainer,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(
                    Icons.task_alt_rounded,
                    color: AppColors.primary,
                    size: 20,
                  ),
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
          side: BorderSide(color: Theme.of(context).colorScheme.outlineVariant),
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
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurfaceVariant,
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
                        Divider(
                          height: 1,
                          color: Theme.of(context).colorScheme.outlineVariant,
                        ),
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
  final bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    final disease = widget.disease;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
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
