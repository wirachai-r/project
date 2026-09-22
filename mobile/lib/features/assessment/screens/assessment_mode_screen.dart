import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_layout.dart';
import '../providers/assessment_mode_provider.dart';

class AssessmentModeScreen extends StatelessWidget {
  const AssessmentModeScreen({super.key});

  Future<void> _selectMode(
    BuildContext context,
    AssessmentModeProvider provider,
    AssessmentMode mode,
  ) async {
    if (provider.mode == mode || provider.saving) return;

    try {
      await provider.setAdaptive(
        mode == AssessmentMode.adaptive,
        syncProfile: true,
      );
    } catch (_) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('บันทึกรูปแบบการประเมินไม่สำเร็จ')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<AssessmentModeProvider>();

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'รูปแบบการประเมิน',
          style: AppTextStyles.h4.copyWith(
            color: Theme.of(context).colorScheme.onSurface,
          ),
        ),
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1),
        ),
      ),
      body: ResponsiveBuilder(
        builder: (context) => AppContentWidth(
          child: ListView(
            padding: EdgeInsets.fromLTRB(
              Responsive.horizontalPadding,
              20,
              Responsive.horizontalPadding,
              32,
            ),
            children: [
              Text('เลือกระบบประเมินอาการ', style: AppTextStyles.h4),
              const SizedBox(height: 6),
              Text(
                'คุณสามารถกลับมาเปลี่ยนรูปแบบได้ทุกเมื่อ โดยไม่กระทบผลการประเมินเดิม',
                style: AppTextStyles.body2.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 18),
              Card(
                margin: EdgeInsets.zero,
                clipBehavior: Clip.antiAlias,
                child: Column(
                  children: [
                    _ModeOption(
                      icon: Icons.account_tree_outlined,
                      title: 'ประเมินแบบแผนผังอาการ',
                      subtitle: 'เลือกตำแหน่งและอาการตามลำดับที่กำหนดไว้',
                      value: AssessmentMode.classic,
                      selected: provider.mode,
                      enabled: !provider.saving,
                      onSelected: (mode) => _selectMode(context, provider, mode),
                    ),
                    const Divider(height: 1, indent: 64),
                    _ModeOption(
                      icon: Icons.question_answer_outlined,
                      title: 'ประเมินแบบปรับตามคำตอบ',
                      subtitle: 'ระบบปรับคำถามถัดไปตามอาการและคำตอบของคุณ',
                      value: AssessmentMode.adaptive,
                      selected: provider.mode,
                      enabled: !provider.saving,
                      onSelected: (mode) => _selectMode(context, provider, mode),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.info_outline, color: AppColors.primary),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'ผลลัพธ์เป็นการคัดกรองเบื้องต้น ไม่ใช่การวินิจฉัยโรค',
                        style: AppTextStyles.body2.copyWith(
                          color: Theme.of(context).colorScheme.onSurface,
                        ),
                      ),
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

class _ModeOption extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final AssessmentMode value;
  final AssessmentMode selected;
  final bool enabled;
  final ValueChanged<AssessmentMode> onSelected;

  const _ModeOption({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.value,
    required this.selected,
    required this.enabled,
    required this.onSelected,
  });

  @override
  Widget build(BuildContext context) => RadioListTile<AssessmentMode>(
    value: value,
    groupValue: selected,
    onChanged: enabled
        ? (mode) {
            if (mode != null) onSelected(mode);
          }
        : null,
    secondary: Icon(icon, color: AppColors.primary),
    title: Text(title, style: AppTextStyles.body1Bold),
    subtitle: Text(subtitle, style: AppTextStyles.body2),
    controlAffinity: ListTileControlAffinity.trailing,
    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
  );
}
