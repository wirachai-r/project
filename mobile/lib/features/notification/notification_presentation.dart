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
    if (item['type'] != 'U') {
      return const NotificationPresentation(
        icon: Icons.notifications_outlined,
        label: 'แจ้งเตือนจากระบบ',
        color: AppColors.notificationSystem,
        backgroundColor: AppColors.notificationSystemLight,
      );
    }

    return switch (item['target_type']?.toString()) {
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
