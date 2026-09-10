import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

import '../../core/utils/app_icon_mapper.dart';

/// Renders API icon names without adding thousands of healthicons modules to
/// Flutter Web's debug build.
class SymptomIcon extends StatelessWidget {
  const SymptomIcon({
    super.key,
    required this.iconName,
    this.size = 24,
    this.color,
  });

  final String? iconName;
  final double size;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final value = iconName?.trim() ?? '';
    if (!value.startsWith('health:')) {
      return Icon(symptomIconFromName(value), size: size, color: color);
    }

    final name = value.substring('health:'.length);
    return SvgPicture.asset(
      'assets/healthicons/outline-24px/$name.svg',
      width: size,
      height: size,
      colorFilter: color == null
          ? null
          : ColorFilter.mode(color!, BlendMode.srcIn),
      errorBuilder: (context, error, stackTrace) =>
          Icon(Icons.monitor_heart_outlined, size: size, color: color),
    );
  }
}
