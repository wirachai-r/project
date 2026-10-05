import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../data/models/adaptive_assessment_model.dart';
import '../../../data/repositories/adaptive_assessment_repository.dart';
import '../../../data/repositories/assessment_repository.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../shared/widgets/app_layout.dart';
import '../widgets/assessment_progress.dart';
import '../../home/screens/home_screen.dart';
import 'adaptive_result_screen.dart';
import 'assessment_result_screen.dart';

class AdaptiveAssessmentScreen extends StatefulWidget {
  final String symptomId;
  final String symptomName;
  const AdaptiveAssessmentScreen({
    super.key,
    required this.symptomId,
    required this.symptomName,
  });

  @override
  State<AdaptiveAssessmentScreen> createState() =>
      _AdaptiveAssessmentScreenState();
}

class _AdaptiveAssessmentScreenState extends State<AdaptiveAssessmentScreen> {
  dynamic _id;
  AdaptiveQuestionModel? _question;
  String? _selected;
  final Set<int> _selectedOptionIds = {};
  bool _loading = true;
  String? _error;
  final List<({AdaptiveQuestionModel question, String answer})> _history = [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _start());
  }

  Future<void> _start() async {
    try {
      final response = await context.read<AdaptiveAssessmentRepository>().start(
        widget.symptomId,
      );
      if (!mounted) return;
      _id = response.id;
      if (response.question == null)
        return _showResult(response.results, response.historyAssessmentId);
      setState(() {
        _question = response.question;
        _loading = false;
      });
    } catch (_) {
      if (mounted)
        setState(() {
          _error = 'ไม่สามารถเริ่มการประเมินได้ กรุณาลองใหม่';
          _loading = false;
        });
    }
  }

  Future<void> _submit() async {
    final question = _question;
    final answer = _selected;
    if (question == null) return;
    final usesOptions = question.answerType != 'yes_no_unsure';
    if ((!usesOptions && answer == null) ||
        (usesOptions && _selectedOptionIds.isEmpty))
      return;
    setState(() => _loading = true);
    try {
      final response = await context
          .read<AdaptiveAssessmentRepository>()
          .answer(
            _id,
            question,
            answer: usesOptions ? null : answer,
            optionIds: _selectedOptionIds.toList(),
          );
      if (!mounted) return;
      if (response.question == null)
        return _showResult(response.results, response.historyAssessmentId);
      setState(() {
        _history.add((
          question: question,
          answer: usesOptions
              ? question.options
                    .where(
                      (item) =>
                          item.id != null &&
                          _selectedOptionIds.contains(item.id),
                    )
                    .map((item) => item.text)
                    .join(', ')
              : answer!,
        ));
        _question = response.question;
        _selected = null;
        _selectedOptionIds.clear();
        _loading = false;
      });
    } catch (_) {
      if (mounted)
        setState(() {
          _error = 'ส่งคำตอบไม่สำเร็จ กรุณาลองใหม่';
          _loading = false;
        });
    }
  }

  Future<void> _goBack() async {
    final question = _question;
    if (_loading) return;
    if (_id == null || question == null || question.number <= 1) {
      await _backToSymptomSelection();
      return;
    }

    setState(() => _loading = true);
    try {
      final previous = await context.read<AdaptiveAssessmentRepository>().back(
        _id,
      );
      if (!mounted) return;
      final previousEntry = _history.isNotEmpty ? _history.removeLast() : null;
      setState(() {
        _question = previous;
        _selected = previous.answerType == 'yes_no_unsure'
            ? previousEntry?.answer
            : null;
        _selectedOptionIds.clear();
        _loading = false;
        _error = null;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<bool> _confirmBackToSymptoms() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AppActionDialog(
        icon: Icons.arrow_back_rounded,
        title: 'ย้อนกลับไปเลือกอาการ?',
        message:
            'หากย้อนกลับตอนนี้ คำตอบที่ทำไว้จะหายไป และคุณสามารถเลือกอาการเพื่อเริ่มประเมินใหม่ได้',
        primaryLabel: 'ย้อนกลับ',
        onPrimary: () => Navigator.pop(dialogContext, true),
        secondaryLabel: 'ทำแบบประเมินต่อ',
        onSecondary: () => Navigator.pop(dialogContext, false),
      ),
    );
    return confirmed ?? false;
  }

  Future<void> _backToSymptomSelection() async {
    if (!await _confirmBackToSymptoms() || !mounted) return;
    try {
      if (_id != null)
        await context.read<AdaptiveAssessmentRepository>().abandon(_id);
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('ไม่สามารถยกเลิกการประเมินได้ กรุณาลองใหม่'),
          ),
        );
      }
      return;
    }
    if (mounted) Navigator.pop(context);
  }

  Future<void> _close() async {
    if (_loading) return;
    final shouldClose = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AppActionDialog(
        icon: Icons.exit_to_app_rounded,
        iconColor: AppColors.danger,
        iconBackgroundColor: AppColors.surfaceDanger,
        title: 'ยืนยันออกจากการประเมิน',
        message:
            'หากออกตอนนี้ คำตอบที่ทำไว้จะหายไป และต้องเริ่มประเมินใหม่ทั้งหมด ต้องการออกหรือไม่?',
        primaryLabel: 'ออกจากการประเมิน',
        primaryColor: AppColors.danger,
        onPrimary: () => Navigator.pop(dialogContext, true),
        secondaryLabel: 'ยกเลิก',
        onSecondary: () => Navigator.pop(dialogContext, false),
      ),
    );
    if (shouldClose != true || !mounted) return;
    try {
      if (_id != null) {
        await context.read<AdaptiveAssessmentRepository>().abandon(_id);
      }
    } catch (_) {
      // Navigation must remain available even if the abandon request fails.
    }
    if (mounted) {
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => const HomeScreen()),
        (route) => false,
      );
    }
  }

  Future<void> _showResult(
    List<AdaptiveDiseaseResultModel> results,
    dynamic historyAssessmentId,
  ) async {
    if (historyAssessmentId != null) {
      try {
        final result = await context.read<AssessmentRepository>().getResult(
          historyAssessmentId,
        );
        if (!mounted) return;
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (_) => AssessmentResultScreen(
              assessmentId: historyAssessmentId,
              symptomId: widget.symptomId,
              symptomName: widget.symptomName,
              results: result.results,
              assessmentType: result.assessmentType,
              completedAt: result.completedAt,
            ),
          ),
        );
        return;
      } catch (_) {
        // Keep the adaptive result available even if loading the integrated
        // history representation fails temporarily.
      }
    }
    if (!mounted) return;
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(
        builder: (_) => AdaptiveResultScreen(
          assessmentId: _id,
          symptomName: widget.symptomName,
          results: results,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final q = _question;
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop && !_loading) _goBack();
      },
      child: Scaffold(
        appBar: AppBar(
          automaticallyImplyLeading: false,
          backgroundColor: Theme.of(context).scaffoldBackgroundColor,
          elevation: 0,
          surfaceTintColor: Colors.transparent,
          leading: IconButton(
            tooltip: q != null && q.number > 1 ? 'คำถามก่อนหน้า' : 'ย้อนกลับ',
            onPressed: _loading ? null : _goBack,
            icon: const Icon(Icons.arrow_back_rounded),
          ),
          title: const Text('ประเมินอาการ'),
          centerTitle: true,
          actions: [
            IconButton(
              tooltip: 'ปิดการประเมิน',
              onPressed: _loading ? null : _close,
              icon: const Icon(Icons.close_rounded),
            ),
          ],
          bottom: PreferredSize(
            preferredSize: const Size.fromHeight(0.5),
            child: Divider(
              height: 0.5,
              thickness: 0.5,
              color: Theme.of(context).colorScheme.outlineVariant,
            ),
          ),
        ),
        body: _error != null
            ? AppMessageView.error(
                title: 'เกิดข้อผิดพลาด',
                message: _error!,
                onAction: _start,
              )
            : q == null
            ? const AppLoadingView()
            : SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(24, 16, 24, 24),
                child: AppContentWidth(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      AssessmentProgress(
                        currentStep: 3,
                        title: 'คำถามที่ ${q.number}',
                        description:
                            'ระบบจะถามจนมีข้อมูลเพียงพอที่จะแยกภาวะที่อาจเกี่ยวข้อง',
                      ),
                      const SizedBox(height: 28),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 8,
                        ),
                        decoration: BoxDecoration(
                          color: AppColors.primaryLight,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          'คำถามหลัก',
                          style: AppTextStyles.body2Bold.copyWith(
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                      const SizedBox(height: 18),
                      Text(q.text, style: AppTextStyles.h4),
                      if (q.detail != null) ...[
                        const SizedBox(height: 8),
                        Text(
                          q.detail!,
                          style: AppTextStyles.body2.copyWith(
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurfaceVariant,
                          ),
                        ),
                      ],
                      const SizedBox(height: 28),
                      if (q.answerType == 'yes_no_unsure')
                        for (final option in const [
                          ('yes', 'ใช่', Icons.check_rounded),
                          ('no', 'ไม่ใช่', Icons.close_rounded),
                          (
                            'unsure',
                            'ไม่แน่ใจ',
                            Icons.chat_bubble_outline_rounded,
                          ),
                        ])
                          _AnswerTile(
                            value: option.$1,
                            label: option.$2,
                            icon: option.$3,
                            selected: _selected == option.$1,
                            onTap: () => setState(() => _selected = option.$1),
                          )
                      else
                        for (final option in q.options)
                          _AnswerTile(
                            value: option.value,
                            label: option.text,
                            icon: q.answerType == 'multiple_choice'
                                ? Icons.check_box_outlined
                                : Icons.radio_button_checked,
                            selected:
                                option.id != null &&
                                _selectedOptionIds.contains(option.id),
                            onTap: () => setState(() {
                              if (option.id == null) return;
                              if (q.answerType == 'single_choice') {
                                _selectedOptionIds
                                  ..clear()
                                  ..add(option.id!);
                              } else if (!_selectedOptionIds.add(option.id!)) {
                                _selectedOptionIds.remove(option.id);
                              }
                            }),
                          ),
                      if (_history.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        Text('คำถามก่อนหน้า', style: AppTextStyles.body2Bold),
                        const SizedBox(height: 10),
                        _PreviousAnswerCard(entry: _history.last),
                      ],
                    ],
                  ),
                ),
              ),
        bottomNavigationBar: _buildBottomBar(context, q),
      ),
    );
  }

  Widget _buildBottomBar(
    BuildContext context,
    AdaptiveQuestionModel? question,
  ) {
    if (question == null || _error != null) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.fromLTRB(24, 12, 24, 28),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        border: Border(
          top: BorderSide(color: Theme.of(context).colorScheme.outlineVariant),
        ),
      ),
      child: AppContentWidth(
        shrinkWrapHeight: true,
        child: SizedBox(
          width: double.infinity,
          child: AppButton(
            label: _loading ? 'กำลังประมวลผล...' : 'ตอบและไปต่อ',
            height: 52,
            onTap:
                _loading ||
                    (question.answerType == 'yes_no_unsure'
                        ? _selected == null
                        : _selectedOptionIds.isEmpty)
                ? null
                : _submit,
          ),
        ),
      ),
    );
  }
}

class _PreviousAnswerCard extends StatelessWidget {
  final ({AdaptiveQuestionModel question, String answer}) entry;

  const _PreviousAnswerCard({required this.entry});

  @override
  Widget build(BuildContext context) {
    final answerLabel = switch (entry.answer) {
      'yes' => 'ใช่',
      'no' => 'ไม่ใช่',
      'unsure' => 'ไม่แน่ใจ',
      _ => entry.answer,
    };

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: AppColors.primaryLight,
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(
              Icons.check_rounded,
              color: AppColors.primary,
              size: 18,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(entry.question.text, style: AppTextStyles.body1Bold),
                const SizedBox(height: 6),
                Text.rich(
                  TextSpan(
                    text: 'คำตอบของคุณ: ',
                    children: [
                      TextSpan(
                        text: answerLabel,
                        style: AppTextStyles.body2Bold.copyWith(
                          color: AppColors.primary,
                        ),
                      ),
                    ],
                  ),
                  style: AppTextStyles.body2,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _AnswerTile extends StatelessWidget {
  final String value;
  final String label;
  final IconData icon;
  final bool selected;
  final VoidCallback onTap;
  const _AnswerTile({
    required this.value,
    required this.label,
    required this.icon,
    required this.selected,
    required this.onTap,
  });
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: selected
              ? AppColors.primaryLight
              : Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: selected
                ? AppColors.primary
                : Theme.of(context).colorScheme.outlineVariant,
            width: selected ? 1.5 : 1,
          ),
        ),
        child: Row(
          children: [
            Icon(
              icon,
              color: selected
                  ? AppColors.primary
                  : Theme.of(context).colorScheme.onSurfaceVariant,
            ),
            const SizedBox(width: 14),
            Expanded(child: Text(label, style: AppTextStyles.body1Bold)),
            Icon(
              selected ? Icons.radio_button_checked : Icons.radio_button_off,
              color: selected
                  ? AppColors.primary
                  : Theme.of(context).colorScheme.outlineVariant,
            ),
          ],
        ),
      ),
    ),
  );
}
