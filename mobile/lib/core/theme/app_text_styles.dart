import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'app_colors.dart';

class AppTextStyles {
  // --- Logo font (Fredoka — rounded bold เหมือนโลโก้) ---
  static TextStyle get logo_h1 => GoogleFonts.googleSansFlex(
    fontSize: 30,
    fontWeight: FontWeight.w900,
    color: AppColors.primary,
  );

  static TextStyle get logo_h2 => GoogleFonts.googleSansFlex(
    fontSize: 26,
    fontWeight: FontWeight.w900,
    color: AppColors.primary,
  );

  // Headlines — prompt รองรับไทย + อังกฤษ
  static TextStyle get h1 =>
      GoogleFonts.prompt(fontSize: 32, fontWeight: FontWeight.w700);

  static TextStyle get h2 =>
      GoogleFonts.prompt(fontSize: 28, fontWeight: FontWeight.w700);

  static TextStyle get h3 =>
      GoogleFonts.prompt(fontSize: 22, fontWeight: FontWeight.w700);

  static TextStyle get h4 =>
      GoogleFonts.prompt(fontSize: 18, fontWeight: FontWeight.w700);

  // Body
  static TextStyle get body1 =>
      GoogleFonts.prompt(fontSize: 16, fontWeight: FontWeight.w400);

  static TextStyle get body1Bold =>
      GoogleFonts.prompt(fontSize: 16, fontWeight: FontWeight.w700);

  static TextStyle get body2 =>
      GoogleFonts.prompt(fontSize: 14, fontWeight: FontWeight.w400);

  static TextStyle get body2Bold =>
      GoogleFonts.prompt(fontSize: 14, fontWeight: FontWeight.w700);

  static TextStyle get body3 =>
      GoogleFonts.prompt(fontSize: 12, fontWeight: FontWeight.w400);

  static TextStyle get body3Bold =>
      GoogleFonts.prompt(fontSize: 12, fontWeight: FontWeight.w700);
}
