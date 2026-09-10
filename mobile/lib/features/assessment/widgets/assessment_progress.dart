import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';

class AssessmentProgress extends StatelessWidget {
  final int currentStep;
  final String title;
  final String description;
  final Widget? trailing;

  const AssessmentProgress({
    super.key,
    required this.currentStep,
    required this.title,
    required this.description,
    this.trailing,
  }) : assert(currentStep >= 1 && currentStep <= 3);

  static const _stepLabels = ['เลือกบริเวณ', 'เลือกอาการ', 'ตอบคำถาม'];

  @override
  Widget build(BuildContext context) {
    return Semantics(
      container: true,
      label: 'ขั้นตอนที่ $currentStep จาก 3 ${_stepLabels[currentStep - 1]}',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: List.generate(3, (index) {
              final step = index + 1;
              final active = step <= currentStep;
              return Expanded(
                child: Container(
                  height: 5,
                  margin: EdgeInsets.only(right: index == 2 ? 0 : 6),
                  decoration: BoxDecoration(
                    color: active
                        ? AppColors.primary
                        : Theme.of(context).colorScheme.outlineVariant,
                    borderRadius: BorderRadius.circular(99),
                  ),
                ),
              );
            }),
          ),
          const SizedBox(height: 10),
          Text(
            'ขั้นตอนที่ $currentStep จาก 3 · ${_stepLabels[currentStep - 1]}',
            style: AppTextStyles.body3Bold.copyWith(color: AppColors.primary),
          ),
          const SizedBox(height: 12),
          Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Expanded(child: Text(title, style: AppTextStyles.h3)),
              if (trailing != null) ...[const SizedBox(width: 12), trailing!],
            ],
          ),
          const SizedBox(height: 5),
          Text(
            description,
            style: AppTextStyles.body2.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
              height: 1.5,
            ),
          ),
        ],
      ),
    );
  }
}
