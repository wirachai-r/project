import 'package:flutter/material.dart';

class AppColors {
  // Primary
  static const Color primary = Color(0xFF2F27CE);
  static const Color primaryDark = Color.fromARGB(255, 22, 73, 176);
  static const Color primaryMid = Color(0xFF433BFF);
  static const Color primaryLight = Color(0xFFDEDCFF);

  // Neutral
  static const Color white = Color(0xFFFFFFFF);
  static const Color black = Color(0xFF000000);
  static const Color background = Color(0xFFF6F8FC);
  static const Color surface = Color(0xFFF0F4FA);
  static const Color surfaceElevated = Color(0xFFFFFFFF);
  static const Color surfacePrimary = Color(0xFFF1F6FF);
  static const Color surfaceDanger = Color(0xFFFFF1F2);

  // Semantic
  static const Color warning = Color(0xFFFFCC00); // เหลือง
  static const Color danger = Color(0xFFFF383C); // แดง
  static const Color success = Color(0xFF34C759); // เขียว
  static const Color successText = Color(0xFF198A43);

  // Notification categories
  static const Color notificationSystem = Color(0xFF52606D);
  static const Color notificationSystemLight = Color(0xFFE9EEF2);
  static const Color notificationAssessment = Color(0xFF2563EB);
  static const Color notificationAssessmentLight = Color(0xFFDBEAFE);
  static const Color notificationTracking = Color(0xFF6D28D9);
  static const Color notificationTrackingLight = Color(0xFFEDE9FE);
  static const Color notificationReminder = Color(0xFFD97706);
  static const Color notificationReminderLight = Color(0xFFFEF3C7);
  static const Color notificationDaily = Color(0xFF0F766E);
  static const Color notificationDailyLight = Color(0xFFCCFBF1);
  static const Color notificationPersonal = Color(0xFFBE185D);
  static const Color notificationPersonalLight = Color(0xFFFCE7F3);

  // Urgency levels
  static const Color urgencyRed = Color(0xFFFF383C);
  static const Color urgencyPink = Color(0xFFFF6B9D);
  static const Color urgencyYellow = Color(0xFFFFCC00);
  static const Color urgencyGreen = Color(0xFF34C759);
  static const Color urgencyWhite = Color(0xFFF3F4F6);

  // Text
  static const Color textPrimary = Color(0xFF17233C);
  static const Color textSecondary = Color(0xFF667085);
  static const Color textHint = Color(0xFF98A2B3);

  // Border
  static const Color border = Color(0xFFE5EAF2);
  static const Color borderStrong = Color(0xFFD0D8E5);

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
