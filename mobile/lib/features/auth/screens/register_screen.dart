import 'package:flutter/material.dart';
import 'package:provider/provider.dart'; // เพิ่ม
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/buddhist_calendar_delegate.dart';
import '../../../core/utils/responsive.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_text_field.dart';
import '../../../shared/widgets/app_layout.dart';
import '../providers/auth_provider.dart'; // เพิ่ม
import 'login_screen.dart';
import 'registration_otp_screen.dart';

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

  String get _formattedDate =>
      _selectedDate == null ? '' : formatThaiDate(_selectedDate!);

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
        MaterialPageRoute(
          builder: (_) => RegistrationOtpScreen(email: _emailCtrl.text.trim()),
        ),
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
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        appBar: AppBar(
          backgroundColor: Theme.of(context).scaffoldBackgroundColor,
          elevation: 0,
          surfaceTintColor: Colors.transparent,
          leading: BackButton(color: Theme.of(context).colorScheme.onSurface),
          title: Text('สร้างบัญชีใหม่', style: AppTextStyles.h4),
          centerTitle: true,
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
          ),
          child: AppContentWidth(
            maxWidth: 620,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                SizedBox(height: Responsive.dp(16)),
                Text('เข้าร่วมกับเรา', style: AppTextStyles.h3),
                SizedBox(height: Responsive.dp(24)),
                _ResponsiveFieldPair(
                  first: AppTextField(
                    label: 'ชื่อ',
                    hint: 'ชื่อจริง',
                    controller: _firstNameCtrl,
                    errorText: _firstNameError,
                    textInputAction: TextInputAction.next,
                    autofillHints: const [AutofillHints.givenName],
                  ),
                  second: AppTextField(
                    label: 'นามสกุล',
                    hint: 'นามสกุล',
                    controller: _lastNameCtrl,
                    errorText: _lastNameError,
                    textInputAction: TextInputAction.next,
                    autofillHints: const [AutofillHints.familyName],
                  ),
                ),
                SizedBox(height: Responsive.dp(16)),
                AppTextField(
                  label: 'อีเมล',
                  hint: 'กรอกอีเมลของคุณ',
                  controller: _emailCtrl,
                  keyboardType: TextInputType.emailAddress,
                  errorText: _emailError,
                  textInputAction: TextInputAction.next,
                  autofillHints: const [AutofillHints.email],
                ),
                SizedBox(height: Responsive.dp(16)),
                AppTextField(
                  label: 'รหัสผ่าน',
                  hint: 'กรอกรหัสผ่านของคุณ',
                  controller: _passCtrl,
                  obscure: _obscurePass,
                  helperText: 'รหัสผ่านควรมีความยาวอย่างน้อย 8 ตัวอักษร',
                  errorText: _passError,
                  textInputAction: TextInputAction.next,
                  autofillHints: const [AutofillHints.newPassword],
                  suffixIcon: IconButton(
                    tooltip: _obscurePass ? 'แสดงรหัสผ่าน' : 'ซ่อนรหัสผ่าน',
                    icon: Icon(
                      _obscurePass
                          ? Icons.visibility_off_outlined
                          : Icons.visibility_outlined,
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                    onPressed: () =>
                        setState(() => _obscurePass = !_obscurePass),
                  ),
                ),
                SizedBox(height: Responsive.dp(16)),
                AppTextField(
                  label: 'ยืนยันรหัสผ่าน',
                  hint: 'กรอกรหัสผ่านของคุณ',
                  controller: _confirmCtrl,
                  obscure: _obscureConfirm,
                  errorText: _confirmError,
                  textInputAction: TextInputAction.done,
                  autofillHints: const [AutofillHints.newPassword],
                  onSubmitted: (_) => isLoading ? null : _submit(),
                  suffixIcon: IconButton(
                    tooltip: _obscureConfirm ? 'แสดงรหัสผ่าน' : 'ซ่อนรหัสผ่าน',
                    icon: Icon(
                      _obscureConfirm
                          ? Icons.visibility_off_outlined
                          : Icons.visibility_outlined,
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
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
                            calendarDelegate: const BuddhistCalendarDelegate(),
                            initialDate: DateTime(2000),
                            firstDate: DateTime(1950),
                            lastDate: DateTime.now(),
                          );
                          if (picked != null) {
                            setState(() => _selectedDate = picked);
                          }
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
                          DropdownButtonFormField<String>(
                            initialValue: _selectedGender,
                            isExpanded: true,
                            style: AppTextStyles.body2.copyWith(
                              color: Theme.of(context).colorScheme.onSurface,
                            ),
                            decoration: const InputDecoration(
                              hintText: 'เลือกเพศ',
                            ),
                            items: const [
                              DropdownMenuItem(value: 'M', child: Text('ชาย')),
                              DropdownMenuItem(value: 'F', child: Text('หญิง')),
                            ],
                            onChanged: (v) =>
                                setState(() => _selectedGender = v),
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
                Center(
                  child: Wrap(
                    alignment: WrapAlignment.center,
                    crossAxisAlignment: WrapCrossAlignment.center,
                    children: [
                      Text('มีบัญชีอยู่แล้ว?', style: AppTextStyles.body2),
                      TextButton(
                        onPressed: () => Navigator.of(context).pushReplacement(
                          MaterialPageRoute(
                            builder: (_) => const LoginScreen(),
                          ),
                        ),
                        child: const Text('เข้าสู่ระบบ'),
                      ),
                    ],
                  ),
                ),
                SizedBox(height: Responsive.dp(32)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _ResponsiveFieldPair extends StatelessWidget {
  final Widget first;
  final Widget second;

  const _ResponsiveFieldPair({required this.first, required this.second});

  @override
  Widget build(BuildContext context) => LayoutBuilder(
    builder: (context, constraints) {
      final textScale = MediaQuery.textScalerOf(context).scale(14) / 14;
      if (constraints.maxWidth < 360 || textScale > 1.2) {
        return Column(
          children: [
            first,
            SizedBox(height: Responsive.dp(16)),
            second,
          ],
        );
      }
      return Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(child: first),
          SizedBox(width: Responsive.dp(12)),
          Expanded(child: second),
        ],
      );
    },
  );
}
