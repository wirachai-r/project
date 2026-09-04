import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/app_logo.dart';
import '../../home/screens/home_screen.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../shared/widgets/app_feedback.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  static const _minimumDisplayDuration = Duration(seconds: 2);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _openHome());
  }

  Future<void> _openHome() async {
    await Future<void>.delayed(_minimumDisplayDuration);
    if (!mounted) return;
    Navigator.of(
      context,
    ).pushReplacement(MaterialPageRoute(builder: (_) => const HomeScreen()));
  }

  @override
  Widget build(BuildContext context) {
    // ไม่ใช้ Responsive เลย เพื่อหลีกเลี่ยง LateInitializationError
    return Scaffold(
      backgroundColor: AppColors.background,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            AppLogoSmall(size: 100),
            SizedBox(height: 16),
            Text('CHECKUP', style: AppTextStyles.logo_h1),
            SizedBox(height: 8),
            Text(
              'แอปพลิเคชันประเมิน\nอาการเจ็บป่วยเบื้องต้น',
              textAlign: TextAlign.center,
              style: AppTextStyles.body2.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
            SizedBox(height: 48),
            AppLoadingSpinner(size: 24),
          ],
        ),
      ),
    );
  }
}
