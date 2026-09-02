import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_logo.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_text_field.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../auth/providers/auth_provider.dart';
import 'register_screen.dart';
import 'forgot_password_screen.dart';
import 'registration_otp_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  bool _obscure = true;

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    final email = _emailCtrl.text.trim();
    final password = _passCtrl.text;

    final auth = context.read<AuthProvider>();
    final success = await auth.login(email: email, password: password);

    if (!mounted) return;

    if (success) {
      showAppSuccess(context, 'เข้าสู่ระบบสำเร็จ');
      Navigator.of(context).pop();
    } else {
      if (auth.errorMessage?.contains('ยืนยันอีเมล') == true) {
        try {
          await auth.resendRegistrationOtp(email);
        } catch (_) {
          // The verification screen can request a new code again.
        }
        if (!mounted) return;
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => RegistrationOtpScreen(email: email),
          ),
        );
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(auth.errorMessage ?? 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  Future<void> _loginWithGoogle() async {
    final auth = context.read<AuthProvider>();
    final result = await auth.loginWithGoogle();
    if (!mounted || result == null) return;

    if (result) {
      showAppSuccess(context, 'เข้าสู่ระบบด้วย Google สำเร็จ');
      Navigator.of(context).pop();
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          auth.errorMessage ?? 'ไม่สามารถเข้าสู่ระบบด้วย Google ได้',
        ),
        backgroundColor: AppColors.danger,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isLoading =
        context.watch<AuthProvider>().status == AuthStatus.loading;

    return ResponsiveBuilder(
      builder: (context) => Scaffold(
        backgroundColor: AppColors.background,
        appBar: AppBar(
          backgroundColor: AppColors.white,
          elevation: 0,
          surfaceTintColor: Colors.transparent,
          leading: const BackButton(color: AppColors.textPrimary),
          title: Text('เข้าสู่ระบบ', style: AppTextStyles.h4),
          centerTitle: true,
          bottom: const PreferredSize(
            preferredSize: Size.fromHeight(1),
            child: Divider(height: 1, thickness: 1, color: AppColors.border),
          ),
        ),
        body: SafeArea(
          child: SingleChildScrollView(
            padding: EdgeInsets.symmetric(
              horizontal: Responsive.horizontalPadding,
            ),
            child: AutofillGroup(
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    SizedBox(height: Responsive.dp(16)),
                    // ลบ Text('เข้าสู่ระบบ') และ Center ออก
                    Center(
                      child: AppLogo(
                        size: Responsive.dp(100),
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
                      textInputAction: TextInputAction.next,
                      autofillHints: const [AutofillHints.email],
                      validator: (value) {
                        final email = value?.trim() ?? '';
                        if (email.isEmpty) return 'กรุณากรอกอีเมล';
                        if (!email.contains('@')) {
                          return 'รูปแบบอีเมลไม่ถูกต้อง';
                        }
                        return null;
                      },
                    ),
                    SizedBox(height: Responsive.dp(16)),
                    AppTextField(
                      label: 'รหัสผ่าน',
                      hint: 'กรอกรหัสผ่านของคุณ',
                      controller: _passCtrl,
                      obscure: _obscure,
                      textInputAction: TextInputAction.done,
                      autofillHints: const [AutofillHints.password],
                      onSubmitted: (_) => isLoading ? null : _login(),
                      validator: (value) => value?.isNotEmpty == true
                          ? null
                          : 'กรุณากรอกรหัสผ่าน',
                      suffixIcon: IconButton(
                        tooltip: _obscure ? 'แสดงรหัสผ่าน' : 'ซ่อนรหัสผ่าน',
                        icon: Icon(
                          _obscure
                              ? Icons.visibility_off_outlined
                              : Icons.visibility_outlined,
                          color: AppColors.textSecondary,
                        ),
                        onPressed: () => setState(() => _obscure = !_obscure),
                      ),
                    ),
                    SizedBox(height: Responsive.dp(12)),
                    Align(
                      alignment: Alignment.centerRight,
                      child: TextButton(
                        onPressed: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const ForgotPasswordScreen(),
                          ),
                        ),
                        child: const Text('ลืมรหัสผ่าน?'),
                      ),
                    ),
                    SizedBox(height: Responsive.dp(24)),
                    AppButton(
                      label: 'เข้าสู่ระบบ',
                      loading: isLoading,
                      onTap: isLoading ? null : _login,
                    ),
                    SizedBox(height: Responsive.dp(20)),
                    Row(
                      children: [
                        const Expanded(child: Divider(color: AppColors.border)),
                        Padding(
                          padding: EdgeInsets.symmetric(
                            horizontal: Responsive.dp(12),
                          ),
                          child: Text(
                            'หรือ',
                            style: AppTextStyles.body2.copyWith(
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ),
                        const Expanded(child: Divider(color: AppColors.border)),
                      ],
                    ),
                    SizedBox(height: Responsive.dp(20)),
                    SizedBox(
                      width: double.infinity,
                      height: Responsive.dp(52),
                      child: DecoratedBox(
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: [
                            BoxShadow(
                              color: AppColors.textPrimary.withValues(
                                alpha: 0.08,
                              ),
                              blurRadius: 8,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: OutlinedButton(
                          onPressed: isLoading ? null : _loginWithGoogle,
                          style: OutlinedButton.styleFrom(
                            foregroundColor: AppColors.textPrimary,
                            backgroundColor: AppColors.white,
                            side: const BorderSide(color: AppColors.border),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              SvgPicture.asset(
                                'images/google_g_logo.svg',
                                width: 20,
                                height: 20,
                              ),
                              SizedBox(width: Responsive.dp(10)),
                              Text(
                                'เข้าสู่ระบบด้วย Google',
                                style: AppTextStyles.body1Bold,
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    SizedBox(height: Responsive.dp(24)),
                    Center(
                      child: Wrap(
                        alignment: WrapAlignment.center,
                        crossAxisAlignment: WrapCrossAlignment.center,
                        children: [
                          Text('ยังไม่มีบัญชี?', style: AppTextStyles.body2),
                          TextButton(
                            onPressed: () => Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => const RegisterScreen(),
                              ),
                            ),
                            child: const Text('สมัครสมาชิก'),
                          ),
                        ],
                      ),
                    ),
                    SizedBox(height: Responsive.dp(24)),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
