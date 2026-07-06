import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_button.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class EditProfileScreen extends StatefulWidget {
  final String token;
  final Map<String, dynamic>? user;
  const EditProfileScreen({super.key, required this.token, this.user});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _firstNameCtrl = TextEditingController();
  final _lastNameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _firstNameCtrl.text = widget.user?['first_name'] ?? '';
    _lastNameCtrl.text = widget.user?['last_name'] ?? '';
    _phoneCtrl.text = widget.user?['phone'] ?? '';
  }

  @override
  void dispose() {
    _firstNameCtrl.dispose();
    _lastNameCtrl.dispose();
    _phoneCtrl.dispose();
    super.dispose();
  }

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  Future<void> _save() async {
    setState(() => _isSaving = true);
    try {
      final res = await http.put(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.profile}'),
        headers: _headers,
        body: jsonEncode({
          'first_name': _firstNameCtrl.text.trim(),
          'last_name': _lastNameCtrl.text.trim(),
          'phone': _phoneCtrl.text.trim(),
        }),
      );
      if (!mounted) return;
      if (res.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('บันทึกข้อมูลสำเร็จ'),
            backgroundColor: AppColors.success,
          ),
        );
        Navigator.pop(context);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('เกิดข้อผิดพลาด กรุณาลองใหม่'),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    } catch (_) {
      if (mounted)
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('ไม่สามารถเชื่อมต่อได้'),
            backgroundColor: AppColors.danger,
          ),
        );
    }
    setState(() => _isSaving = false);
  }

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    final hp = Responsive.horizontalPadding;
    final user = widget.user;
    final name = '${user?['first_name'] ?? ''} ${user?['last_name'] ?? ''}'
        .trim();
    final initial = name.isNotEmpty ? name[0].toUpperCase() : '?';

    return Scaffold(
      backgroundColor: AppColors.surface,
      appBar: AppBar(
        backgroundColor: AppColors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        leading: const BackButton(color: AppColors.textPrimary),
        title: Text('แก้ไขข้อมูลส่วนตัว', style: AppTextStyles.h4),
        centerTitle: true,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(0.5),
          child: Divider(height: 0.5, thickness: 0.5, color: AppColors.border),
        ),
      ),
      body: SingleChildScrollView(
        padding: EdgeInsets.symmetric(horizontal: hp),
        child: Column(
          children: [
            SizedBox(height: Responsive.dp(24)),

            // Avatar card
            Container(
              width: double.infinity,
              padding: EdgeInsets.symmetric(vertical: Responsive.dp(28)),
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(20),
              ),
              child: Center(
                child: Stack(
                  children: [
                    CircleAvatar(
                      radius: 44,
                      backgroundColor: AppColors.white,
                      backgroundImage: user?['profile_image'] != null
                          ? NetworkImage(user!['profile_image'])
                          : null,
                      child: user?['profile_image'] == null
                          ? Text(
                              initial,
                              style: AppTextStyles.h2.copyWith(
                                color: AppColors.primary,
                              ),
                            )
                          : null,
                    ),
                    Positioned(
                      bottom: 0,
                      right: 0,
                      child: Container(
                        width: 30,
                        height: 30,
                        decoration: BoxDecoration(
                          color: AppColors.primary,
                          shape: BoxShape.circle,
                          border: Border.all(color: AppColors.white, width: 2),
                        ),
                        child: const Icon(
                          Icons.camera_alt_outlined,
                          size: 16,
                          color: AppColors.white,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),

            SizedBox(height: Responsive.dp(28)),

            // Form
            _fieldLabel('ชื่อ-นามสกุล'),
            SizedBox(height: Responsive.dp(8)),
            Row(
              children: [
                Expanded(child: _textField(_firstNameCtrl, 'ชื่อ')),
                SizedBox(width: Responsive.dp(10)),
                Expanded(child: _textField(_lastNameCtrl, 'นามสกุล')),
              ],
            ),

            SizedBox(height: Responsive.dp(20)),
            _fieldLabel('เบอร์โทรศัพท์'),
            SizedBox(height: Responsive.dp(8)),
            _textField(
              _phoneCtrl,
              'กรอกเบอร์โทรศัพท์',
              inputType: TextInputType.phone,
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

  Widget _fieldLabel(String label) => Align(
    alignment: Alignment.centerLeft,
    child: Text(label, style: AppTextStyles.body1Bold),
  );

  Widget _textField(
    TextEditingController ctrl,
    String hint, {
    TextInputType? inputType,
  }) {
    return TextField(
      controller: ctrl,
      keyboardType: inputType,
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
          borderSide: BorderSide(color: AppColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(30),
          borderSide: BorderSide(color: AppColors.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(30),
          borderSide: BorderSide(color: AppColors.primary, width: 1.5),
        ),
      ),
    );
  }
}
