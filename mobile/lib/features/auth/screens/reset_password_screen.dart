import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_text_field.dart';
import '../../../shared/widgets/app_layout.dart';
import '../providers/auth_provider.dart';

class ResetPasswordScreen extends StatefulWidget {
  final String initialEmail;
  final int otpExpiresIn;
  final int resendAvailableIn;

  const ResetPasswordScreen({
    super.key,
    this.initialEmail = '',
    this.otpExpiresIn = 0,
    this.resendAvailableIn = 0,
  });

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
  bool _resending = false;
  bool _otpLocked = false;
  bool _obscurePassword = true;
  bool _obscureConfirmation = true;
  Timer? _timer;
  DateTime? _expiresAt;
  DateTime? _resendAt;
  int _remainingSeconds = 0;
  int _resendSeconds = 0;

  @override
  void initState() {
    super.initState();
    _emailCtrl = TextEditingController(text: widget.initialEmail);
    _startOtpTimer(widget.otpExpiresIn, widget.resendAvailableIn);
  }

  @override
  void dispose() {
    _emailCtrl.dispose();
    _otpCtrl.dispose();
    _passwordCtrl.dispose();
    _confirmationCtrl.dispose();
    _timer?.cancel();
    super.dispose();
  }

  void _startOtpTimer(int expiresIn, int resendAvailableIn) {
    _timer?.cancel();
    final now = DateTime.now();
    _expiresAt = expiresIn > 0 ? now.add(Duration(seconds: expiresIn)) : null;
    _resendAt = resendAvailableIn > 0
        ? now.add(Duration(seconds: resendAvailableIn))
        : null;
    _updateCountdown();
    if (_expiresAt != null || _resendAt != null) {
      _timer = Timer.periodic(const Duration(seconds: 1), (_) {
        if (mounted) setState(_updateCountdown);
      });
    }
  }

  void _updateCountdown() {
    final now = DateTime.now();
    _remainingSeconds = _expiresAt == null
        ? 0
        : (_expiresAt!.difference(now).inSeconds + 1)
              .clamp(0, 300)
              .toInt();
    _resendSeconds = _resendAt == null
        ? 0
        : (_resendAt!.difference(now).inSeconds + 1).clamp(0, 60).toInt();
    if (_remainingSeconds == 0 && _resendSeconds == 0) _timer?.cancel();
  }

  String get _countdownLabel {
    final minutes = _remainingSeconds ~/ 60;
    final seconds = _remainingSeconds % 60;
    return '$minutes:${seconds.toString().padLeft(2, '0')}';
  }

  Future<void> _resendOtp() async {
    final email = _emailCtrl.text.trim();
    if (email.isEmpty || !email.contains('@')) {
      setState(() => _error = 'กรุณากรอกอีเมลให้ถูกต้อง');
      return;
    }
    setState(() {
      _resending = true;
      _error = null;
    });
    try {
      final timing = await context.read<AuthProvider>().forgotPassword(email);
      if (!mounted) return;
      _otpCtrl.clear();
      setState(() {
        _otpLocked = false;
        _startOtpTimer(timing.expiresIn, timing.resendAvailableIn);
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('ส่งรหัส OTP ใหม่แล้ว')),
      );
    } catch (error) {
      if (mounted) {
        final message = error.toString();
        setState(() {
          _error = message;
          _otpLocked = message.contains('ผิดครบจำนวนครั้งที่กำหนด') ||
              message.contains('OTP หมดอายุแล้ว') ||
              message.contains('OTP นี้ถูกใช้งานแล้ว');
        });
      }
    } finally {
      if (mounted) setState(() => _resending = false);
    }
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
      Navigator.of(context).pop(true);
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
        padding: EdgeInsets.symmetric(
          horizontal: Responsive.horizontalPadding,
          vertical: 24,
        ),
        child: AppContentWidth(
          maxWidth: 560,
          child: _resetToken == null
              ? AppPanel(child: _otpForm())
              : _passwordForm(),
        ),
      ),
    ),
  );

  Widget _otpForm() => Column(
    children: [
      Container(
        width: 88,
        height: 88,
        decoration: BoxDecoration(
          color: AppColors.primary.withValues(alpha: 0.10),
          shape: BoxShape.circle,
        ),
        child: const Icon(
          Icons.mark_email_read_outlined,
          size: 44,
          color: AppColors.primary,
        ),
      ),
      const SizedBox(height: 20),
      Text('ตรวจสอบอีเมลของคุณ', style: AppTextStyles.h3),
      const SizedBox(height: 8),
      Text(
        'กรอกรหัสยืนยัน 6 หลักที่ส่งไปยังอีเมล',
        textAlign: TextAlign.center,
        style: AppTextStyles.body2.copyWith(
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
      ),
      const SizedBox(height: 28),
      AppTextField(
        label: 'อีเมล',
        controller: _emailCtrl,
        keyboardType: TextInputType.emailAddress,
        readOnly: widget.initialEmail.isNotEmpty,
      ),
      const SizedBox(height: 20),
      Align(
        alignment: Alignment.centerLeft,
        child: Text('รหัส OTP', style: AppTextStyles.body2Bold),
      ),
      const SizedBox(height: 10),
      OtpCodeField(
        controller: _otpCtrl,
        errorText: _error,
        onSubmitted: _verifyOtp,
        onChanged: () {
          if (_error != null && !_otpLocked) setState(() => _error = null);
        },
      ),
      const SizedBox(height: 12),
      Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.schedule_outlined,
            size: 16,
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
          const SizedBox(width: 6),
          Text(
            _remainingSeconds > 0
                ? 'รหัสหมดอายุใน $_countdownLabel และกรอกได้ไม่เกิน 5 ครั้ง'
                : 'รหัสหมดอายุแล้ว กรุณาขอรหัสใหม่',
            style: AppTextStyles.body3.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
        ],
      ),
      const SizedBox(height: 24),
      AppButton(
        label: 'ยืนยัน OTP',
        loading: _loading,
        onTap: _remainingSeconds > 0 && !_otpLocked ? _verifyOtp : null,
      ),
      const SizedBox(height: 8),
      TextButton(
        onPressed: _loading || _resending || _resendSeconds > 0
            ? null
            : _resendOtp,
        child: Text(
          _resending
              ? 'กำลังส่ง...'
              : _resendSeconds > 0
              ? 'ส่งรหัสใหม่ได้ใน $_resendSeconds วินาที'
              : 'ไม่ได้รับรหัส? ส่งรหัสใหม่',
        ),
      ),
    ],
  );

  Widget _passwordForm() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const AppInfoBanner(
        icon: Icons.lock_outline_rounded,
        message:
            'ยืนยันอีเมลสำเร็จแล้ว กรุณาตั้งรหัสผ่านใหม่เพื่อความปลอดภัยในการใช้งาน',
      ),
      const SizedBox(height: 24),
      AppPanel(
        child: Column(
          children: [
            AppTextField(
              label: 'รหัสผ่านใหม่',
              hint: 'ป้อนรหัสผ่านใหม่',
              controller: _passwordCtrl,
              obscure: _obscurePassword,
              helperText: 'รหัสผ่านควรมีความยาวอย่างน้อย 8 ตัวอักษร',
              textInputAction: TextInputAction.next,
              suffixIcon: _visibilityButton(
                obscure: _obscurePassword,
                onToggle: () => setState(
                  () => _obscurePassword = !_obscurePassword,
                ),
              ),
            ),
            const SizedBox(height: 20),
            AppTextField(
              label: 'ยืนยันรหัสผ่านใหม่',
              hint: 'ยืนยันรหัสผ่านใหม่อีกครั้ง',
              controller: _confirmationCtrl,
              obscure: _obscureConfirmation,
              errorText: _error,
              textInputAction: TextInputAction.done,
              onSubmitted: (_) => _loading ? null : _resetPassword(),
              suffixIcon: _visibilityButton(
                obscure: _obscureConfirmation,
                onToggle: () => setState(
                  () => _obscureConfirmation = !_obscureConfirmation,
                ),
              ),
            ),
          ],
        ),
      ),
      const SizedBox(height: 28),
      AppButton(
        label: 'ตั้งรหัสผ่านใหม่',
        loading: _loading,
        onTap: _loading ? null : _resetPassword,
      ),
      const SizedBox(height: 24),
    ],
  );

  Widget _visibilityButton({
    required bool obscure,
    required VoidCallback onToggle,
  }) => IconButton(
    tooltip: obscure ? 'แสดงรหัสผ่าน' : 'ซ่อนรหัสผ่าน',
    onPressed: onToggle,
    icon: Icon(
      obscure ? Icons.visibility_off_outlined : Icons.visibility_outlined,
      size: 20,
      color: Theme.of(context).colorScheme.onSurfaceVariant,
    ),
  );
}

class OtpCodeField extends StatefulWidget {
  final TextEditingController controller;
  final String? errorText;
  final VoidCallback onChanged;
  final VoidCallback onSubmitted;

  const OtpCodeField({
    required this.controller,
    required this.errorText,
    required this.onChanged,
    required this.onSubmitted,
  });

  @override
  State<OtpCodeField> createState() => _OtpCodeFieldState();
}

class _OtpCodeFieldState extends State<OtpCodeField> {
  final _focusNode = FocusNode();
  Timer? _cursorTimer;
  bool _cursorVisible = true;

  void _selectDigit(TapDownDetails details, double fieldWidth) {
    final codeLength = widget.controller.text.length;
    final tappedIndex = (details.localPosition.dx / (fieldWidth / 6))
        .floor()
        .clamp(0, 5);

    _focusNode.requestFocus();
    if (tappedIndex < codeLength) {
      widget.controller.selection = TextSelection(
        baseOffset: tappedIndex,
        extentOffset: tappedIndex + 1,
      );
    } else {
      widget.controller.selection = TextSelection.collapsed(offset: codeLength);
    }
  }

  @override
  void initState() {
    super.initState();
    widget.controller.addListener(_refresh);
    _focusNode.addListener(_refresh);
    _cursorTimer = Timer.periodic(const Duration(milliseconds: 550), (_) {
      if (mounted && _focusNode.hasFocus) {
        setState(() => _cursorVisible = !_cursorVisible);
      }
    });
  }

  @override
  void dispose() {
    widget.controller.removeListener(_refresh);
    _cursorTimer?.cancel();
    _focusNode
      ..removeListener(_refresh)
      ..dispose();
    super.dispose();
  }

  void _refresh() {
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final code = widget.controller.text;
    final hasError = widget.errorText?.isNotEmpty == true;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Semantics(
          label: 'รหัส OTP 6 หลัก',
          textField: true,
          child: LayoutBuilder(
            builder: (context, constraints) => GestureDetector(
              behavior: HitTestBehavior.opaque,
              onTapDown: (details) =>
                  _selectDigit(details, constraints.maxWidth),
              child: Stack(
                children: [
                  Row(
                    children: List.generate(6, (index) {
                      final selection = widget.controller.selection;
                      final active =
                          _focusNode.hasFocus &&
                          ((selection.isValid &&
                                  selection.start <= index &&
                                  selection.end > index) ||
                              (selection.isCollapsed &&
                                  selection.start == index));
                      return Expanded(
                        child: Container(
                          height: 58,
                          margin: EdgeInsets.only(right: index == 5 ? 0 : 8),
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                            color: active
                                ? AppColors.primary.withValues(alpha: 0.06)
                                : Theme.of(context).colorScheme.surface,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: hasError
                                  ? AppColors.danger
                                  : active
                                  ? AppColors.primary
                                  : Theme.of(
                                      context,
                                    ).colorScheme.outlineVariant,
                              width: active ? 2 : 1,
                            ),
                          ),
                          child: active && index >= code.length
                              ? AnimatedOpacity(
                                  opacity: _cursorVisible ? 1 : 0,
                                  duration: const Duration(milliseconds: 120),
                                  child: Container(
                                    width: 2,
                                    height: 26,
                                    decoration: BoxDecoration(
                                      color: AppColors.primary,
                                      borderRadius: BorderRadius.circular(2),
                                    ),
                                  ),
                                )
                              : Text(
                                  index < code.length ? code[index] : '',
                                  style: AppTextStyles.h3.copyWith(
                                    color: Theme.of(
                                      context,
                                    ).colorScheme.onSurface,
                                  ),
                                ),
                        ),
                      );
                    }),
                  ),
                  Positioned.fill(
                    child: IgnorePointer(
                      child: Opacity(
                        opacity: 0.01,
                        child: TextField(
                          controller: widget.controller,
                          focusNode: _focusNode,
                          keyboardType: TextInputType.number,
                          textInputAction: TextInputAction.done,
                          autofillHints: const [AutofillHints.oneTimeCode],
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                            LengthLimitingTextInputFormatter(6),
                          ],
                          onChanged: (_) => widget.onChanged(),
                          onSubmitted: (_) => widget.onSubmitted(),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
        if (hasError)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: Text(
              widget.errorText!,
              style: AppTextStyles.body3.copyWith(color: AppColors.danger),
            ),
          ),
      ],
    );
  }
}
