import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_text_field.dart';
import '../../../shared/widgets/app_layout.dart';
import '../providers/auth_provider.dart';
import 'reset_password_screen.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _emailCtrl = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _emailCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final email = _emailCtrl.text.trim();
    if (email.isEmpty || !email.contains('@')) {
      setState(() => _error = 'กรุณากรอกอีเมลให้ถูกต้อง');
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final timing = await context.read<AuthProvider>().forgotPassword(email);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('พบบัญชีและส่งรหัส OTP ไปยังอีเมลแล้ว'),
        ),
      );
      final resetCompleted = await Navigator.push<bool>(
        context,
        MaterialPageRoute(
          builder: (_) => ResetPasswordScreen(
            initialEmail: email,
            otpExpiresIn: timing.expiresIn,
            resendAvailableIn: timing.resendAvailableIn,
          ),
        ),
      );
      if (resetCompleted == true && mounted) {
        Navigator.of(context).pop();
      }
    } catch (error) {
      if (mounted) setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => ResponsiveBuilder(
    builder: (context) => Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text('ลืมรหัสผ่าน', style: AppTextStyles.h4),
        bottom: PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(
            height: 1,
            thickness: 1,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: SingleChildScrollView(
        padding: EdgeInsets.symmetric(horizontal: Responsive.horizontalPadding),
        child: AppContentWidth(
          maxWidth: 520,
          child: Column(
            children: [
              SizedBox(height: Responsive.dp(36)),
              const AppHeroIntro(
                icon: Icons.lock_reset_rounded,
                title: 'ตั้งรหัสผ่านใหม่',
                description:
                    'กรอกอีเมลที่ใช้สมัคร ระบบจะส่งรหัส OTP 6 หลักให้คุณ',
              ),
              SizedBox(height: Responsive.dp(28)),
              AppPanel(
                child: Column(
                  children: [
                    AppTextField(
                      label: 'อีเมล',
                      controller: _emailCtrl,
                      keyboardType: TextInputType.emailAddress,
                      errorText: _error,
                      autofillHints: const [AutofillHints.email],
                    ),
                    const SizedBox(height: 24),
                    AppButton(
                      label: 'ส่งรหัส OTP',
                      loading: _loading,
                      onTap: _submit,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 8),
              TextButton(
                onPressed: () async {
                  final resetCompleted = await Navigator.push<bool>(
                    context,
                    MaterialPageRoute(
                      builder: (_) => ResetPasswordScreen(
                        initialEmail: _emailCtrl.text.trim(),
                      ),
                    ),
                  );
                  if (resetCompleted == true && mounted) {
                    Navigator.of(context).pop();
                  }
                },
                child: const Text('มีรหัส OTP แล้ว'),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    ),
  );
}
