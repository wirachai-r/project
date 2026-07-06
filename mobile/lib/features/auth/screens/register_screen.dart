import 'package:flutter/material.dart';
import 'package:provider/provider.dart'; // เพิ่ม
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../core/errors/app_exception.dart'; // เพิ่ม
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_text_field.dart';
import '../providers/auth_provider.dart'; // เพิ่ม
import 'login_screen.dart';
import '../../home/screens/home_screen.dart'; // เพิ่ม

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _firstNameCtrl = TextEditingController();
  final _lastNameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  final _confirmCtrl = TextEditingController();
  bool _obscurePass = true;
  bool _obscureConfirm = true;
  String? _selectedGender;
  DateTime? _selectedDate;

  // error messages แยกแต่ละ field
  String? _firstNameError;
  String? _lastNameError;
  String? _emailError;
  String? _passError;
  String? _confirmError;

  String get _formattedDate => _selectedDate == null
      ? ''
      : '${_selectedDate!.day}/${_selectedDate!.month}/${_selectedDate!.year}';

  // validate ก่อนส่ง
  bool _validate() {
    setState(() {
      _firstNameError = _firstNameCtrl.text.trim().isEmpty
          ? 'กรุณากรอกชื่อ'
          : null;
      _lastNameError = _lastNameCtrl.text.trim().isEmpty
          ? 'กรุณากรอกนามสกุล'
          : null;
      _emailError = _emailCtrl.text.trim().isEmpty ? 'กรุณากรอกอีเมล' : null;
      _passError = _passCtrl.text.length < 8
          ? 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร'
          : null;
      _confirmError = _confirmCtrl.text != _passCtrl.text
          ? 'รหัสผ่านไม่ตรงกัน'
          : null;
    });
    return _firstNameError == null &&
        _lastNameError == null &&
        _emailError == null &&
        _passError == null &&
        _confirmError == null;
  }

  Future<void> _submit() async {
    if (!_validate()) return;

    final provider = context.read<AuthProvider>();

    final success = await provider.register(
      firstName: _firstNameCtrl.text.trim(),
      lastName: _lastNameCtrl.text.trim(),
      email: _emailCtrl.text.trim(),
      password: _passCtrl.text,
      passwordConfirmation: _confirmCtrl.text,
      sex: _selectedGender,
      dateOfBirth: _selectedDate != null
          ? '${_selectedDate!.year}-${_selectedDate!.month.toString().padLeft(2, '0')}-${_selectedDate!.day.toString().padLeft(2, '0')}'
          : null,
    );

    if (!mounted) return;

    if (success) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => const HomeScreen()),
      );
    } else {
      // ดึง validation errors จาก API (422)
      final errors = provider.validationErrors;
      setState(() {
        _firstNameError = errors['first_name']?.first;
        _lastNameError = errors['last_name']?.first;
        _emailError = errors['email']?.first;
        _passError = errors['password']?.first;
      });

      // แสดง error message ทั่วไป
      if (errors.isEmpty && provider.errorMessage != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(provider.errorMessage!),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    }
  }

  @override
  void dispose() {
    _firstNameCtrl.dispose();
    _lastNameCtrl.dispose();
    _emailCtrl.dispose();
    _passCtrl.dispose();
    _confirmCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isLoading =
        context.watch<AuthProvider>().status == AuthStatus.loading;

    return ResponsiveBuilder(
      builder: (context) => Scaffold(
        backgroundColor: AppColors.white,
        appBar: AppBar(
          backgroundColor: AppColors.white,
          elevation: 0,
          surfaceTintColor: Colors.transparent,
          leading: const BackButton(color: AppColors.textPrimary),
          title: Text('สร้างบัญชีใหม่', style: AppTextStyles.h4),
          centerTitle: true,
          bottom: PreferredSize(
            preferredSize: const Size.fromHeight(0.5),
            child: Divider(
              height: 0.5,
              thickness: 0.5,
              color: AppColors.border,
            ),
          ),
        ),
        body: SingleChildScrollView(
          padding: EdgeInsets.symmetric(
            horizontal: Responsive.horizontalPadding,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SizedBox(height: Responsive.dp(16)),
              Text('เข้าร่วมกับเรา', style: AppTextStyles.h3),
              SizedBox(height: Responsive.dp(24)),
              Row(
                children: [
                  Expanded(
                    child: AppTextField(
                      label: 'ชื่อ',
                      hint: 'ชื่อจริง',
                      controller: _firstNameCtrl,
                      errorText: _firstNameError, // เพิ่ม
                    ),
                  ),
                  SizedBox(width: Responsive.dp(12)),
                  Expanded(
                    child: AppTextField(
                      label: 'นามสกุล',
                      hint: 'นามสกุล',
                      controller: _lastNameCtrl,
                      errorText: _lastNameError, // เพิ่ม
                    ),
                  ),
                ],
              ),
              SizedBox(height: Responsive.dp(16)),
              AppTextField(
                label: 'อีเมล',
                hint: 'กรอกอีเมลของคุณ',
                controller: _emailCtrl,
                keyboardType: TextInputType.emailAddress,
                errorText: _emailError, // เพิ่ม
              ),
              SizedBox(height: Responsive.dp(16)),
              AppTextField(
                label: 'รหัสผ่าน',
                hint: 'กรอกรหัสผ่านของคุณ',
                controller: _passCtrl,
                obscure: _obscurePass,
                helperText: 'รหัสผ่านควรมีความยาวอย่างน้อย 8 ตัวอักษร',
                errorText: _passError, // เพิ่ม
                suffixIcon: IconButton(
                  icon: Icon(
                    _obscurePass
                        ? Icons.visibility_outlined
                        : Icons.visibility_off_outlined,
                    color: AppColors.textSecondary,
                  ),
                  onPressed: () => setState(() => _obscurePass = !_obscurePass),
                ),
              ),
              SizedBox(height: Responsive.dp(16)),
              AppTextField(
                label: 'ยืนยันรหัสผ่าน',
                hint: 'กรอกรหัสผ่านของคุณ',
                controller: _confirmCtrl,
                obscure: _obscureConfirm,
                errorText: _confirmError, // เพิ่ม
                suffixIcon: IconButton(
                  icon: Icon(
                    _obscureConfirm
                        ? Icons.visibility_outlined
                        : Icons.visibility_off_outlined,
                    color: AppColors.textSecondary,
                  ),
                  onPressed: () =>
                      setState(() => _obscureConfirm = !_obscureConfirm),
                ),
              ),
              SizedBox(height: Responsive.dp(16)),

              // วันเกิด + เพศ (เหมือนเดิม)
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: AppTextField(
                      label: 'วัน/เดือน/ปีเกิด',
                      hint: 'เลือกวันที่',
                      controller: TextEditingController(text: _formattedDate),
                      readOnly: true,
                      onTap: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: DateTime(2000),
                          firstDate: DateTime(1950),
                          lastDate: DateTime.now(),
                          builder: (ctx, child) => Theme(
                            data: Theme.of(ctx).copyWith(
                              colorScheme: const ColorScheme.light(
                                primary: AppColors.primary,
                              ),
                            ),
                            child: child!,
                          ),
                        );
                        if (picked != null)
                          setState(() => _selectedDate = picked);
                      },
                    ),
                  ),
                  SizedBox(width: Responsive.dp(12)),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('เพศ', style: AppTextStyles.body2Bold),
                        SizedBox(height: Responsive.dp(8)),
                        Container(
                          height: 52,
                          padding: const EdgeInsets.symmetric(horizontal: 20),
                          decoration: BoxDecoration(
                            border: Border.all(color: AppColors.border),
                            borderRadius: BorderRadius.circular(30),
                            color: AppColors.white,
                          ),
                          child: DropdownButtonHideUnderline(
                            child: DropdownButton<String>(
                              value: _selectedGender,
                              hint: Text(
                                'เลือกเพศ',
                                style: AppTextStyles.body2.copyWith(
                                  color: AppColors.textHint,
                                ),
                              ),
                              isExpanded: true,
                              items: const [
                                DropdownMenuItem(
                                  value: 'M',
                                  child: Text('ชาย'),
                                ),
                                DropdownMenuItem(
                                  value: 'F',
                                  child: Text('หญิง'),
                                ),
                              ],
                              onChanged: (v) =>
                                  setState(() => _selectedGender = v),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              SizedBox(height: Responsive.dp(32)),
              AppButton(
                label: 'สมัครสมาชิก',
                loading: isLoading,
                onTap: isLoading ? null : _submit, // เปลี่ยน
              ),
              SizedBox(height: Responsive.dp(20)),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    'มีบัญชีอยู่แล้ว? ',
                    style: AppTextStyles.body2.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                  GestureDetector(
                    onTap: () => Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const LoginScreen()),
                    ),
                    child: Text(
                      'เข้าสู่ระบบ',
                      style: AppTextStyles.body2Bold.copyWith(
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                ],
              ),
              SizedBox(height: Responsive.dp(32)),
            ],
          ),
        ),
      ),
    );
  }
}
