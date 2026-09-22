import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';

class NotificationPresentation {
  const NotificationPresentation({
    required this.icon,
    required this.label,
    required this.color,
    required this.backgroundColor,
  });

  final IconData icon;
  final String label;
  final Color color;
  final Color backgroundColor;

  factory NotificationPresentation.fromItem(Map<String, dynamic> item) {
    switch (item['type']?.toString()) {
      case 'E':
        return const NotificationPresentation(
          icon: Icons.notification_important_rounded,
          label: 'เร่งด่วน',
          color: Color(0xFFDC2626),
          backgroundColor: Color(0xFFFEE2E2),
        );
      case 'W':
        return const NotificationPresentation(
          icon: Icons.warning_amber_rounded,
          label: 'คำเตือน',
          color: Color(0xFFB45309),
          backgroundColor: Color(0xFFFEF3C7),
        );
      case 'I':
        return const NotificationPresentation(
          icon: Icons.info_outline_rounded,
          label: 'ข้อมูล',
          color: Color(0xFF0369A1),
          backgroundColor: Color(0xFFE0F2FE),
        );
      case 'S':
        return const NotificationPresentation(
          icon: Icons.notifications_outlined,
          label: 'แจ้งเตือนจากระบบ',
          color: AppColors.notificationSystem,
          backgroundColor: AppColors.notificationSystemLight,
        );
    }

    return switch (item['target_type']?.toString()) {
      'user_feedback' => const NotificationPresentation(
        icon: Icons.feedback_outlined,
        label: 'ผลการตรวจสอบข้อเสนอแนะ',
        color: AppColors.notificationPersonal,
        backgroundColor: AppColors.notificationPersonalLight,
      ),
      'article_comment_report' => const NotificationPresentation(
        icon: Icons.report_outlined,
        label: 'ผลการตรวจสอบรายงาน',
        color: AppColors.notificationPersonal,
        backgroundColor: AppColors.notificationPersonalLight,
      ),
      'assessment' => const NotificationPresentation(
        icon: Icons.fact_check_outlined,
        label: 'ผลการประเมิน',
        color: AppColors.notificationAssessment,
        backgroundColor: AppColors.notificationAssessmentLight,
      ),
      'health_episode' => const NotificationPresentation(
        icon: Icons.monitor_heart_outlined,
        label: 'การติดตามอาการ',
        color: AppColors.notificationTracking,
        backgroundColor: AppColors.notificationTrackingLight,
      ),
      'health_reminder' => const NotificationPresentation(
        icon: Icons.alarm_rounded,
        label: 'การเตือนสุขภาพ',
        color: AppColors.notificationReminder,
        backgroundColor: AppColors.notificationReminderLight,
      ),
      'daily_health_record' => const NotificationPresentation(
        icon: Icons.event_note_outlined,
        label: 'บันทึกสุขภาพประจำวัน',
        color: AppColors.notificationDaily,
        backgroundColor: AppColors.notificationDailyLight,
      ),
      _ => const NotificationPresentation(
        icon: Icons.person_outline_rounded,
        label: 'แจ้งเตือนส่วนตัว',
        color: AppColors.notificationPersonal,
        backgroundColor: AppColors.notificationPersonalLight,
      ),
    };
  }
}
