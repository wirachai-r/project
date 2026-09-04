import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_layout.dart';
import '../../home/screens/home_screen.dart';
import '../providers/auth_provider.dart';
import 'reset_password_screen.dart';

class RegistrationOtpScreen extends StatefulWidget {
  final String email;

  const RegistrationOtpScreen({super.key, required this.email});

  @override
  State<RegistrationOtpScreen> createState() => _RegistrationOtpScreenState();
}

class _RegistrationOtpScreenState extends State<RegistrationOtpScreen> {
  final _otpController = TextEditingController();
  String? _error;
  bool _loading = false;
  bool _resending = false;

  @override
  void dispose() {
    _otpController.dispose();
    super.dispose();
  }

  Future<void> _verify() async {
    if (_otpController.text.length != 6) {
      setState(() => _error = 'กรุณากรอก OTP ให้ครบ 6 หลัก');
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    final success = await context.read<AuthProvider>().verifyRegistrationOtp(
      email: widget.email,
      otp: _otpController.text,
    );
    if (!mounted) return;
    setState(() => _loading = false);
    if (success) {
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => const HomeScreen()),
        (_) => false,
      );
    } else {
      setState(() {
        _error =
            context.read<AuthProvider>().errorMessage ??
            'รหัส OTP ไม่ถูกต้องหรือหมดอายุแล้ว';
      });
    }
  }

  Future<void> _resend() async {
    setState(() => _resending = true);
    try {
      await context.read<AuthProvider>().resendRegistrationOtp(widget.email);
      if (!mounted) return;
      _otpController.clear();
      setState(() => _error = null);
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('ส่งรหัส OTP ใหม่แล้ว')));
    } catch (error) {
      if (mounted) setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _resending = false);
    }
  }

  @override
  Widget build(BuildContext context) => ResponsiveBuilder(
    builder: (context) => Scaffold(
      appBar: AppBar(title: const Text('ยืนยันอีเมล')),
      body: SingleChildScrollView(
        padding: EdgeInsets.symmetric(
          horizontal: Responsive.horizontalPadding,
          vertical: 32,
        ),
        child: AppContentWidth(
          maxWidth: 520,
          child: Column(
            children: [
              AppHeroIntro(
                icon: Icons.mark_email_read_outlined,
                title: 'ยืนยันการสมัครสมาชิก',
                description: 'กรอกรหัส 6 หลักที่ส่งไปยัง\n${widget.email}',
              ),
              const SizedBox(height: 28),
              AppPanel(
                child: Column(
                  children: [
                    OtpCodeField(
                      controller: _otpController,
                      errorText: _error,
                      onChanged: () {
                        if (_error != null) setState(() => _error = null);
                      },
                      onSubmitted: _verify,
                    ),
                    const SizedBox(height: 16),
                    const AppInfoBanner(
                      icon: Icons.schedule_outlined,
                      message:
                          'รหัสหมดอายุใน 10 นาที และกรอกได้ไม่เกิน 5 ครั้ง',
                    ),
                    const SizedBox(height: 20),
                    AppButton(
                      label: 'ยืนยันและเข้าสู่ระบบ',
                      loading: _loading,
                      onTap: _verify,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              TextButton(
                onPressed: _resending || _loading ? null : _resend,
                child: Text(_resending ? 'กำลังส่ง...' : 'ส่งรหัสใหม่'),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    ),
  );
}
