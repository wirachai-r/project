import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';

class AppTextField extends StatelessWidget {
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
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: AppTextStyles.body2Bold),
        const SizedBox(height: 8),
        TextField(
          controller: controller,
          obscureText: obscure,
          keyboardType: keyboardType,
          onChanged: onChanged,
          readOnly: readOnly,
          onTap: onTap,
          style: AppTextStyles.body2.copyWith(color: AppColors.textPrimary),
          decoration: InputDecoration(
            hintText: hint,
            suffixIcon: suffixIcon,
            errorText: errorText,
            helperText: helperText,
          ),
        ),
      ],
    );
  }
}
