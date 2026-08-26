import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_text_field.dart';
import '../providers/auth_provider.dart';

class ResetPasswordScreen extends StatefulWidget {
  final String initialEmail;

  const ResetPasswordScreen({super.key, this.initialEmail = ''});

  @override
  State<ResetPasswordScreen> createState() => _ResetPasswordScreenState();
}

class _ResetPasswordScreenState extends State<ResetPasswordScreen> {
  late final TextEditingController _emailCtrl;
  final _otpCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();
  final _confirmationCtrl = TextEditingController();
  String? _resetToken;
  String? _error;
  bool _loading = false;
  bool _obscure = true;

  @override
  void initState() {
    super.initState();
    _emailCtrl = TextEditingController(text: widget.initialEmail);
  }

  @override
  void dispose() {
    _emailCtrl.dispose();
    _otpCtrl.dispose();
    _passwordCtrl.dispose();
    _confirmationCtrl.dispose();
    super.dispose();
  }

  Future<void> _verifyOtp() async {
    final email = _emailCtrl.text.trim();
    final otp = _otpCtrl.text.trim();
    if (email.isEmpty || otp.length != 6) {
      setState(() => _error = 'กรุณากรอกอีเมลและ OTP 6 หลัก');
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final token = await context.read<AuthProvider>().verifyPasswordOtp(
        email: email,
        otp: otp,
      );
      if (mounted) setState(() => _resetToken = token);
    } catch (error) {
      if (mounted) setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _resetPassword() async {
    if (_passwordCtrl.text.length < 8) {
      setState(() => _error = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
      return;
    }
    if (_passwordCtrl.text != _confirmationCtrl.text) {
      setState(() => _error = 'รหัสผ่านยืนยันไม่ตรงกัน');
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await context.read<AuthProvider>().resetPassword(
        email: _emailCtrl.text.trim(),
        resetToken: _resetToken!,
        password: _passwordCtrl.text,
        passwordConfirmation: _confirmationCtrl.text,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('ตั้งรหัสผ่านใหม่สำเร็จ'),
          backgroundColor: AppColors.success,
        ),
      );
      Navigator.of(context).popUntil((route) => route.isFirst);
    } catch (error) {
      if (mounted) setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => ResponsiveBuilder(
    builder: (context) => Scaffold(
      appBar: AppBar(
        title: Text(
          _resetToken == null ? 'ยืนยัน OTP' : 'ตั้งรหัสผ่านใหม่',
          style: AppTextStyles.h4,
        ),
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, thickness: 1, color: AppColors.border),
        ),
      ),
      body: SingleChildScrollView(
        padding: EdgeInsets.symmetric(
          horizontal: Responsive.horizontalPadding,
          vertical: 24,
        ),
        child: _resetToken == null ? _otpForm() : _passwordForm(),
      ),
    ),
  );

  Widget _otpForm() => Column(
    children: [
      const Icon(
        Icons.mark_email_read_outlined,
        size: 80,
        color: AppColors.primary,
      ),
      const SizedBox(height: 24),
      AppTextField(
        label: 'อีเมล',
        controller: _emailCtrl,
        keyboardType: TextInputType.emailAddress,
        readOnly: widget.initialEmail.isNotEmpty,
      ),
      const SizedBox(height: 16),
      AppTextField(
        label: 'รหัส OTP',
        hint: 'กรอก OTP 6 หลัก',
        controller: _otpCtrl,
        keyboardType: TextInputType.number,
        autofillHints: const [AutofillHints.oneTimeCode],
        errorText: _error,
        textInputAction: TextInputAction.done,
        onSubmitted: (_) => _verifyOtp(),
        inputFormatters: [
          FilteringTextInputFormatter.digitsOnly,
          LengthLimitingTextInputFormatter(6),
        ],
      ),
      const SizedBox(height: 8),
      const Text('OTP หมดอายุภายใน 10 นาที และตรวจสอบได้ไม่เกิน 5 ครั้ง'),
      const SizedBox(height: 24),
      AppButton(label: 'ยืนยัน OTP', loading: _loading, onTap: _verifyOtp),
    ],
  );

  Widget _passwordForm() => Column(
    children: [
      const Icon(
        Icons.verified_user_outlined,
        size: 80,
        color: AppColors.success,
      ),
      const SizedBox(height: 24),
      AppTextField(
        label: 'รหัสผ่านใหม่',
        controller: _passwordCtrl,
        obscure: _obscure,
        suffixIcon: IconButton(
          onPressed: () => setState(() => _obscure = !_obscure),
          icon: Icon(
            _obscure
                ? Icons.visibility_outlined
                : Icons.visibility_off_outlined,
          ),
        ),
      ),
      const SizedBox(height: 16),
      AppTextField(
        label: 'ยืนยันรหัสผ่านใหม่',
        controller: _confirmationCtrl,
        obscure: _obscure,
        errorText: _error,
      ),
      const SizedBox(height: 24),
      AppButton(
        label: 'ตั้งรหัสผ่านใหม่',
        loading: _loading,
        onTap: _resetPassword,
      ),
    ],
  );
}
