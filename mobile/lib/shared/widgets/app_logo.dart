import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';

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
        // PNG Logo
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
              // fallback ถ้า asset ยังไม่มี
              errorBuilder: (_, __, ___) => Center(
                child: Text(
                  'CU',
                  style: TextStyle(
                    color: AppColors.white,
                    fontSize: size * 0.32,
                    fontWeight: FontWeight.w900,
                    fontFamily: 'Prompt',
                  ),
                ),
              ),
            ),
          ),
        ),
        if (showText) ...[
          SizedBox(height: size * 0.15),
          Text(
            'Checkup',
            style: TextStyle(
              fontFamily: 'Prompt',
              fontSize: size * 0.28,
              fontWeight: FontWeight.w700,
              color: AppColors.primary,
            ),
          ),
        ],
        if (showTagline) ...[
          const SizedBox(height: 4),
          const Text(
            'แอปพลิเคชันประเมิน\nอาการเจ็บป่วยเบื้องต้น',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontFamily: 'Prompt',
              fontSize: 14,
              fontWeight: FontWeight.w400,
              color: AppColors.textSecondary,
              height: 1.6,
            ),
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
              style: TextStyle(
                color: AppColors.white,
                fontSize: size * 0.35,
                fontWeight: FontWeight.w900,
                fontFamily: 'Prompt',
              ),
            ),
          ),
        ),
      ),
    );
  }
}
