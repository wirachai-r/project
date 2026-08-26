import 'package:flutter/material.dart';

class AppColors {
  // Primary
  static const Color primary = Color(0xFF2F27CE); // น้ำเงิน-ม่วง หลัก
  static const Color primaryMid = Color(0xFF433BFF); // น้ำเงินสด
  static const Color primaryLight = Color(0xFFDEDCFF); // ม่วงอ่อน background

  // Neutral
  static const Color white = Color(0xFFFFFFFF);
  static const Color black = Color(0xFF000000);
  static const Color background = Color(0xFFF7F8FC);
  static const Color surface = Color(0xFFF1F3F8);
  static const Color surfaceElevated = Color(0xFFFFFFFF);
  static const Color surfacePrimary = Color(0xFFF0EFFF);
  static const Color surfaceDanger = Color(0xFFFFF1F2);

  // Semantic
  static const Color warning = Color(0xFFFFCC00); // เหลือง
  static const Color danger = Color(0xFFFF383C); // แดง
  static const Color success = Color(0xFF34C759); // เขียว
  static const Color successText = Color(0xFF198A43);

  // Urgency levels
  static const Color urgencyRed = Color(0xFFFF383C);
  static const Color urgencyPink = Color(0xFFFF6B9D);
  static const Color urgencyYellow = Color(0xFFFFCC00);
  static const Color urgencyGreen = Color(0xFF34C759);
  static const Color urgencyWhite = Color(0xFFF3F4F6);

  // Text
  static const Color textPrimary = Color(0xFF17172B);
  static const Color textSecondary = Color(0xFF62677A);
  static const Color textHint = Color(0xFF8E93A4);

  // Border
  static const Color border = Color(0xFFE1E4EC);
  static const Color borderStrong = Color(0xFFCDD1DC);

  // เพิ่ม alias
  // static const Color background = white; // หรือ Color(0xFFFFFFFF)
  // static const Color divider = surface; // หรือ Color(0xFFF3F4F6)
  // static const Color error = danger; // หรือ Color(0xFFFF383C)

  // เพิ่ม helper methods
  static Color urgencyColor(String level) {
    switch (level) {
      case 'R':
        return urgencyRed;
      case 'P':
        return urgencyPink;
      case 'Y':
        return urgencyYellow;
      case 'G':
        return urgencyGreen;
      default:
        return urgencyWhite;
    }
  }

  static String urgencyLabel(String level) {
    switch (level) {
      case 'R':
        return 'ฉุกเฉินมาก';
      case 'P':
        return 'เร่งด่วน';
      case 'Y':
        return 'ควรพบแพทย์';
      case 'G':
        return 'ดูแลตัวเองได้';
      default:
        return 'ปกติ';
    }
  }
}
