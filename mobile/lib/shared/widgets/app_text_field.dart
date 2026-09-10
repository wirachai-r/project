import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';

class AppTextField extends StatefulWidget {
  final String label;
  final String? hint;
  final TextEditingController? controller;
  final bool obscure;
  final Widget? suffixIcon;
  final TextInputType? keyboardType;
  final String? errorText;
  final String? helperText;
  final ValueChanged<String>? onChanged;
  final bool readOnly;
  final VoidCallback? onTap;
  final String? Function(String?)? validator;
  final TextInputAction? textInputAction;
  final Iterable<String>? autofillHints;
  final ValueChanged<String>? onSubmitted;
  final int maxLines;
  final Widget? prefixIcon;
  final List<TextInputFormatter>? inputFormatters;

  const AppTextField({
    super.key,
    required this.label,
    this.hint,
    this.controller,
    this.obscure = false,
    this.suffixIcon,
    this.keyboardType,
    this.errorText,
    this.helperText,
    this.onChanged,
    this.readOnly = false,
    this.onTap,
    this.validator,
    this.textInputAction,
    this.autofillHints,
    this.onSubmitted,
    this.maxLines = 1,
    this.prefixIcon,
    this.inputFormatters,
  });

  @override
  State<AppTextField> createState() => _AppTextFieldState();
}

class _AppTextFieldState extends State<AppTextField> {
  late final FocusNode _focusNode;

  @override
  void initState() {
    super.initState();
    _focusNode = FocusNode()..addListener(_onFocusChanged);
  }

  void _onFocusChanged() => setState(() {});

  @override
  void dispose() {
    _focusNode
      ..removeListener(_onFocusChanged)
      ..dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        AnimatedDefaultTextStyle(
          duration: const Duration(milliseconds: 160),
          style: AppTextStyles.body2Bold.copyWith(
            color: widget.errorText != null
                ? AppColors.danger
                : _focusNode.hasFocus
                ? AppColors.primary
                : Theme.of(context).colorScheme.onSurface,
          ),
          child: Text(widget.label),
        ),
        const SizedBox(height: 8),
        TextFormField(
          focusNode: _focusNode,
          controller: widget.controller,
          obscureText: widget.obscure,
          keyboardType: widget.keyboardType,
          onChanged: widget.onChanged,
          readOnly: widget.readOnly,
          onTap: widget.onTap,
          validator: widget.validator,
          textInputAction: widget.textInputAction,
          autofillHints: widget.autofillHints,
          onFieldSubmitted: widget.onSubmitted,
          maxLines: widget.obscure ? 1 : widget.maxLines,
          inputFormatters: widget.inputFormatters,
          style: AppTextStyles.body2.copyWith(
            color: Theme.of(context).colorScheme.onSurface,
          ),
          decoration: InputDecoration(
            hintText: widget.hint,
            suffixIcon: widget.suffixIcon,
            prefixIcon: widget.prefixIcon,
            errorText: widget.errorText,
            helperText: widget.helperText,
            filled: true,
            fillColor: Theme.of(context).colorScheme.surface,
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 15,
            ),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: BorderSide(
                color: Theme.of(context).colorScheme.outlineVariant,
              ),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: BorderSide(
                color: Theme.of(context).colorScheme.outlineVariant,
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: const BorderSide(
                color: AppColors.primary,
                width: 1.5,
              ),
            ),
          ),
        ),
      ],
    );
  }
}
