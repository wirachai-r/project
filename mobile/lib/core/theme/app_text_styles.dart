import 'package:flutter/material.dart';
import 'app_colors.dart';

class AppTextStyles {
  static const String _font = 'Prompt';

  // Headlines (Mobile: Prompt Bold)
  static const TextStyle h1 = TextStyle(
    fontFamily: _font,
    fontSize: 32,
    fontWeight: FontWeight.w700,
    color: AppColors.textPrimary,
    height: 1.25,
  );
  static const TextStyle h2 = TextStyle(
    fontFamily: _font,
    fontSize: 28,
    fontWeight: FontWeight.w700,
    color: AppColors.textPrimary,
    height: 1.3,
  );
  static const TextStyle h3 = TextStyle(
    fontFamily: _font,
    fontSize: 24,
    fontWeight: FontWeight.w700,
    color: AppColors.textPrimary,
    height: 1.3,
  );
  static const TextStyle h4 = TextStyle(
    fontFamily: _font,
    fontSize: 20,
    fontWeight: FontWeight.w700,
    color: AppColors.textPrimary,
    height: 1.4,
  );

  // Body (Mobile: Prompt Regular/Bold)
  static const TextStyle body1 = TextStyle(
    fontFamily: _font,
    fontSize: 16,
    fontWeight: FontWeight.w400,
    color: AppColors.textPrimary,
    height: 1.5,
  );
  static const TextStyle body1Bold = TextStyle(
    fontFamily: _font,
    fontSize: 16,
    fontWeight: FontWeight.w700,
    color: AppColors.textPrimary,
    height: 1.5,
  );
  static const TextStyle body2 = TextStyle(
    fontFamily: _font,
    fontSize: 14,
    fontWeight: FontWeight.w400,
    color: AppColors.textPrimary,
    height: 1.5,
  );
  static const TextStyle body2Bold = TextStyle(
    fontFamily: _font,
    fontSize: 14,
    fontWeight: FontWeight.w700,
    color: AppColors.textPrimary,
    height: 1.5,
  );
  static const TextStyle body3 = TextStyle(
    fontFamily: _font,
    fontSize: 12,
    fontWeight: FontWeight.w400,
    color: AppColors.textPrimary,
    height: 1.5,
  );
  static const TextStyle body3Bold = TextStyle(
    fontFamily: _font,
    fontSize: 12,
    fontWeight: FontWeight.w700,
    color: AppColors.textPrimary,
    height: 1.5,
  );
}
