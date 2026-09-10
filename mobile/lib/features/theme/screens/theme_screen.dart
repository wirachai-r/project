import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_layout.dart';
import '../providers/theme_provider.dart';

class ThemeScreen extends StatelessWidget {
  const ThemeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<ThemeProvider>();
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'ธีม',
          style: AppTextStyles.h4.copyWith(
            color: Theme.of(context).colorScheme.onSurface,
          ),
        ),
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1),
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
                'เลือกธีมของแอป',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const SizedBox(height: 6),
              Text(
                'เลือกรูปแบบที่สบายตา หรือให้แอปเปลี่ยนตามการตั้งค่าของอุปกรณ์',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 18),
              Card(
                margin: EdgeInsets.zero,
                child: Column(
                  children: [
                    _ThemeOption(
                      icon: Icons.brightness_auto_rounded,
                      title: 'ตามระบบ',
                      subtitle: 'เปลี่ยนตามธีมของอุปกรณ์โดยอัตโนมัติ',
                      value: ThemeMode.system,
                      selected: provider.themeMode,
                    ),
                    const Divider(height: 1, indent: 64),
                    _ThemeOption(
                      icon: Icons.light_mode_outlined,
                      title: 'สว่าง',
                      subtitle: 'ใช้พื้นหลังสว่างตลอดเวลา',
                      value: ThemeMode.light,
                      selected: provider.themeMode,
                    ),
                    const Divider(height: 1, indent: 64),
                    _ThemeOption(
                      icon: Icons.dark_mode_outlined,
                      title: 'มืด',
                      subtitle: 'ลดความสว่างของหน้าจอในที่แสงน้อย',
                      value: ThemeMode.dark,
                      selected: provider.themeMode,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ThemeOption extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final ThemeMode value;
  final ThemeMode selected;

  const _ThemeOption({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.value,
    required this.selected,
  });

  @override
  Widget build(BuildContext context) => RadioListTile<ThemeMode>(
    value: value,
    groupValue: selected,
    onChanged: (value) {
      if (value != null) context.read<ThemeProvider>().setThemeMode(value);
    },
    secondary: Icon(icon, color: Theme.of(context).colorScheme.primary),
    title: Text(title, style: Theme.of(context).textTheme.titleMedium),
    subtitle: Text(subtitle, style: Theme.of(context).textTheme.bodySmall),
    controlAffinity: ListTileControlAffinity.trailing,
  );
}
