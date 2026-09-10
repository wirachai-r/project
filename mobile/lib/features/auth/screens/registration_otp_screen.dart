import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

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
  bool _otpLocked = false;
  Timer? _timer;
  late DateTime _expiresAt;
  late DateTime _resendAt;
  int _remainingSeconds = 300;
  int _resendSeconds = 60;

  @override
  void initState() {
    super.initState();
    final timing = context.read<AuthProvider>().registrationOtpTiming;
    _startOtpTimer(timing.expiresIn, timing.resendAvailableIn);
  }

  @override
  void dispose() {
    _otpController.dispose();
    _timer?.cancel();
    super.dispose();
  }

  void _startOtpTimer(int expiresIn, int resendAvailableIn) {
    _timer?.cancel();
    final now = DateTime.now();
    _expiresAt = now.add(Duration(seconds: expiresIn));
    _resendAt = now.add(Duration(seconds: resendAvailableIn));
    _updateCountdown();
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) setState(_updateCountdown);
    });
  }

  void _updateCountdown() {
    final now = DateTime.now();
    _remainingSeconds = (_expiresAt.difference(now).inSeconds + 1)
        .clamp(0, 300)
        .toInt();
    _resendSeconds = (_resendAt.difference(now).inSeconds + 1)
        .clamp(0, 60)
        .toInt();
    if (_remainingSeconds == 0 && _resendSeconds == 0) _timer?.cancel();
  }

  String get _countdownLabel {
    final minutes = _remainingSeconds ~/ 60;
    final seconds = _remainingSeconds % 60;
    return '$minutes:${seconds.toString().padLeft(2, '0')}';
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
      final message =
          context.read<AuthProvider>().errorMessage ??
          'รหัส OTP ไม่ถูกต้องหรือหมดอายุแล้ว';
      setState(() {
        _error = message;
        _otpLocked = message.contains('ผิดครบจำนวนครั้งที่กำหนด') ||
            message.contains('OTP หมดอายุแล้ว') ||
            message.contains('OTP นี้ถูกใช้งานแล้ว');
      });
    }
  }

  Future<void> _resend() async {
    setState(() => _resending = true);
    try {
      final timing = await context
          .read<AuthProvider>()
          .resendRegistrationOtp(widget.email);
      if (!mounted) return;
      _otpController.clear();
      setState(() {
        _error = null;
        _otpLocked = false;
        _startOtpTimer(timing.expiresIn, timing.resendAvailableIn);
      });
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
                        if (_error != null && !_otpLocked) {
                          setState(() => _error = null);
                        }
                      },
                      onSubmitted: _verify,
                    ),
                    const SizedBox(height: 16),
                    AppInfoBanner(
                      icon: Icons.schedule_outlined,
                      message: _remainingSeconds > 0
                          ? 'รหัสหมดอายุใน $_countdownLabel และกรอกได้ไม่เกิน 5 ครั้ง'
                          : 'รหัสหมดอายุแล้ว กรุณาขอรหัสใหม่',
                    ),
                    const SizedBox(height: 20),
                    AppButton(
                      label: 'ยืนยันและเข้าสู่ระบบ',
                      loading: _loading,
                      onTap: _remainingSeconds > 0 && !_otpLocked
                          ? _verify
                          : null,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              TextButton(
                onPressed: _resending || _loading || _resendSeconds > 0
                    ? null
                    : _resend,
                child: Text(
                  _resending
                      ? 'กำลังส่ง...'
                      : 'ไม่ได้รับรหัส? ส่งรหัสใหม่',
                ),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    ),
  );
}
