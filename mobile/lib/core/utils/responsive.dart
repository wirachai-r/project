import 'package:flutter/material.dart';

/// ช่วย scale ขนาดตามหน้าจอ
/// ออกแบบบน base width 390px (iPhone 14)
class Responsive {
  static late MediaQueryData _mediaQuery;

  static void init(BuildContext context) {
    _mediaQuery = MediaQuery.of(context);
  }

  static double get screenWidth => _mediaQuery.size.width;
  static double get screenHeight => _mediaQuery.size.height;
  static EdgeInsets get padding => _mediaQuery.padding;

  // Breakpoints
  static bool get isSmall => screenWidth < 360;
  static bool get isMedium => screenWidth >= 360 && screenWidth < 414;
  static bool get isLarge => screenWidth >= 414;

  /// Horizontal padding ของแต่ละหน้า
  static double get horizontalPadding {
    if (isSmall) return 16;
    if (isMedium) return 20;
    return 24;
  }

  /// Scale font ตามขนาดหน้าจอ (ถ้าต้องการ)
  static double sp(double size) {
    const baseWidth = 390.0;
    final scale = screenWidth / baseWidth;
    return size * scale.clamp(0.85, 1.15);
  }

  /// Scale widget ตามขนาดหน้าจอ
  static double dp(double size) {
    const baseWidth = 390.0;
    final scale = screenWidth / baseWidth;
    return size * scale.clamp(0.85, 1.2);
  }
}

/// Widget ที่รับ context แล้ว init Responsive ให้อัตโนมัติ
class ResponsiveBuilder extends StatelessWidget {
  final Widget Function(BuildContext context) builder;

  const ResponsiveBuilder({super.key, required this.builder});

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    return builder(context);
  }
}
