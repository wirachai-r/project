import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_button.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'edit_profile_screen.dart';
import 'change_password_screen.dart';

class ProfileScreen extends StatefulWidget {
  final String token;
  final VoidCallback onLogout;
  const ProfileScreen({super.key, required this.token, required this.onLogout});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  Map<String, dynamic>? _user;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  Future<void> _load() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.profile}'),
        headers: _headers,
      );
      if (res.statusCode == 200) {
        setState(() => _user = jsonDecode(res.body)['data']);
      }
    } catch (_) {}
    setState(() => _isLoading = false);
  }

  Future<void> _logout() async {
    widget.onLogout();
  }

  void _confirmLogout() {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text('ออกจากระบบ', style: AppTextStyles.h4),
        content: Text(
          'ต้องการออกจากระบบใช่หรือไม่?',
          style: AppTextStyles.body2,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text(
              'ยกเลิก',
              style: AppTextStyles.body2.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              _logout();
            },
            child: Text(
              'ออกจากระบบ',
              style: AppTextStyles.body1Bold.copyWith(color: AppColors.danger),
            ),
          ),
        ],
      ),
    );
  }

  int? _calculateAge(String? dob) {
    if (dob == null) return null;
    try {
      final birth = DateTime.parse(dob);
      final now = DateTime.now();
      int age = now.year - birth.year;
      if (now.month < birth.month ||
          (now.month == birth.month && now.day < birth.day))
        age--;
      return age;
    } catch (_) {
      return null;
    }
  }

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    final hp = Responsive.horizontalPadding;

    final name = '${_user?['first_name'] ?? ''} ${_user?['last_name'] ?? ''}'
        .trim();
    final initial = name.isNotEmpty ? name[0].toUpperCase() : '?';
    final age = _calculateAge(_user?['date_of_birth']);
    final sexLabel = _user?['sex'] == 'M'
        ? 'ชาย'
        : _user?['sex'] == 'F'
        ? 'หญิง'
        : '-';
    final email = _user?['email'] ?? '-';

    return Scaffold(
      backgroundColor: AppColors.surface,
      appBar: AppBar(
        backgroundColor: AppColors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        automaticallyImplyLeading: false,
        title: Text('ข้อมูลส่วนตัว', style: AppTextStyles.h4),
        centerTitle: true,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(0.5),
          child: Divider(height: 0.5, thickness: 0.5, color: AppColors.border),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
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
                    child: Column(
                      children: [
                        CircleAvatar(
                          radius: 44,
                          backgroundColor: AppColors.white,
                          backgroundImage: _user?['profile_image'] != null
                              ? NetworkImage(_user!['profile_image'])
                              : null,
                          child: _user?['profile_image'] == null
                              ? Text(
                                  initial,
                                  style: AppTextStyles.h2.copyWith(
                                    color: AppColors.primary,
                                  ),
                                )
                              : null,
                        ),
                        SizedBox(height: Responsive.dp(12)),
                        Text(
                          name.isNotEmpty ? name : 'ผู้ใช้งาน',
                          style: AppTextStyles.h3,
                        ),
                      ],
                    ),
                  ),

                  SizedBox(height: Responsive.dp(24)),

                  // Info section
                  Align(
                    alignment: Alignment.centerLeft,
                    child: Text(
                      'ข้อมูลส่วนตัว',
                      style: AppTextStyles.body1Bold,
                    ),
                  ),
                  SizedBox(height: Responsive.dp(12)),

                  _infoCard([
                    _InfoRow(
                      icon: Icons.cake_outlined,
                      label: 'อายุ',
                      value: age != null ? '$age ปี' : '-',
                    ),
                    _InfoRow(
                      icon: Icons.wc_outlined,
                      label: 'เพศ',
                      value: sexLabel,
                    ),
                    _InfoRow(
                      icon: Icons.email_outlined,
                      label: 'อีเมล',
                      value: email,
                    ),
                  ]),

                  SizedBox(height: Responsive.dp(24)),

                  // Edit button
                  AppButton(
                    label: 'แก้ไขข้อมูลส่วนตัว',
                    onTap: () async {
                      await Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => EditProfileScreen(
                            token: widget.token,
                            user: _user,
                          ),
                        ),
                      );
                      _load();
                    },
                  ),
                  SizedBox(height: Responsive.dp(12)),

                  // Change password button (outlined)
                  SizedBox(
                    width: double.infinity,
                    height: 52,
                    child: OutlinedButton(
                      onPressed: () => Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) =>
                              ChangePasswordScreen(token: widget.token),
                        ),
                      ),
                      style: OutlinedButton.styleFrom(
                        side: BorderSide(color: AppColors.border, width: 1.5),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(30),
                        ),
                      ),
                      child: Text(
                        'เปลี่ยนรหัสผ่าน',
                        style: AppTextStyles.body1Bold.copyWith(
                          color: AppColors.textPrimary,
                        ),
                      ),
                    ),
                  ),

                  SizedBox(height: Responsive.dp(24)),

                  // Logout section
                  Container(
                    decoration: BoxDecoration(
                      color: AppColors.white,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: ListTile(
                      leading: Container(
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          color: AppColors.danger.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Icon(
                          Icons.logout_outlined,
                          color: AppColors.danger,
                          size: 20,
                        ),
                      ),
                      title: Text(
                        'ออกจากระบบ',
                        style: AppTextStyles.body2.copyWith(
                          color: AppColors.danger,
                        ),
                      ),
                      trailing: const Icon(
                        Icons.chevron_right,
                        color: AppColors.danger,
                        size: 20,
                      ),
                      onTap: _confirmLogout,
                    ),
                  ),

                  SizedBox(height: Responsive.dp(32)),
                ],
              ),
            ),
    );
  }

  Widget _infoCard(List<_InfoRow> rows) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        children: rows.asMap().entries.map((e) {
          final i = e.key;
          final row = e.value;
          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 14,
                ),
                child: Row(
                  children: [
                    Container(
                      width: 36,
                      height: 36,
                      decoration: BoxDecoration(
                        color: AppColors.primaryLight,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Icon(row.icon, color: AppColors.primary, size: 18),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          row.label,
                          style: AppTextStyles.body3.copyWith(
                            color: AppColors.textSecondary,
                          ),
                        ),
                        Text(row.value, style: AppTextStyles.body1Bold),
                      ],
                    ),
                  ],
                ),
              ),
              if (i < rows.length - 1)
                const Divider(height: 0.5, indent: 64, endIndent: 16),
            ],
          );
        }).toList(),
      ),
    );
  }
}

class _InfoRow {
  final IconData icon;
  final String label;
  final String value;
  const _InfoRow({
    required this.icon,
    required this.label,
    required this.value,
  });
}
