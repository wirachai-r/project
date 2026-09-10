import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import 'app_button.dart';

void showAppSuccess(BuildContext context, String message) {
  final messenger = ScaffoldMessenger.of(context);
  messenger
    ..hideCurrentSnackBar()
    ..showSnackBar(
      SnackBar(
        behavior: SnackBarBehavior.floating,
        backgroundColor: AppColors.success,
        content: Row(
          children: [
            const Icon(Icons.check_circle_rounded, color: AppColors.white),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                message,
                style: AppTextStyles.body2Bold.copyWith(color: AppColors.white),
              ),
            ),
          ],
        ),
      ),
    );
}

class AppLoadingView extends StatelessWidget {
  final String label;

  const AppLoadingView({super.key, this.label = 'กำลังโหลดข้อมูล...'});

  @override
  Widget build(BuildContext context) => Semantics(
    label: label,
    liveRegion: true,
    child: Center(
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 360),
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 64,
                height: 64,
                alignment: Alignment.center,
                decoration: const BoxDecoration(
                  color: AppColors.primaryLight,
                  shape: BoxShape.circle,
                ),
                child: const SizedBox.square(
                  dimension: 30,
                  child: CircularProgressIndicator(
                    strokeWidth: 3,
                    strokeCap: StrokeCap.round,
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                label,
                style: AppTextStyles.body2.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ),
        ),
      ),
    ),
  );
}

class AppLoadingSpinner extends StatelessWidget {
  final double size;
  final Color? color;

  const AppLoadingSpinner({super.key, this.size = 24, this.color});

  @override
  Widget build(BuildContext context) => Semantics(
    label: 'กำลังโหลด',
    liveRegion: true,
    child: SizedBox.square(
      dimension: size,
      child: CircularProgressIndicator(
        color: color,
        strokeWidth: size <= 24 ? 2.5 : 3,
        strokeCap: StrokeCap.round,
      ),
    ),
  );
}

class AppMessageView extends StatelessWidget {
  final IconData icon;
  final String title;
  final String message;
  final String? actionLabel;
  final VoidCallback? onAction;

  const AppMessageView({
    super.key,
    required this.icon,
    required this.title,
    required this.message,
    this.actionLabel,
    this.onAction,
  });

  const AppMessageView.empty({
    super.key,
    required this.title,
    required this.message,
    this.actionLabel,
    this.onAction,
  }) : icon = Icons.inbox_outlined;

  const AppMessageView.error({
    super.key,
    this.title = 'เกิดข้อผิดพลาด',
    required this.message,
    this.actionLabel = 'ลองอีกครั้ง',
    this.onAction,
  }) : icon = Icons.error_outline_rounded;

  @override
  Widget build(BuildContext context) {
    final isError = icon == Icons.error_outline_rounded;
    final iconColor = isError ? AppColors.danger : AppColors.primary;
    final iconBackground = isError
        ? Theme.of(context).colorScheme.errorContainer
        : AppColors.primaryLight;
    return Semantics(
      container: true,
      liveRegion: isError,
      child: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 360),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: iconBackground,
                    borderRadius: BorderRadius.circular(26),
                  ),
                  child: Icon(icon, color: iconColor, size: 36),
                ),
                const SizedBox(height: 20),
                Text(
                  title,
                  style: AppTextStyles.h4.copyWith(
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 8),
                Text(
                  message,
                  style: AppTextStyles.body2.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                  textAlign: TextAlign.center,
                ),
                if (onAction != null && actionLabel != null) ...[
                  const SizedBox(height: 24),
                  SizedBox(
                    width: 200,
                    child: AppButton(
                      label: actionLabel!,
                      onTap: onAction,
                      expand: false,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class AppSectionHeader extends StatelessWidget {
  final String title;
  final String? actionLabel;
  final VoidCallback? onAction;

  const AppSectionHeader({
    super.key,
    required this.title,
    this.actionLabel,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) => Row(
    crossAxisAlignment: CrossAxisAlignment.center,
    children: [
      Container(
        width: 4,
        height: 22,
        decoration: BoxDecoration(
          color: AppColors.primary,
          borderRadius: BorderRadius.circular(999),
        ),
      ),
      const SizedBox(width: 10),
      Expanded(
        child: Text(
          title,
          style: AppTextStyles.h4.copyWith(
            color: Theme.of(context).colorScheme.onSurface,
          ),
          maxLines: 2,
        ),
      ),
      if (actionLabel != null && onAction != null) ...[
        const SizedBox(width: 8),
        TextButton(onPressed: onAction, child: Text(actionLabel!)),
      ],
    ],
  );
}

void showAppError(BuildContext context, String message) {
  final messenger = ScaffoldMessenger.of(context);

  messenger
    ..hideCurrentSnackBar()
    ..showSnackBar(
      SnackBar(
        behavior: SnackBarBehavior.floating,
        backgroundColor: AppColors.danger,
        content: Row(
          children: [
            const Icon(Icons.error_outline_rounded, color: AppColors.white),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                message,
                style: AppTextStyles.body2Bold.copyWith(color: AppColors.white),
              ),
            ),
          ],
        ),
      ),
    );
}
