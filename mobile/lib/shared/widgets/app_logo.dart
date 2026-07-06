import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';

class AppLogo extends StatelessWidget {
  final double size;
  final bool showText;
  final bool showTagline;

  const AppLogo({
    super.key,
    this.size = 120,
    this.showText = true,
    this.showTagline = false,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: size,
          height: size,
          decoration: BoxDecoration(
            color: AppColors.primary,
            borderRadius: BorderRadius.circular(size * 0.22),
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(size * 0.22),
            child: Image.asset(
              'images/logo.png',
              width: size,
              height: size,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Center(
                child: Text(
                  'CU',
                  style: AppTextStyles.logo_h1.copyWith(
                    color: AppColors.white,
                    fontSize: size * 0.32,
                  ),
                ),
              ),
            ),
          ),
        ),
        if (showText) ...[
          SizedBox(height: size * 0.15),
          Text(
            'CHECKUP',
            style: AppTextStyles.logo_h1.copyWith(fontSize: size * 0.28),
          ),
        ],
        if (showTagline) ...[
          const SizedBox(height: 4),
          Text(
            'แอปพลิเคชันประเมิน\nอาการเจ็บป่วยเบื้องต้น',
            textAlign: TextAlign.center,
            style: AppTextStyles.body2.copyWith(color: AppColors.textSecondary),
          ),
        ],
      ],
    );
  }
}

/// โลโก้ขนาดเล็กสำหรับ AppBar / BottomNav
class AppLogoSmall extends StatelessWidget {
  final double size;

  const AppLogoSmall({super.key, this.size = 60});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: AppColors.primary,
        borderRadius: BorderRadius.circular(size * 0.25),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(size * 0.25),
        child: Image.asset(
          'images/logo.png',
          fit: BoxFit.cover,
          errorBuilder: (_, __, ___) => Center(
            child: Text(
              'CU',
              style: AppTextStyles.logo_h1.copyWith(
                color: AppColors.white,
                fontSize: size * 0.35,
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// โลโก้แบบข้อความ (logo_text.png)
class AppLogoText extends StatelessWidget {
  final double width;
  final Color? color;

  const AppLogoText({super.key, this.width = 160, this.color});

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      'images/logo_text.png',
      width: width,
      fit: BoxFit.contain,
      color: color,
      colorBlendMode: color != null ? BlendMode.srcIn : null,
      errorBuilder: (_, __, ___) => Text(
        'CHECKUP',
        style: AppTextStyles.logo_h1.copyWith(
          fontSize: width * 0.18,
          color: color ?? AppColors.primary,
          letterSpacing: width * 0.02,
        ),
      ),
    );
  }
}
