import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';

class AppButton extends StatefulWidget {
  final String label;
  final VoidCallback? onTap;
  final bool loading;
  final bool outlined;
  final Widget? icon;
  final Color? backgroundColor;
  final Color? foregroundColor;
  final double height;
  final bool expand;

  const AppButton({
    super.key,
    required this.label,
    this.onTap,
    this.loading = false,
    this.outlined = false,
    this.icon,
    this.backgroundColor,
    this.foregroundColor,
    this.height = 52,
    this.expand = true,
  });

  @override
  State<AppButton> createState() => _AppButtonState();
}

class _AppButtonState extends State<AppButton> {
  bool _pressed = false;

  void _setPressed(bool value) {
    if (_pressed == value || widget.onTap == null || widget.loading) return;
    setState(() => _pressed = value);
  }

  @override
  Widget build(BuildContext context) {
    final disabled = widget.onTap == null || widget.loading;
    final background = widget.backgroundColor ?? AppColors.primary;
    final foreground =
        widget.foregroundColor ??
        (widget.outlined ? background : AppColors.white);
    final Color effectiveForeground;
    if (widget.loading || !disabled) {
      effectiveForeground = foreground;
    } else if (widget.outlined) {
      effectiveForeground = Theme.of(context).colorScheme.onSurfaceVariant;
    } else {
      effectiveForeground = background.withValues(alpha: 0.58);
    }
    final content = AnimatedSwitcher(
      duration: const Duration(milliseconds: 160),
      child: widget.loading
          ? SizedBox.square(
              key: const ValueKey('loading'),
              dimension: 22,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                strokeCap: StrokeCap.round,
                color: effectiveForeground,
              ),
            )
          : Row(
              key: const ValueKey('label'),
              mainAxisAlignment: MainAxisAlignment.center,
              mainAxisSize: MainAxisSize.min,
              children: [
                if (widget.icon != null) ...[
                  IconTheme(
                    data: IconThemeData(color: effectiveForeground),
                    child: widget.icon!,
                  ),
                  const SizedBox(width: 8),
                ],
                Flexible(
                  child: Text(
                    widget.label,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: AppTextStyles.body1Bold.copyWith(
                      color: effectiveForeground,
                    ),
                  ),
                ),
              ],
            ),
    );

    final button = widget.outlined
        ? OutlinedButton(
            onPressed: widget.loading ? null : widget.onTap,
            style: OutlinedButton.styleFrom(
              foregroundColor: background,
              backgroundColor: Theme.of(context).colorScheme.surface,
              disabledForegroundColor: Theme.of(
                context,
              ).colorScheme.onSurfaceVariant,
              side: BorderSide(
                color: disabled
                    ? Theme.of(context).colorScheme.outline
                    : background,
                width: 1.5,
              ),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
            ),
            child: content,
          )
        : ElevatedButton(
            onPressed: widget.loading ? null : widget.onTap,
            style: ElevatedButton.styleFrom(
              backgroundColor: background,
              foregroundColor: foreground,
              disabledBackgroundColor: widget.loading
                  ? background
                  : Color.alphaBlend(
                      background.withValues(alpha: 0.12),
                      Theme.of(context).colorScheme.surface,
                    ),
              disabledForegroundColor: widget.loading
                  ? foreground
                  : background.withValues(alpha: 0.58),
              elevation: 0,
              shadowColor: Colors.transparent,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
            ),
            child: content,
          );

    return Semantics(
      button: true,
      enabled: widget.onTap != null && !widget.loading,
      label: widget.label,
      value: widget.loading ? 'กำลังดำเนินการ' : null,
      excludeSemantics: true,
      child: Listener(
        onPointerDown: (_) => _setPressed(true),
        onPointerUp: (_) => _setPressed(false),
        onPointerCancel: (_) => _setPressed(false),
        child: AnimatedOpacity(
          opacity: disabled && !widget.loading ? 0.9 : 1,
          duration: const Duration(milliseconds: 140),
          child: AnimatedScale(
            scale: _pressed ? 0.975 : 1,
            duration: const Duration(milliseconds: 120),
            curve: Curves.easeOutCubic,
            child: SizedBox(
              width: widget.expand ? double.infinity : null,
              height: widget.height,
              child: button,
            ),
          ),
        ),
      ),
    );
  }
}
