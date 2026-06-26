import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_logo.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_text_field.dart';
import 'register_screen.dart';
import 'forgot_password_screen.dart';
import '../../home/screens/home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  bool _obscure = true;
  bool _loading = false;

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ResponsiveBuilder(
      builder: (context) => Scaffold(
        backgroundColor: AppColors.white,
        body: SafeArea(
          child: SingleChildScrollView(
            padding: EdgeInsets.symmetric(
              horizontal: Responsive.horizontalPadding,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                SizedBox(height: Responsive.dp(16)),
                Center(child: Text('เข้าสู่ระบบ', style: AppTextStyles.h4)),
                SizedBox(height: Responsive.dp(32)),
                Center(
                  child: AppLogo(
                    size: Responsive.dp(120),
                    showText: true,
                    showTagline: true,
                  ),
                ),
                SizedBox(height: Responsive.dp(36)),
                AppTextField(
                  label: 'อีเมล',
                  hint: 'กรอกอีเมลของคุณ',
                  controller: _emailCtrl,
                  keyboardType: TextInputType.emailAddress,
                ),
                SizedBox(height: Responsive.dp(16)),
                AppTextField(
                  label: 'รหัสผ่าน',
                  hint: 'กรอกรหัสผ่านของคุณ',
                  controller: _passCtrl,
                  obscure: _obscure,
                  suffixIcon: IconButton(
                    icon: Icon(
                      _obscure
                          ? Icons.visibility_outlined
                          : Icons.visibility_off_outlined,
                      color: AppColors.textSecondary,
                    ),
                    onPressed: () => setState(() => _obscure = !_obscure),
                  ),
                ),
                SizedBox(height: Responsive.dp(12)),
                Align(
                  alignment: Alignment.centerRight,
                  child: GestureDetector(
                    onTap: () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => const ForgotPasswordScreen(),
                      ),
                    ),
                    child: Text(
                      'ลืมรหัสผ่าน ?',
                      style: AppTextStyles.body2Bold.copyWith(
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                ),
                SizedBox(height: Responsive.dp(24)),
                AppButton(
                  label: 'เข้าสู่ระบบ',
                  loading: _loading,
                  onTap: () async {
                    setState(() => _loading = true);
                    await Future.delayed(const Duration(seconds: 1));
                    if (mounted) {
                      Navigator.pushReplacement(
                        context,
                        MaterialPageRoute(builder: (_) => const HomeScreen()),
                      );
                    }
                  },
                ),
                SizedBox(height: Responsive.dp(24)),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      'ยังไม่มีบัญชี ? ',
                      style: AppTextStyles.body2.copyWith(
                        color: AppColors.textSecondary,
                      ),
                    ),
                    GestureDetector(
                      onTap: () => Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => const RegisterScreen(),
                        ),
                      ),
                      child: Text(
                        'สมัครสมาชิก',
                        style: AppTextStyles.body2Bold.copyWith(
                          color: AppColors.primary,
                        ),
                      ),
                    ),
                  ],
                ),
                SizedBox(height: Responsive.dp(24)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
