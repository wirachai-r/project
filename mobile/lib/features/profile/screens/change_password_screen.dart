import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_layout.dart';
import '../../../shared/widgets/app_text_field.dart';
import '../../auth/providers/auth_provider.dart';

class ChangePasswordScreen extends StatefulWidget {
  final String token;
  const ChangePasswordScreen({super.key, required this.token});

  @override
  State<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends State<ChangePasswordScreen> {
  final _currentCtrl = TextEditingController();
  final _newCtrl = TextEditingController();
  final _confirmCtrl = TextEditingController();
  bool _obscureCurrent = true;
  bool _obscureNew = true;
  bool _obscureConfirm = true;
  bool _isSaving = false;

  String? _currentError;
  String? _newError;
  String? _confirmError;

  @override
  void dispose() {
    _currentCtrl.dispose();
    _newCtrl.dispose();
    _confirmCtrl.dispose();
    super.dispose();
  }

  bool _validate() {
    setState(() {
      _currentError = _currentCtrl.text.isEmpty
          ? 'กรุณากรอกรหัสผ่านปัจจุบัน'
          : null;
      _newError = _newCtrl.text.length < 8
          ? 'รหัสผ่านควรมีความยาวอย่างน้อย 8 ตัวอักษร'
          : null;
      _confirmError = _confirmCtrl.text != _newCtrl.text
          ? 'รหัสผ่านไม่ตรงกัน'
          : null;
    });
    return _currentError == null && _newError == null && _confirmError == null;
  }

  Future<void> _save() async {
    if (!_validate()) return;
    setState(() => _isSaving = true);
    try {
      await context.read<AuthProvider>().changePassword(
        currentPassword: _currentCtrl.text,
        password: _newCtrl.text,
        passwordConfirmation: _confirmCtrl.text,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('เปลี่ยนรหัสผ่านสำเร็จ'),
          backgroundColor: AppColors.success,
        ),
      );
      Navigator.pop(context);
    } catch (error) {
      if (mounted) setState(() => _currentError = error.toString());
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    final hp = Responsive.horizontalPadding;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        leading: const BackButton(color: AppColors.textPrimary),
        title: Text('เปลี่ยนรหัสผ่าน', style: AppTextStyles.h4),
        centerTitle: true,
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, thickness: 1, color: AppColors.border),
        ),
      ),
      body: SingleChildScrollView(
        padding: EdgeInsets.symmetric(horizontal: hp),
        child: AppContentWidth(
          maxWidth: 560,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SizedBox(height: Responsive.dp(24)),

              // Description
              const AppInfoBanner(
                icon: Icons.lock_outline_rounded,
                message:
                    'กรุณากรอกรหัสผ่านปัจจุบันของคุณและตั้งรหัสผ่านใหม่เพื่อความปลอดภัยในการใช้งาน',
              ),

              SizedBox(height: Responsive.dp(28)),

              AppPanel(
                child: Column(
                  children: [
                    AppTextField(
                      label: 'รหัสผ่านปัจจุบัน',
                      hint: 'ป้อนรหัสผ่านปัจจุบัน',
                      controller: _currentCtrl,
                      obscure: _obscureCurrent,
                      errorText: _currentError,
                      textInputAction: TextInputAction.next,
                      suffixIcon: _visibilityButton(
                        obscure: _obscureCurrent,
                        onToggle: () =>
                            setState(() => _obscureCurrent = !_obscureCurrent),
                      ),
                    ),
                    SizedBox(height: Responsive.dp(20)),
                    AppTextField(
                      label: 'รหัสผ่านใหม่',
                      hint: 'ป้อนรหัสผ่านใหม่',
                      controller: _newCtrl,
                      obscure: _obscureNew,
                      errorText: _newError,
                      helperText: 'รหัสผ่านควรมีความยาวอย่างน้อย 8 ตัวอักษร',
                      textInputAction: TextInputAction.next,
                      suffixIcon: _visibilityButton(
                        obscure: _obscureNew,
                        onToggle: () =>
                            setState(() => _obscureNew = !_obscureNew),
                      ),
                    ),
                    SizedBox(height: Responsive.dp(20)),
                    AppTextField(
                      label: 'ยืนยันรหัสผ่านใหม่',
                      hint: 'ยืนยันรหัสผ่านใหม่อีกครั้ง',
                      controller: _confirmCtrl,
                      obscure: _obscureConfirm,
                      errorText: _confirmError,
                      textInputAction: TextInputAction.done,
                      onSubmitted: (_) => _isSaving ? null : _save(),
                      suffixIcon: _visibilityButton(
                        obscure: _obscureConfirm,
                        onToggle: () =>
                            setState(() => _obscureConfirm = !_obscureConfirm),
                      ),
                    ),
                  ],
                ),
              ),

              SizedBox(height: Responsive.dp(36)),

              AppButton(
                label: 'บันทึกข้อมูล',
                loading: _isSaving,
                onTap: _isSaving ? null : _save,
              ),

              SizedBox(height: Responsive.dp(32)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _visibilityButton({
    required bool obscure,
    required VoidCallback onToggle,
  }) => IconButton(
    tooltip: obscure ? 'แสดงรหัสผ่าน' : 'ซ่อนรหัสผ่าน',
    icon: Icon(
      obscure ? Icons.visibility_off_outlined : Icons.visibility_outlined,
      color: AppColors.textSecondary,
      size: 20,
    ),
    onPressed: onToggle,
  );
}
