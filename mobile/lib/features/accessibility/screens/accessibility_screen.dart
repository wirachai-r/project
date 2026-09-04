import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_layout.dart';

import '../providers/accessibility_provider.dart';

class AccessibilityScreen extends StatelessWidget {
  const AccessibilityScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final settings = context.watch<AccessibilityProvider>();
    return Scaffold(
      appBar: AppBar(
        title: Text('การแสดงผลและการเข้าถึง', style: AppTextStyles.h4),
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, thickness: 1, color: AppColors.border),
        ),
      ),
      body: ResponsiveBuilder(
        builder: (context) => AppContentWidth(
          child: ListView(
            padding: EdgeInsets.fromLTRB(
              Responsive.horizontalPadding,
              20,
              Responsive.horizontalPadding,
              32,
            ),
            children: [
              Text('ขนาดข้อความ', style: AppTextStyles.h3),
              const SizedBox(height: 6),
              Text(
                'ลากแถบเพื่อเลือกขนาดที่อ่านสบาย ข้อความในแอปจะเปลี่ยนทันที',
                style: AppTextStyles.body2.copyWith(
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 18),
              AppPanel(
                padding: const EdgeInsets.all(20),
                child: Column(
                  children: [
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 22,
                      ),
                      decoration: BoxDecoration(
                        color: AppColors.surfacePrimary,
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: Column(
                        children: [
                          Text(
                            'Aa',
                            style: AppTextStyles.h1.copyWith(
                              color: AppColors.primary,
                            ),
                          ),
                          const SizedBox(height: 8),
                          const Text(
                            'ตัวอย่างข้อความสุขภาพสำหรับตรวจสอบขนาดตัวอักษร',
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),
                    Row(
                      children: [
                        Text('ก', style: AppTextStyles.body3),
                        Expanded(
                          child: Slider(
                            min: 0.85,
                            max: 1.15,
                            divisions: 2,
                            value: settings.textScale,
                            label: _scaleLabel(settings.textScale),
                            onChanged: settings.setTextScale,
                          ),
                        ),
                        Text('ก', style: AppTextStyles.h2),
                      ],
                    ),
                    Text(
                      _scaleLabel(settings.textScale),
                      style: AppTextStyles.body2Bold.copyWith(
                        color: AppColors.primary,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              Text('การแสดงผล', style: AppTextStyles.h3),
              const SizedBox(height: 10),
              Material(
                color: AppColors.surfaceElevated,
                clipBehavior: Clip.antiAlias,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20),
                  side: const BorderSide(color: AppColors.border),
                ),
                child: Column(
                  children: [
                    SwitchListTile(
                      title: const Text('เพิ่มความคมชัด'),
                      subtitle: const Text(
                        'ทำให้ข้อความและเส้นขอบแยกจากพื้นหลังชัดขึ้น',
                      ),
                      secondary: const Icon(Icons.contrast_rounded),
                      value: settings.highContrast,
                      onChanged: settings.setHighContrast,
                    ),
                    const Divider(height: 1, indent: 64),
                    SwitchListTile(
                      title: const Text('ลดภาพเคลื่อนไหว'),
                      subtitle: const Text(
                        'ลดเอฟเฟกต์ที่อาจรบกวนหรือทำให้เวียนศีรษะ',
                      ),
                      secondary: const Icon(Icons.motion_photos_off_outlined),
                      value: settings.reduceMotion,
                      onChanged: settings.setReduceMotion,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
              OutlinedButton.icon(
                onPressed: settings.reset,
                icon: const Icon(Icons.restart_alt_rounded),
                label: const Text('คืนค่าเริ่มต้น'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _scaleLabel(double value) {
    if (value < 0.95) return 'เล็ก';
    if (value < 1.1) return 'ปกติ';
    return 'ใหญ่';
  }
}
