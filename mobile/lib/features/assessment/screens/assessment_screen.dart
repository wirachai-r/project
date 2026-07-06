import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_button.dart';
import '../../assessment/providers/assessment_provider.dart';
import '../../home/screens/home_screen.dart';
import '../../../data/models/assessment_model.dart';
import 'assessment_result_screen.dart';

class AssessmentScreen extends StatefulWidget {
  final String symptomId;
  final String? symptomName;

  const AssessmentScreen({
    super.key,
    required this.symptomId,
    this.symptomName,
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
      context.read<AssessmentProvider>().startAssessment(widget.symptomId);
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
            backgroundColor: AppColors.white,
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
      return const Center(child: CircularProgressIndicator());
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
      padding: EdgeInsets.fromLTRB(hp, 20, hp, 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
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
          Text(box.questionText, style: AppTextStyles.h3),
          SizedBox(height: Responsive.dp(20)),
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
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        margin: EdgeInsets.only(bottom: Responsive.dp(10)),
        padding: EdgeInsets.symmetric(
          horizontal: Responsive.dp(16),
          vertical: Responsive.dp(14),
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
              width: 22,
              height: 22,
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
    );
  }
}
