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
        child: AppContentWidth(
          maxWidth: 560,
          child: AppPanel(
            child: _resetToken == null ? _otpForm() : _passwordForm(),
          ),
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
        style: AppTextStyles.body2.copyWith(color: AppColors.textSecondary),
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
          if (_error != null) setState(() => _error = null);
        },
      ),
      const SizedBox(height: 12),
      Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(
            Icons.schedule_outlined,
            size: 16,
            color: AppColors.textSecondary,
          ),
          const SizedBox(width: 6),
          Text(
            'รหัสหมดอายุใน 10 นาที และกรอกได้ไม่เกิน 5 ครั้ง',
            style: AppTextStyles.body3.copyWith(color: AppColors.textSecondary),
          ),
        ],
      ),
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
          tooltip: _obscure ? 'แสดงรหัสผ่าน' : 'ซ่อนรหัสผ่าน',
          onPressed: () => setState(() => _obscure = !_obscure),
          icon: Icon(
            _obscure
                ? Icons.visibility_off_outlined
                : Icons.visibility_outlined,
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
  }

  @override
  void dispose() {
    widget.controller.removeListener(_refresh);
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
                                : AppColors.white,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: hasError
                                  ? AppColors.danger
                                  : active
                                  ? AppColors.primary
                                  : AppColors.border,
                              width: active ? 2 : 1,
                            ),
                          ),
                          child: Text(
                            index < code.length ? code[index] : '',
                            style: AppTextStyles.h3.copyWith(
                              color: AppColors.textPrimary,
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
