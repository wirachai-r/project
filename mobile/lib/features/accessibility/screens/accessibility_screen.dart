import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';

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
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text('ขนาดตัวอักษร', style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 8),
          Container(
            padding: EdgeInsets.zero,
            decoration: BoxDecoration(
              color: Colors.transparent,
              borderRadius: BorderRadius.circular(14),
            ),
            child: SegmentedButton<double>(
              expandedInsets: EdgeInsets.zero,
              showSelectedIcon: false,
              style: ButtonStyle(
                side: const WidgetStatePropertyAll(
                  BorderSide(color: AppColors.primary, width: 1.5),
                ),
                backgroundColor: WidgetStateProperty.resolveWith(
                  (states) => states.contains(WidgetState.selected)
                      ? AppColors.primary
                      : AppColors.surfaceElevated,
                ),
                foregroundColor: WidgetStateProperty.resolveWith(
                  (states) => states.contains(WidgetState.selected)
                      ? AppColors.white
                      : AppColors.textPrimary,
                ),
                minimumSize: const WidgetStatePropertyAll(Size(0, 46)),
                padding: const WidgetStatePropertyAll(
                  EdgeInsets.symmetric(horizontal: 8, vertical: 10),
                ),
                textStyle: WidgetStatePropertyAll(AppTextStyles.body2Bold),
                shape: WidgetStatePropertyAll(
                  RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
              ),
              segments: const [
                ButtonSegment(value: 0.85, label: Text('เล็ก')),
                ButtonSegment(value: 1, label: Text('ปกติ')),
                ButtonSegment(value: 1.15, label: Text('ใหญ่')),
                ButtonSegment(value: 1.3, label: Text('ใหญ่มาก')),
              ],
              selected: {settings.textScale},
              onSelectionChanged: (values) =>
                  settings.setTextScale(values.first),
            ),
          ),
          const SizedBox(height: 12),
          const Card(
            child: Padding(
              padding: EdgeInsets.all(16),
              child: Text('ตัวอย่างข้อความสุขภาพสำหรับตรวจสอบขนาดตัวอักษร'),
            ),
          ),
          const SizedBox(height: 16),
          SwitchListTile(
            title: const Text('Contrast สูง'),
            subtitle: const Text(
              'เพิ่มความแตกต่างระหว่างข้อความ พื้นหลัง และเส้นขอบ',
            ),
            secondary: const Icon(Icons.contrast_rounded),
            value: settings.highContrast,
            onChanged: settings.setHighContrast,
          ),
          SwitchListTile(
            title: const Text('ลดภาพเคลื่อนไหว'),
            subtitle: const Text(
              'ลด Animation ที่อาจทำให้เวียนศีรษะหรือรบกวนการใช้งาน',
            ),
            secondary: const Icon(Icons.motion_photos_off_outlined),
            value: settings.reduceMotion,
            onChanged: settings.setReduceMotion,
          ),
          const SizedBox(height: 20),
          OutlinedButton.icon(
            onPressed: settings.reset,
            icon: const Icon(Icons.restart_alt_rounded),
            label: const Text('คืนค่าเริ่มต้น'),
          ),
          const SizedBox(height: 16),
          Semantics(
            label: 'ข้อมูลการรองรับโปรแกรมอ่านหน้าจอ',
            child: const Text(
              'แอปรองรับการตั้งค่าขนาดตัวอักษรของระบบ ปุ่มสำคัญมีชื่อกำกับสำหรับโปรแกรมอ่านหน้าจอ และพื้นที่กดมีขนาดอย่างน้อย 44 จุด',
            ),
          ),
        ],
      ),
    );
  }
}
