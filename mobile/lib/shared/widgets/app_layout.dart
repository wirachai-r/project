import 'package:flutter/material.dart';

import '../../core/theme/app_text_styles.dart';
import '../../core/utils/responsive.dart';

class AppContentWidth extends StatelessWidget {
  const AppContentWidth({
    super.key,
    required this.child,
    this.maxWidth = 680,
    this.shrinkWrapHeight = false,
  });

  final Widget child;
  final double maxWidth;
  final bool shrinkWrapHeight;

  @override
  Widget build(BuildContext context) => Align(
    alignment: Alignment.topCenter,
    heightFactor: shrinkWrapHeight ? 1 : null,
    child: ConstrainedBox(
      constraints: BoxConstraints(maxWidth: maxWidth),
      child: child,
    ),
  );
}

class AppHeroIntro extends StatelessWidget {
  const AppHeroIntro({
    super.key,
    required this.icon,
    required this.title,
    required this.description,
    this.color,
  });

  final IconData icon;
  final String title;
  final String description;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    final effectiveColor = color ?? Theme.of(context).colorScheme.primary;
    return Column(
      children: [
        Container(
          width: Responsive.dp(80),
          height: Responsive.dp(80),
          decoration: BoxDecoration(
            color: effectiveColor.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(26),
          ),
          child: Icon(icon, size: Responsive.dp(38), color: effectiveColor),
        ),
        SizedBox(height: Responsive.dp(20)),
        Text(
          title,
          style: AppTextStyles.h3.copyWith(
            color: Theme.of(context).colorScheme.onSurface,
          ),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 8),
        Text(
          description,
          textAlign: TextAlign.center,
          style: AppTextStyles.body2.copyWith(
            color: Theme.of(context).colorScheme.onSurfaceVariant,
            height: 1.55,
          ),
        ),
      ],
    );
  }
}

class AppPanel extends StatelessWidget {
  const AppPanel({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(18),
    this.margin = EdgeInsets.zero,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final EdgeInsetsGeometry margin;

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Padding(
      padding: margin,
      child: Material(
        color: colorScheme.surface,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
          side: BorderSide(color: colorScheme.outlineVariant),
        ),
        clipBehavior: Clip.antiAlias,
        child: Padding(padding: padding, child: child),
      ),
    );
  }
}

class AppInfoBanner extends StatelessWidget {
  const AppInfoBanner({
    super.key,
    required this.message,
    this.icon = Icons.info_outline_rounded,
    this.color,
  });

  final String message;
  final IconData icon;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final effectiveColor = color ?? Theme.of(context).colorScheme.primary;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: effectiveColor.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: effectiveColor.withValues(alpha: 0.14)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: effectiveColor, size: 22),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              message,
              style: AppTextStyles.body1.copyWith(
                height: 1.5,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
