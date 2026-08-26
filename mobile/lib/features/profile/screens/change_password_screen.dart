import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_button.dart';
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
        backgroundColor: AppColors.white,
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
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(height: Responsive.dp(24)),

            // Description
            Container(
              padding: EdgeInsets.all(Responsive.dp(16)),
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Row(
                children: [
                  Icon(Icons.lock_outline, color: AppColors.primary, size: 20),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'กรุณากรอกรหัสผ่านปัจจุบันของคุณและตั้งรหัสผ่านใหม่เพื่อความปลอดภัยในการใช้งาน',
                      style: AppTextStyles.body2.copyWith(
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            SizedBox(height: Responsive.dp(28)),

            _fieldLabel('รหัสผ่านปัจจุบัน'),
            SizedBox(height: Responsive.dp(8)),
            _passField(
              ctrl: _currentCtrl,
              hint: 'ป้อนรหัสผ่านปัจจุบัน',
              obscure: _obscureCurrent,
              onToggle: () =>
                  setState(() => _obscureCurrent = !_obscureCurrent),
              errorText: _currentError,
            ),

            SizedBox(height: Responsive.dp(20)),
            _fieldLabel('รหัสผ่านใหม่'),
            SizedBox(height: Responsive.dp(8)),
            _passField(
              ctrl: _newCtrl,
              hint: 'ป้อนรหัสผ่านใหม่',
              obscure: _obscureNew,
              onToggle: () => setState(() => _obscureNew = !_obscureNew),
              errorText: _newError,
              helperText: 'รหัสผ่านควรมีความยาวอย่างน้อย 8 ตัวอักษร',
            ),

            SizedBox(height: Responsive.dp(20)),
            _fieldLabel('ยืนยันรหัสผ่านใหม่'),
            SizedBox(height: Responsive.dp(8)),
            _passField(
              ctrl: _confirmCtrl,
              hint: 'ยืนยันรหัสผ่านใหม่อีกครั้ง',
              obscure: _obscureConfirm,
              onToggle: () =>
                  setState(() => _obscureConfirm = !_obscureConfirm),
              errorText: _confirmError,
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
    );
  }

  Widget _fieldLabel(String label) =>
      Text(label, style: AppTextStyles.body1Bold);

  Widget _passField({
    required TextEditingController ctrl,
    required String hint,
    required bool obscure,
    required VoidCallback onToggle,
    String? errorText,
    String? helperText,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        TextField(
          controller: ctrl,
          obscureText: obscure,
          style: AppTextStyles.body1,
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: AppTextStyles.body1.copyWith(color: AppColors.textHint),
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 20,
              vertical: 16,
            ),
            filled: true,
            fillColor: AppColors.white,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(30),
              borderSide: BorderSide(
                color: errorText != null ? AppColors.danger : AppColors.border,
              ),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(30),
              borderSide: BorderSide(
                color: errorText != null ? AppColors.danger : AppColors.border,
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(30),
              borderSide: BorderSide(
                color: errorText != null ? AppColors.danger : AppColors.primary,
                width: 1.5,
              ),
            ),
            suffixIcon: IconButton(
              icon: Icon(
                obscure
                    ? Icons.visibility_outlined
                    : Icons.visibility_off_outlined,
                color: AppColors.textSecondary,
                size: 20,
              ),
              onPressed: onToggle,
            ),
          ),
        ),
        if (errorText != null) ...[
          const SizedBox(height: 4),
          Padding(
            padding: const EdgeInsets.only(left: 16),
            child: Text(
              errorText,
              style: AppTextStyles.body3.copyWith(color: AppColors.danger),
            ),
          ),
        ] else if (helperText != null) ...[
          const SizedBox(height: 4),
          Padding(
            padding: const EdgeInsets.only(left: 16),
            child: Text(
              helperText,
              style: AppTextStyles.body3.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
          ),
        ],
      ],
    );
  }
}
