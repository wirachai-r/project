import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../shared/widgets/app_layout.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../core/constants/api_constants.dart';
import '../../../shared/widgets/app_button.dart';
import '../../assessment/providers/assessment_provider.dart';
import '../../home/screens/home_screen.dart';
import '../../../data/models/assessment_model.dart';
import '../../../data/models/ai_assistance_model.dart';
import 'assessment_result_screen.dart';
import '../widgets/assessment_progress.dart';

class AssessmentScreen extends StatefulWidget {
  final String symptomId;
  final String? symptomName;
  final bool resumeExisting;

  const AssessmentScreen({
    super.key,
    required this.symptomId,
    this.symptomName,
    this.resumeExisting = false,
  });

  @override
  State<AssessmentScreen> createState() => _AssessmentScreenState();
}

class _AssessmentScreenState extends State<AssessmentScreen> {
  late AssessmentProvider _assessmentProvider;
  bool _showingClarification = false;
  AiClarificationChoice? _selectedClarificationChoice;
  int? _clarificationReviewIndex;
  AiClarificationChoice? _selectedReviewChoice;

  Future<void> _showClarification(
    AssessmentProvider provider, {
    bool nextRound = false,
  }) async {
    final box = provider.currentBox;
    if (!nextRound && box != null) {
      final history = provider.clarificationHistoryFor(box.boxId);
      if (history.isNotEmpty) {
        final firstEntry = history.first;
        setState(() {
          _showingClarification = false;
          _selectedClarificationChoice = null;
          _clarificationReviewIndex = 0;
          _selectedReviewChoice = _historySelectedChoice(firstEntry);
        });
        return;
      }
    }
    if (!nextRound && provider.clarification != null) {
      setState(() {
        _showingClarification = true;
        _selectedClarificationChoice = null;
      });
      return;
    }
    await provider.clarifyCurrentQuestion();
    if (!mounted || provider.clarification == null) return;
    setState(() {
      _showingClarification = true;
      _selectedClarificationChoice = null;
    });
  }

  void _closeClarification() {
    setState(() {
      _showingClarification = false;
      _selectedClarificationChoice = null;
    });
  }

  AiClarificationChoice? _historySelectedChoice(
    AiClarificationHistoryEntry entry,
  ) {
    for (final choice in entry.choices) {
      if (choice.id == entry.selectedChoiceId) return choice;
    }
    return null;
  }

  void _goBackWithinAssessment(AssessmentProvider provider) {
    final reviewIndex = _clarificationReviewIndex;
    if (_showingClarification) {
      _closeClarification();
      return;
    }
    if (reviewIndex != null) {
      final history = provider.clarificationHistoryFor(
        provider.currentBox!.boxId,
      );
      final nextIndex = reviewIndex > 0 ? reviewIndex - 1 : null;
      if (nextIndex == null) {
        provider.toggleChoice(
          provider.currentBox!.boxId,
          AssessmentProvider.uncertainChoiceId,
          false,
        );
      }
      setState(() {
        _clarificationReviewIndex = nextIndex;
        _selectedReviewChoice = nextIndex == null
            ? null
            : _historySelectedChoice(history[nextIndex]);
      });
      return;
    }
    if (!provider.canGoBack) return;
    final previousBox = provider.answeredBoxes.last;
    final history = provider.clarificationHistoryFor(previousBox.boxId);
    provider.goBack();
    if (history.isNotEmpty) {
      // Keep the user's original "uncertain" step in the back-navigation
      // trail even though the assessment engine received a mapped choice.
      provider.toggleChoice(
        previousBox.boxId,
        AssessmentProvider.uncertainChoiceId,
        false,
      );
    }
    final lastHistoryIndex = history.isEmpty ? null : history.length - 1;
    setState(() {
      _clarificationReviewIndex = lastHistoryIndex;
      _selectedReviewChoice = lastHistoryIndex == null
          ? null
          : _historySelectedChoice(history[lastHistoryIndex]);
      _showingClarification = false;
      _selectedClarificationChoice = null;
    });
  }

  Future<void> _submitClarification(AssessmentProvider provider) async {
    final choice = _selectedClarificationChoice;
    if (choice == null) return;
    final result = await provider.answerClarificationChoice(choice);
    if (!mounted || result == null) return;

    if (result.mapsToChoiceId != null) {
      provider.confirmClarificationChoice(
        result.mapsToChoiceId!,
        clearClarification: false,
      );
      await provider.submitAnswers();
      if (!mounted) return;
      setState(() {
        _showingClarification = false;
        _selectedClarificationChoice = null;
      });
      return;
    }

    if (result.canRetry) {
      await _showClarification(provider, nextRound: true);
      return;
    }

    if (result.status != 'unresolved') {
      await provider.markClarificationUnresolved();
    } else {
      provider.clearClarification(resetAttempts: true);
    }
    if (!mounted) return;
    setState(() {
      _showingClarification = false;
      _selectedClarificationChoice = null;
    });
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text(
          'ยังไม่สามารถสรุปคำถามนี้ได้ กรุณาเลือกคำตอบหลักหรือย้อนกลับ',
        ),
      ),
    );
  }

  Future<void> _submitHistoricalClarification(
    AssessmentProvider provider,
  ) async {
    final index = _clarificationReviewIndex;
    final choice = _selectedReviewChoice;
    if (index == null || choice == null) return;
    final history = provider.clarificationHistoryFor(
      provider.currentBox!.boxId,
    );
    final entry = history[index];
    final result = await provider.answerHistoricalClarificationChoice(
      entry,
      choice,
    );
    if (!mounted || result == null) return;
    if (result.mapsToChoiceId == null) {
      if (index < history.length - 1) {
        final nextEntry = history[index + 1];
        setState(() {
          _clarificationReviewIndex = index + 1;
          _selectedReviewChoice = _historySelectedChoice(nextEntry);
        });
      } else if (result.canRetry) {
        setState(() {
          _clarificationReviewIndex = null;
          _selectedReviewChoice = null;
        });
        await _showClarification(provider, nextRound: true);
      }
      return;
    }
    provider.confirmClarificationChoice(
      result.mapsToChoiceId!,
      clearClarification: false,
    );
    await provider.submitAnswers();
    if (!mounted) return;
    setState(() {
      _clarificationReviewIndex = null;
      _selectedReviewChoice = null;
    });
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      if (!widget.resumeExisting) {
        context.read<AssessmentProvider>().startAssessment(widget.symptomId);
      }
    });
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    // เก็บ reference ไว้ล่วงหน้า จะได้ไม่ต้อง context.read ตอน dispose
    // (context อาจไม่ปลอดภัยแล้วตอนนั้น เพราะ widget deactivate ไปแล้ว)
    _assessmentProvider = context.read<AssessmentProvider>();
  }

  @override
  void dispose() {
    // ใช้ disposeReset() แทน reset() เพราะไม่ต้อง notifyListeners()
    // ระหว่างที่ widget tree กำลังถูก unmount (ป้องกัน "tree was locked")
    _assessmentProvider.disposeReset();
    super.dispose();
  }

  // แสดง dialog ยืนยันก่อนออกจากการประเมิน
  Future<bool> _confirmExit(BuildContext context) async {
    final confirmed = await showDialog<bool>(
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
    return confirmed ?? false;
  }

  Future<bool> _confirmBack(BuildContext context) async {
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

  Future<void> _handleBackToSelection(BuildContext context) async {
    final shouldGoBack = await _confirmBack(context);
    if (!shouldGoBack || !context.mounted) return;

    final provider = context.read<AssessmentProvider>();
    final abandoned = await provider.abandonAssessment();
    if (!context.mounted) return;

    if (abandoned) {
      provider.reset();
      Navigator.pop(context);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(provider.error ?? 'กรุณาลองใหม่อีกครั้ง')),
      );
    }
  }

  Future<void> _handleClose(BuildContext context) async {
    final shouldExit = await _confirmExit(context);
    if (!shouldExit || !context.mounted) return;

    final provider = context.read<AssessmentProvider>();
    final abandoned = await provider.abandonAssessment();
    if (!context.mounted) return;

    if (abandoned) {
      provider.reset();
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => const HomeScreen()),
        (route) => false,
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(provider.error ?? 'กรุณาลองใหม่อีกครั้ง')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    final hp = Responsive.horizontalPadding;

    return Consumer<AssessmentProvider>(
      builder: (context, provider, _) {
        // ไปหน้าผลลัพธ์ทันทีที่ assessment เสร็จสิ้น
        if (provider.isCompleted) {
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (!mounted) return;
            Navigator.of(context).pushReplacement(
              MaterialPageRoute(
                builder: (_) => AssessmentResultScreen(
                  assessmentId: provider.assessmentId,
                  symptomId: widget.symptomId,
                  results: provider.results,
                  symptomName: widget.symptomName ?? '',
                ),
              ),
            );
          });
        }

        return PopScope(
          canPop: false,
          onPopInvokedWithResult: (didPop, result) async {
            if (didPop) return;
            if (_showingClarification ||
                _clarificationReviewIndex != null ||
                provider.canGoBack) {
              _goBackWithinAssessment(provider);
              return;
            }
            if (widget.resumeExisting) {
              await _handleClose(context);
            } else {
              await _handleBackToSelection(context);
            }
          },
          child: Scaffold(
            backgroundColor: Theme.of(context).scaffoldBackgroundColor,
            appBar: AppBar(
              automaticallyImplyLeading: false,
              backgroundColor: Theme.of(context).scaffoldBackgroundColor,
              elevation: 0,
              surfaceTintColor: Colors.transparent,
              leading:
                  widget.resumeExisting &&
                      !_showingClarification &&
                      _clarificationReviewIndex == null &&
                      !provider.canGoBack
                  ? null
                  : IconButton(
                      tooltip: 'ย้อนกลับ',
                      icon: Icon(
                        Icons.arrow_back_rounded,
                        color: Theme.of(context).colorScheme.onSurface,
                      ),
                      onPressed: provider.isClarifying || provider.isLoading
                          ? null
                          : () {
                              if (_showingClarification ||
                                  _clarificationReviewIndex != null ||
                                  provider.canGoBack) {
                                _goBackWithinAssessment(provider);
                              } else {
                                _handleBackToSelection(context);
                              }
                            },
                    ),
              title: Text('ประเมินอาการ', style: AppTextStyles.h4),
              centerTitle: true,
              actions: [
                IconButton(
                  tooltip: 'ออกจากการประเมิน',
                  icon: Icon(
                    Icons.close_rounded,
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                  onPressed: provider.isLoading
                      ? null
                      : () => _handleClose(context),
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
            body: _buildBody(context, provider, hp),
            bottomNavigationBar: _buildBottomBar(context, provider, hp),
          ),
        );
      },
    );
  }

  Widget _buildBody(
    BuildContext context,
    AssessmentProvider provider,
    double hp,
  ) {
    if (provider.isLoading && provider.currentBox == null) {
      return const AppLoadingView();
    }

    if (provider.error != null && provider.currentBox == null) {
      return AppMessageView.error(
        message: provider.error!,
        onAction: () => provider.startAssessment(widget.symptomId),
      );
    }

    final box = provider.currentBox;
    if (box == null) return const SizedBox.shrink();

    if (_showingClarification && provider.clarification != null) {
      return _buildClarificationBody(provider, hp);
    }

    if (_clarificationReviewIndex != null) {
      return _buildClarificationReview(provider, hp);
    }

    final selected = provider.selectedChoicesFor(box.boxId);

    return SingleChildScrollView(
      padding: EdgeInsets.fromLTRB(hp, 16, hp, 16),
      child: AppContentWidth(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            AssessmentProgress(
              currentStep: 3,
              showDetails: false,
              title: widget.symptomName?.isNotEmpty == true
                  ? 'ประเมินอาการ ${widget.symptomName}'
                  : 'ตอบคำถามเกี่ยวกับอาการ',
              description:
                  'เลือกคำตอบที่ตรงกับอาการในขณะนี้มากที่สุด เพื่อช่วยคัดกรองเบื้องต้น',
            ),
            SizedBox(height: Responsive.dp(24)),
            if (provider.answeredBoxes.isNotEmpty) ...[
              Text('คำถามก่อนหน้า', style: AppTextStyles.body2Bold),
              SizedBox(height: Responsive.dp(10)),
              if (provider
                      .selectedChoicesFor(provider.answeredBoxes.last.boxId)
                      .contains(AssessmentProvider.uncertainChoiceId) &&
                  provider
                      .clarificationHistoryFor(
                        provider.answeredBoxes.last.boxId,
                      )
                      .isNotEmpty)
                _ClarificationHistoryCard(
                  entry: provider
                      .clarificationHistoryFor(
                        provider.answeredBoxes.last.boxId,
                      )
                      .last,
                )
              else
                _AnsweredQuestionCard(
                  box: provider.answeredBoxes.last,
                  selectedChoiceIds: provider.selectedChoicesFor(
                    provider.answeredBoxes.last.boxId,
                  ),
                ),
              SizedBox(height: Responsive.dp(8)),
            ],
            Row(
              children: [
                Container(
                  padding: EdgeInsets.symmetric(
                    horizontal: Responsive.dp(10),
                    vertical: Responsive.dp(6),
                  ),
                  decoration: BoxDecoration(
                    color: AppColors.primaryLight,
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(
                        Icons.assignment_outlined,
                        size: 16,
                        color: AppColors.primary,
                      ),
                      SizedBox(width: Responsive.dp(6)),
                      Text(
                        'คำถามหลัก',
                        style: AppTextStyles.body3Bold.copyWith(
                          color: AppColors.primary,
                        ),
                      ),
                    ],
                  ),
                ),
                const Spacer(),
                Text(
                  selected.isEmpty ? 'เลือกคำตอบเพื่อไปต่อ' : 'เลือกแล้ว',
                  style: AppTextStyles.body2.copyWith(
                    color: selected.isEmpty
                        ? Theme.of(context).colorScheme.onSurfaceVariant
                        : AppColors.success,
                  ),
                ),
              ],
            ),
            SizedBox(height: Responsive.dp(10)),
            if (box.isMultiple)
              Container(
                margin: EdgeInsets.only(bottom: Responsive.dp(10)),
                padding: EdgeInsets.symmetric(
                  horizontal: Responsive.dp(10),
                  vertical: Responsive.dp(6),
                ),
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Text(
                  'เลือกได้มากกว่า 1 ข้อ',
                  style: AppTextStyles.body3Bold.copyWith(
                    color: AppColors.primary,
                  ),
                ),
              ),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // _QuestionIcon(imageUrl: box.questionImage),
                // SizedBox(width: Responsive.dp(12)),
                Expanded(
                  child: Text(
                    box.questionText,
                    style: AppTextStyles.h4.copyWith(height: 1.4),
                  ),
                ),
              ],
            ),
            if (box.detail?.trim().isNotEmpty == true) ...[
              SizedBox(height: Responsive.dp(10)),
              Container(
                width: double.infinity,
                padding: EdgeInsets.all(Responsive.dp(12)),
                decoration: BoxDecoration(
                  color: AppColors.primaryLight.withValues(alpha: 0.45),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.primaryLight),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(
                      Icons.info_outline_rounded,
                      color: AppColors.primary,
                      size: 20,
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        box.detail!.trim(),
                        style: AppTextStyles.body2.copyWith(
                          color: Theme.of(context).colorScheme.onSurface,
                          height: 1.55,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
            SizedBox(
              height: Responsive.dp(
                box.detail?.trim().isNotEmpty == true ? 14 : 24,
              ),
            ),
            ...box.choices.map(
              (choice) => _ChoiceItem(
                choice: choice,
                selected: selected.contains(choice.choiceId),
                onTap: () => provider.toggleChoice(
                  box.boxId,
                  choice.choiceId,
                  box.isMultiple,
                ),
              ),
            ),
            if (!box.isMultiple &&
                !provider.clarificationExhaustedFor(box.boxId))
              _ChoiceItem(
                choice: const AnswerChoiceModel(
                  choiceId: AssessmentProvider.uncertainChoiceId,
                  choiceText: 'ไม่แน่ใจ',
                  order: 999999,
                ),
                selected: selected.contains(
                  AssessmentProvider.uncertainChoiceId,
                ),
                onTap: () => provider.toggleChoice(
                  box.boxId,
                  AssessmentProvider.uncertainChoiceId,
                  false,
                ),
              ),
            if (box.isMultiple && box.minRequired == 1)
              _ChoiceItem(
                choice: const AnswerChoiceModel(
                  choiceId: AssessmentProvider.noneChoiceId,
                  choiceText: 'ไม่ใช่ทั้งหมด',
                  order: 999999,
                ),
                selected: selected.contains(AssessmentProvider.noneChoiceId),
                onTap: () => provider.toggleChoice(
                  box.boxId,
                  AssessmentProvider.noneChoiceId,
                  true,
                ),
              ),
            if (provider.error != null) ...[
              SizedBox(height: Responsive.dp(12)),
              Text(
                provider.error!,
                style: AppTextStyles.body3.copyWith(color: AppColors.danger),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildClarificationReview(AssessmentProvider provider, double hp) {
    final box = provider.currentBox!;
    final history = provider.clarificationHistoryFor(box.boxId);
    final index = _clarificationReviewIndex!;
    if (index >= history.length) return const SizedBox.shrink();
    final entry = history[index];
    return SingleChildScrollView(
      padding: EdgeInsets.fromLTRB(hp, 16, hp, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('คำถามก่อนหน้า', style: AppTextStyles.body2Bold),
          SizedBox(height: Responsive.dp(10)),
          if (index == 0)
            _AnsweredQuestionCard(
              box: box,
              selectedChoiceIds: const [AssessmentProvider.uncertainChoiceId],
            )
          else
            _ClarificationHistoryCard(entry: history[index - 1]),
          SizedBox(height: Responsive.dp(20)),
          Container(
            padding: EdgeInsets.symmetric(
              horizontal: Responsive.dp(10),
              vertical: Responsive.dp(6),
            ),
            decoration: BoxDecoration(
              color: AppColors.primaryLight,
              borderRadius: BorderRadius.circular(16),
            ),
            child: Text(
              'ทบทวนคำถามช่วย รอบ ${entry.attempt}/${history.length}',
              style: AppTextStyles.body3Bold.copyWith(color: AppColors.primary),
            ),
          ),
          SizedBox(height: Responsive.dp(20)),
          Text(entry.questionText, style: AppTextStyles.h4),
          SizedBox(height: Responsive.dp(24)),
          ...entry.choices.asMap().entries.map(
            (item) => _ClarificationChoiceItem(
              choice: item.value,
              choiceIndex: item.key,
              selected: _selectedReviewChoice?.id == item.value.id,
              onTap: () => setState(() => _selectedReviewChoice = item.value),
            ),
          ),
          SizedBox(height: Responsive.dp(12)),
          Text(
            'กดย้อนกลับเพื่อดูคำถามช่วยรอบก่อนหน้า',
            style: AppTextStyles.body3.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildClarificationBody(AssessmentProvider provider, double hp) {
    final clarification = provider.clarification!;
    final box = provider.currentBox!;
    final history = provider.clarificationHistoryFor(box.boxId);
    return SingleChildScrollView(
      padding: EdgeInsets.fromLTRB(hp, 16, hp, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('คำถามก่อนหน้า', style: AppTextStyles.body2Bold),
          SizedBox(height: Responsive.dp(10)),
          if (history.isEmpty)
            _AnsweredQuestionCard(
              box: box,
              selectedChoiceIds: const [AssessmentProvider.uncertainChoiceId],
            )
          else
            _ClarificationHistoryCard(entry: history.last),
          SizedBox(height: Responsive.dp(20)),
          Row(
            children: [
              Container(
                padding: EdgeInsets.symmetric(
                  horizontal: Responsive.dp(10),
                  vertical: Responsive.dp(6),
                ),
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Text(
                  'คำถามช่วย รอบ ${clarification.attempt}/${clarification.maxAttempts}',
                  style: AppTextStyles.body3Bold.copyWith(
                    color: AppColors.primary,
                  ),
                ),
              ),
              const Spacer(),
              Text(
                _selectedClarificationChoice == null
                    ? 'เลือกคำตอบเพื่อไปต่อ'
                    : 'เลือกแล้ว',
                style: AppTextStyles.body2.copyWith(
                  color: _selectedClarificationChoice == null
                      ? Theme.of(context).colorScheme.onSurfaceVariant
                      : AppColors.success,
                ),
              ),
            ],
          ),
          SizedBox(height: Responsive.dp(18)),
          Text(clarification.questionText, style: AppTextStyles.h4),
          SizedBox(height: Responsive.dp(8)),
          Text(
            clarification.explanation,
            style: AppTextStyles.body2.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
          SizedBox(height: Responsive.dp(24)),
          ...clarification.choices.asMap().entries.map(
            (item) => _ClarificationChoiceItem(
              choice: item.value,
              choiceIndex: item.key,
              selected: _selectedClarificationChoice?.id == item.value.id,
              onTap: () =>
                  setState(() => _selectedClarificationChoice = item.value),
            ),
          ),
          SizedBox(height: Responsive.dp(8)),
          Text(
            'คำตอบนี้ถูกเก็บแยก และจะส่งเข้าแผนภูมิเฉพาะเมื่อจับคู่กับคำตอบหลักได้',
            style: AppTextStyles.body3.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomBar(
    BuildContext context,
    AssessmentProvider provider,
    double hp,
  ) {
    final box = provider.currentBox;
    if (box == null) return const SizedBox.shrink();

    final reviewing = _clarificationReviewIndex != null;
    final hasSelection = reviewing
        ? _selectedReviewChoice != null
        : _showingClarification
        ? _selectedClarificationChoice != null
        : provider.selectedChoicesFor(box.boxId).isNotEmpty;
    final loading = provider.isLoading || provider.isClarifying;

    return Container(
      padding: EdgeInsets.fromLTRB(hp, 12, hp, 28),
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
            label: loading ? 'กำลังประมวลผล...' : 'ตอบและไปต่อ',
            height: 52,
            onTap: (!hasSelection || loading)
                ? null
                : () async {
                    if (reviewing) {
                      await _submitHistoricalClarification(provider);
                    } else if (_showingClarification) {
                      await _submitClarification(provider);
                    } else if (provider
                        .selectedChoicesFor(box.boxId)
                        .contains(AssessmentProvider.uncertainChoiceId)) {
                      await _showClarification(provider);
                    } else {
                      await provider.submitAnswers();
                    }
                  },
          ),
        ),
      ),
    );
  }
}

class _ClarificationChoiceItem extends StatelessWidget {
  final AiClarificationChoice choice;
  final int choiceIndex;
  final bool selected;
  final VoidCallback onTap;

  const _ClarificationChoiceItem({
    required this.choice,
    required this.choiceIndex,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Semantics(
      button: true,
      selected: selected,
      label: choice.label,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        hoverColor: Colors.transparent,
        splashColor: Colors.transparent,
        highlightColor: Colors.transparent,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 150),
          width: double.infinity,
          margin: EdgeInsets.only(bottom: Responsive.dp(8)),
          padding: EdgeInsets.symmetric(
            horizontal: Responsive.dp(14),
            vertical: Responsive.dp(10),
          ),
          decoration: BoxDecoration(
            color: selected
                ? AppColors.primaryLight
                : Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: selected
                  ? AppColors.primary
                  : Theme.of(context).colorScheme.outlineVariant,
              width: selected ? 1.5 : 1,
            ),
          ),
          child: Row(
            children: [
              Container(
                width: 34,
                height: 34,
                decoration: BoxDecoration(
                  color: selected
                      ? AppColors.primary
                      : Theme.of(context).colorScheme.surfaceContainer,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  choiceIndex == 0
                      ? Icons.check_rounded
                      : choiceIndex == 1
                      ? Icons.close_rounded
                      : Icons.chat_bubble_outline_rounded,
                  color: selected
                      ? AppColors.white
                      : Theme.of(context).colorScheme.onSurfaceVariant,
                  size: 18,
                ),
              ),
              SizedBox(width: Responsive.dp(10)),
              Expanded(
                child: Text(
                  choice.label,
                  style: AppTextStyles.body2Bold.copyWith(
                    color: selected
                        ? AppColors.primary
                        : Theme.of(context).colorScheme.onSurface,
                  ),
                ),
              ),
              AnimatedContainer(
                duration: const Duration(milliseconds: 150),
                width: 20,
                height: 20,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: selected
                        ? AppColors.primary
                        : Theme.of(context).colorScheme.outlineVariant,
                    width: 1.5,
                  ),
                  color: selected ? AppColors.primary : Colors.transparent,
                ),
                child: selected
                    ? const Icon(Icons.check, color: AppColors.white, size: 14)
                    : null,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _AnsweredQuestionCard extends StatelessWidget {
  final QuestionBoxModel box;
  final List<String> selectedChoiceIds;

  const _AnsweredQuestionCard({
    required this.box,
    required this.selectedChoiceIds,
  });

  @override
  Widget build(BuildContext context) {
    final selectedAnswers = selectedChoiceIds
        .map((choiceId) {
          if (choiceId == AssessmentProvider.noneChoiceId) {
            return 'ไม่ใช่ทั้งหมด';
          }
          if (choiceId == AssessmentProvider.uncertainChoiceId) {
            return 'ไม่แน่ใจ';
          }
          for (final choice in box.choices) {
            if (choice.choiceId == choiceId) return choice.choiceText;
          }
          return null;
        })
        .whereType<String>()
        .toList();

    return Container(
      width: double.infinity,
      margin: EdgeInsets.only(bottom: Responsive.dp(10)),
      padding: EdgeInsets.all(Responsive.dp(14)),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 24,
                height: 24,
                decoration: const BoxDecoration(
                  color: AppColors.primaryLight,
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.check_rounded,
                  size: 16,
                  color: AppColors.primary,
                ),
              ),
              SizedBox(width: Responsive.dp(10)),
              Expanded(
                child: Text(
                  box.questionText,
                  style: AppTextStyles.body2Bold.copyWith(height: 1.45),
                ),
              ),
            ],
          ),
          SizedBox(height: Responsive.dp(8)),
          Padding(
            padding: EdgeInsets.only(left: Responsive.dp(34)),
            child: Text.rich(
              TextSpan(
                children: [
                  TextSpan(
                    text: 'คำตอบของคุณ: ',
                    style: AppTextStyles.body3.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                  ),
                  TextSpan(
                    text: selectedAnswers.join(', '),
                    style: AppTextStyles.body3Bold.copyWith(
                      color: AppColors.primary,
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

class _ClarificationHistoryCard extends StatelessWidget {
  final AiClarificationHistoryEntry entry;

  const _ClarificationHistoryCard({required this.entry});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: EdgeInsets.all(Responsive.dp(14)),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('คำถามช่วยรอบ ${entry.attempt}', style: AppTextStyles.body2Bold),
          SizedBox(height: Responsive.dp(10)),
          Text(
            entry.questionText,
            style: AppTextStyles.body3.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
          SizedBox(height: Responsive.dp(6)),
          Text(
            'คำตอบของคุณ: ${entry.answerText}',
            style: AppTextStyles.body3Bold.copyWith(color: AppColors.primary),
          ),
        ],
      ),
    );
  }
}

class _ChoiceItem extends StatelessWidget {
  final AnswerChoiceModel choice;
  final bool selected;
  final VoidCallback onTap;

  const _ChoiceItem({
    required this.choice,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Semantics(
      button: true,
      selected: selected,
      label: choice.choiceText,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        hoverColor: Colors.transparent,
        splashColor: Colors.transparent,
        highlightColor: Colors.transparent,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 150),
          margin: EdgeInsets.only(bottom: Responsive.dp(8)),
          padding: EdgeInsets.symmetric(
            horizontal: Responsive.dp(14),
            vertical: Responsive.dp(10),
          ),
          decoration: BoxDecoration(
            color: selected
                ? AppColors.primaryLight
                : Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(14),
            border: selected
                ? Border.all(color: AppColors.primary, width: 1.5)
                : Border.all(
                    color: Theme.of(context).colorScheme.outlineVariant,
                    width: 1,
                  ),
          ),
          child: Row(
            children: [
              _ChoiceIcon(choice: choice, selected: selected),
              SizedBox(width: Responsive.dp(10)),
              Expanded(
                child: Text(
                  choice.choiceText,
                  style: AppTextStyles.body2Bold.copyWith(
                    color: selected
                        ? AppColors.primary
                        : Theme.of(context).colorScheme.onSurface,
                  ),
                ),
              ),
              AnimatedContainer(
                duration: const Duration(milliseconds: 150),
                width: 20,
                height: 20,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: selected
                        ? AppColors.primary
                        : Theme.of(context).colorScheme.outlineVariant,
                    width: 1.5,
                  ),
                  color: selected ? AppColors.primary : Colors.transparent,
                ),
                child: selected
                    ? const Icon(Icons.check, color: AppColors.white, size: 14)
                    : null,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ChoiceIcon extends StatelessWidget {
  final AnswerChoiceModel choice;
  final bool selected;

  const _ChoiceIcon({required this.choice, required this.selected});

  @override
  Widget build(BuildContext context) {
    final imageUrl = choice.choiceImage;
    final normalized = choice.choiceText.trim().toLowerCase();
    final icon = normalized == 'ใช่' || normalized == 'yes'
        ? Icons.check_rounded
        : normalized == 'ไม่' || normalized == 'ไม่ใช่' || normalized == 'no'
        ? Icons.close_rounded
        : Icons.chat_bubble_outline_rounded;

    return Container(
      width: 34,
      height: 34,
      decoration: BoxDecoration(
        color: selected
            ? AppColors.primary
            : Theme.of(context).colorScheme.surfaceContainer,
        borderRadius: BorderRadius.circular(10),
      ),
      clipBehavior: Clip.antiAlias,
      child: imageUrl?.trim().isNotEmpty == true
          ? Image.network(
              _resolveImageUrl(imageUrl!),
              fit: BoxFit.cover,
              errorBuilder: (_, _, _) => Icon(
                icon,
                color: selected
                    ? AppColors.white
                    : Theme.of(context).colorScheme.onSurfaceVariant,
                size: 18,
              ),
            )
          : Icon(
              icon,
              color: selected
                  ? AppColors.white
                  : Theme.of(context).colorScheme.onSurfaceVariant,
              size: 18,
            ),
    );
  }
}

String _resolveImageUrl(String value) {
  final trimmed = value.trim();
  final uri = Uri.tryParse(trimmed);
  if (uri?.hasScheme == true) return trimmed;

  final apiUri = Uri.parse(ApiConstants.baseUrl);
  return apiUri
      .replace(path: trimmed.startsWith('/') ? trimmed : '/$trimmed')
      .toString();
}
