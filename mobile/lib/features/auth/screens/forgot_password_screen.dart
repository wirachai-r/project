import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_text_field.dart';

class ForgotPasswordScreen extends StatelessWidget {
  const ForgotPasswordScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final emailCtrl = TextEditingController();

    return ResponsiveBuilder(
      builder: (context) => Scaffold(
        backgroundColor: AppColors.white,
        appBar: AppBar(
          title: const Text('ลืมรหัสผ่าน'),
          leading: const BackButton(),
        ),
        body: Padding(
          padding: EdgeInsets.symmetric(
            horizontal: Responsive.horizontalPadding,
          ),
          child: Column(
            children: [
              const Spacer(),
              Container(
                width: Responsive.dp(120),
                height: Responsive.dp(120),
                decoration: const BoxDecoration(
                  color: AppColors.primaryLight,
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  Icons.lock_outline_rounded,
                  size: Responsive.dp(56),
                  color: AppColors.primary,
                ),
              ),
              SizedBox(height: Responsive.dp(32)),
              Text(
                'พบปัญหาในการเข้าสู่ระบบ?',
                style: AppTextStyles.h3,
                textAlign: TextAlign.center,
              ),
              SizedBox(height: Responsive.dp(12)),
              Text(
                'กรุณากรอกอีเมลของคุณเพื่อรับลิงก์สำหรับตั้ง\nรหัสผ่านใหม่เพื่อกลับเข้าสู่บัญชีของคุณ',
                textAlign: TextAlign.center,
                style: AppTextStyles.body2.copyWith(
                  color: AppColors.textSecondary,
                ),
              ),
              SizedBox(height: Responsive.dp(36)),
              AppTextField(
                label: 'อีเมล',
                hint: 'ระบุอีเมลของคุณ',
                controller: emailCtrl,
                keyboardType: TextInputType.emailAddress,
              ),
              SizedBox(height: Responsive.dp(24)),
              AppButton(
                label: 'ส่งลิงก์ตั้งรหัสผ่านใหม่',
                onTap: () => ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('ส่งลิงก์ตั้งรหัสผ่านแล้ว')),
                ),
              ),
              SizedBox(height: Responsive.dp(24)),
              GestureDetector(
                onTap: () => Navigator.pop(context),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(
                      Icons.arrow_back,
                      size: 16,
                      color: AppColors.textSecondary,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      'กลับไปหน้าเข้าสู่ระบบ',
                      style: AppTextStyles.body2.copyWith(
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              const Spacer(),
            ],
          ),
        ),
      ),
    );
  }
}
