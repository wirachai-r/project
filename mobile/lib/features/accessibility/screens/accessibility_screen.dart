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
        title: Text(
          'ขนาดตัวอักษร',
          style: AppTextStyles.h4.copyWith(
            color: Theme.of(context).colorScheme.onSurface,
          ),
        ),
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, thickness: 1),
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
              Text(
                'ขนาดข้อความ',
                style: AppTextStyles.h3.copyWith(
                  color: Theme.of(context).colorScheme.onSurface,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                'ลากแถบเพื่อเลือกขนาดที่อ่านสบาย ข้อความในแอปจะเปลี่ยนทันที',
                style: AppTextStyles.body2.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
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
                        color: Theme.of(context).colorScheme.surfaceContainer,
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
                        Text(
                          'ก',
                          style: AppTextStyles.body3.copyWith(
                            color: Theme.of(context).colorScheme.onSurface,
                          ),
                        ),
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
                        Text(
                          'ก',
                          style: AppTextStyles.h2.copyWith(
                            color: Theme.of(context).colorScheme.onSurface,
                          ),
                        ),
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
