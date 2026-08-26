import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../core/constants/api_constants.dart';
import '../../../shared/widgets/app_button.dart';
import '../../assessment/providers/assessment_provider.dart';
import '../../home/screens/home_screen.dart';
import '../../../data/models/assessment_model.dart';
import 'assessment_result_screen.dart';

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

  // แสดง dialog ยืนยันก่อนออกจากการประเมิน (กดปิด หรือ system back)
  Future<bool> _confirmExit(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('ยืนยันออกจากการประเมิน', style: AppTextStyles.h4),
        content: Text(
          'หากออกตอนนี้ คำตอบที่ทำไว้จะหายไป และต้องเริ่มประเมินใหม่ทั้งหมด ต้องการออกหรือไม่?',
          style: AppTextStyles.body2,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(
              'ยกเลิก',
              style: AppTextStyles.body2.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            // style: TextButton.styleFrom(foregroundColor: AppColors.danger),
            child: Text(
              'ออก',
              style: AppTextStyles.body1Bold.copyWith(color: AppColors.danger),
            ),
          ),
        ],
      ),
    );
    return confirmed ?? false;
  }

  Future<void> _handleClose(BuildContext context) async {
    final shouldExit = await _confirmExit(context);
    if (shouldExit && context.mounted) {
      context.read<AssessmentProvider>().reset();
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => const HomeScreen()),
        (route) => false,
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
            final shouldExit = await _confirmExit(context);
            if (shouldExit && context.mounted) {
              Navigator.pop(context);
            }
          },
          child: Scaffold(
            backgroundColor: AppColors.background,
            appBar: AppBar(
              automaticallyImplyLeading: false,
              backgroundColor: AppColors.white,
              elevation: 0,
              surfaceTintColor: Colors.transparent,
              // ปุ่มย้อนกลับไปคำถามก่อนหน้า (ภายใน assessment เดียวกัน)
              leading: provider.canGoBack
                  ? IconButton(
                      icon: const Icon(
                        Icons.arrow_back,
                        color: AppColors.textPrimary,
                      ),
                      onPressed: provider.isLoading
                          ? null
                          : () => provider.goBack(),
                    )
                  : null,
              title: Text('ประเมินอาการ', style: AppTextStyles.h4),
              centerTitle: true,
              actions: [
                Padding(
                  padding: EdgeInsets.only(right: hp),
                  child: IconButton(
                    icon: const Icon(Icons.close, color: AppColors.textPrimary),
                    onPressed: () => _handleClose(context),
                  ),
                ),
              ],
              bottom: PreferredSize(
                preferredSize: const Size.fromHeight(0.5),
                child: Divider(
                  height: 0.5,
                  thickness: 0.5,
                  color: AppColors.border,
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
      return Center(
        child: Padding(
          padding: EdgeInsets.symmetric(horizontal: hp),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(
                Icons.error_outline,
                color: AppColors.danger,
                size: 48,
              ),
              SizedBox(height: Responsive.dp(12)),
              Text(
                provider.error!,
                style: AppTextStyles.body2,
                textAlign: TextAlign.center,
              ),
              SizedBox(height: Responsive.dp(16)),
              AppButton(
                label: 'ลองอีกครั้ง',
                onTap: () => provider.startAssessment(widget.symptomId),
              ),
            ],
          ),
        ),
      );
    }

    final box = provider.currentBox;
    if (box == null) return const SizedBox.shrink();

    final selected = provider.selectedChoicesFor(box.boxId);

    return SingleChildScrollView(
      padding: EdgeInsets.fromLTRB(hp, 16, hp, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (provider.answeredBoxes.isNotEmpty) ...[
            Text('คำถามก่อนหน้า', style: AppTextStyles.body2Bold),
            SizedBox(height: Responsive.dp(10)),
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
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  'คำถามคัดกรอง',
                  style: AppTextStyles.body3Bold.copyWith(
                    color: AppColors.textSecondary,
                  ),
                ),
              ),
              const Spacer(),
              Text(
                selected.isEmpty ? 'เลือกคำตอบเพื่อไปต่อ' : 'เลือกแล้ว',
                style: AppTextStyles.body2.copyWith(
                  color: selected.isEmpty
                      ? AppColors.textSecondary
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
                borderRadius: BorderRadius.circular(20),
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
                color: const Color(0xFFF5F4FF),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFE1DFFF)),
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
                        color: AppColors.textPrimary,
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
    );
  }

  Widget _buildBottomBar(
    BuildContext context,
    AssessmentProvider provider,
    double hp,
  ) {
    final box = provider.currentBox;
    if (box == null) return const SizedBox.shrink();

    final hasSelection = provider.selectedChoicesFor(box.boxId).isNotEmpty;
    final loading = provider.isLoading;

    return Container(
      padding: EdgeInsets.fromLTRB(hp, 12, hp, 28),
      decoration: const BoxDecoration(
        color: AppColors.white,
        border: Border(top: BorderSide(color: AppColors.border)),
      ),
      child: SizedBox(
        width: double.infinity,
        child: AppButton(
          label: loading ? 'กำลังโหลด...' : 'ถัดไป →',
          height: 52,
          onTap: (!hasSelection || loading)
              ? null
              : () => provider.submitAnswers(),
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
        color: AppColors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
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
                      color: AppColors.textSecondary,
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
            color: selected ? AppColors.primaryLight : AppColors.white,
            borderRadius: BorderRadius.circular(14),
            border: selected
                ? Border.all(color: AppColors.primary, width: 1.5)
                : Border.all(color: AppColors.border, width: 1),
          ),
          child: Row(
            children: [
              _ChoiceIcon(choice: choice, selected: selected),
              SizedBox(width: Responsive.dp(10)),
              Expanded(
                child: Text(
                  choice.choiceText,
                  style: AppTextStyles.body2Bold.copyWith(
                    color: selected ? AppColors.primary : AppColors.textPrimary,
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
                    color: selected ? AppColors.primary : AppColors.border,
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

class _QuestionIcon extends StatelessWidget {
  final String? imageUrl;

  const _QuestionIcon({this.imageUrl});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 44,
      height: 44,
      decoration: BoxDecoration(
        color: AppColors.primaryLight,
        borderRadius: BorderRadius.circular(13),
      ),
      clipBehavior: Clip.antiAlias,
      child: imageUrl?.trim().isNotEmpty == true
          ? Image.network(
              _resolveImageUrl(imageUrl!),
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => const Icon(
                Icons.health_and_safety_outlined,
                color: AppColors.primary,
                size: 23,
              ),
            )
          : const Icon(
              Icons.health_and_safety_outlined,
              color: AppColors.primary,
              size: 23,
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
        color: selected ? AppColors.primary : AppColors.surface,
        borderRadius: BorderRadius.circular(10),
      ),
      clipBehavior: Clip.antiAlias,
      child: imageUrl?.trim().isNotEmpty == true
          ? Image.network(
              _resolveImageUrl(imageUrl!),
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Icon(
                icon,
                color: selected ? AppColors.white : AppColors.textSecondary,
                size: 18,
              ),
            )
          : Icon(
              icon,
              color: selected ? AppColors.white : AppColors.textSecondary,
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
