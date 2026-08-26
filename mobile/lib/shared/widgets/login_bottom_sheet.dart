import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../features/auth/screens/login_screen.dart';
import '../../features/auth/screens/register_screen.dart';

/// เรียกใช้: LoginBottomSheet.show(context)
class LoginBottomSheet extends StatelessWidget {
  const LoginBottomSheet({super.key});

  static Future<void> show(BuildContext context) async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      showDragHandle: false,
      backgroundColor: Colors.transparent,
      constraints: const BoxConstraints(maxWidth: double.infinity),
      builder: (_) => const LoginBottomSheet(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final mediaQuery = MediaQuery.of(context);
    return AnimatedPadding(
      duration: const Duration(milliseconds: 180),
      curve: Curves.easeOut,
      padding: EdgeInsets.only(bottom: mediaQuery.viewInsets.bottom),
      child: Container(
        constraints: BoxConstraints(maxHeight: mediaQuery.size.height * .9),
        decoration: const BoxDecoration(
          color: AppColors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        child: SingleChildScrollView(
          padding: EdgeInsets.fromLTRB(
            24,
            16,
            24,
            20 + mediaQuery.padding.bottom,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
          // Handle bar
          Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: Theme.of(
                context,
              ).bottomSheetTheme.dragHandleColor ??
                  Theme.of(context).colorScheme.onSurfaceVariant.withValues(
                    alpha: .4,
                  ),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(height: 24),

          // Icon
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: AppColors.primaryLight,
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.lock_outline_rounded,
              color: AppColors.primary,
              size: 30,
            ),
          ),
          const SizedBox(height: 16),

          Text('เข้าสู่ระบบเพื่อใช้ฟีเจอร์นี้', style: AppTextStyles.h4),
          const SizedBox(height: 8),
          Text(
            'สมัครสมาชิกฟรีเพื่อบันทึกประวัติการประเมิน\nและเข้าถึงฟีเจอร์ทั้งหมด',
            style: AppTextStyles.body2.copyWith(color: AppColors.textSecondary),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 28),

          // ปุ่มเข้าสู่ระบบ
          SizedBox(
            width: double.infinity,
            height: 52,
            child: ElevatedButton(
              onPressed: () {
                final navigator = Navigator.of(context);
                navigator.pop();
                navigator.push(
                  MaterialPageRoute(builder: (_) => const LoginScreen()),
                );
              },
              child: const Text('เข้าสู่ระบบ'),
            ),
          ),
          const SizedBox(height: 12),

          // ปุ่มสมัครสมาชิก
          SizedBox(
            width: double.infinity,
            height: 52,
            child: OutlinedButton(
              onPressed: () {
                final navigator = Navigator.of(context);
                navigator.pop();
                navigator.push(
                  MaterialPageRoute(builder: (_) => const RegisterScreen()),
                );
              },
              child: const Text('สมัครสมาชิก'),
            ),
          ),
          const SizedBox(height: 12),

          // ยกเลิก
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text(
              'ไว้ทีหลัง',
              style: AppTextStyles.body2.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
          ),
            ],
          ),
        ),
      ),
    );
  }
}
