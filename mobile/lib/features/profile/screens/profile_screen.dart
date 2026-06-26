import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

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
  bool _isEditing = false;

  final _firstNameCtrl = TextEditingController();
  final _lastNameCtrl  = TextEditingController();
  final _phoneCtrl     = TextEditingController();
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _load();
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

  Future<void> _load() async {
    setState(() => _isLoading = true);
    final res = await http.get(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.profile}'),
      headers: _headers,
    );
    if (res.statusCode == 200) {
      final user = jsonDecode(res.body)['data'];
      setState(() {
        _user = user;
        _firstNameCtrl.text = user['first_name'] ?? '';
        _lastNameCtrl.text  = user['last_name'] ?? '';
        _phoneCtrl.text     = user['phone'] ?? '';
      });
    }
    setState(() => _isLoading = false);
  }

  Future<void> _save() async {
    setState(() => _isSaving = true);
    final res = await http.put(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.profile}'),
      headers: _headers,
      body: jsonEncode({
        'first_name': _firstNameCtrl.text,
        'last_name':  _lastNameCtrl.text,
        'phone':      _phoneCtrl.text,
      }),
    );
    if (res.statusCode == 200) {
      setState(() {
        _user = jsonDecode(res.body)['data'];
        _isEditing = false;
      });
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('บันทึกข้อมูลสำเร็จ'), backgroundColor: AppColors.success),
      );
    }
    setState(() => _isSaving = false);
  }

  Future<void> _logout() async {
    await http.post(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.logout}'),
      headers: _headers,
    );
    widget.onLogout();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('โปรไฟล์'),
        actions: [
          if (!_isLoading && !_isEditing)
            IconButton(icon: const Icon(Icons.edit_outlined), onPressed: () => setState(() => _isEditing = true)),
          if (_isEditing)
            TextButton(
              onPressed: _isSaving ? null : _save,
              child: _isSaving ? const SizedBox(width: 16, height: 16,
                child: CircularProgressIndicator(strokeWidth: 2)) : const Text('บันทึก'),
            ),
        ],
      ),
      body: _isLoading
        ? const Center(child: CircularProgressIndicator())
        : SingleChildScrollView(
            child: Column(
              children: [
                _buildHeader(),
                const SizedBox(height: 16),
                _isEditing ? _buildEditForm() : _buildInfoSection(),
                const SizedBox(height: 24),
                _buildMenuSection(),
              ],
            ),
          ),
    );
  }

  Widget _buildHeader() {
    final name = '${_user?['first_name'] ?? ''} ${_user?['last_name'] ?? ''}'.trim();
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(vertical: 32),
      color: AppColors.surface,
      child: Column(
        children: [
          CircleAvatar(
            radius: 40,
            backgroundColor: AppColors.primaryLight,
            backgroundImage: _user?['profile_image'] != null
              ? NetworkImage(_user!['profile_image']) : null,
            child: _user?['profile_image'] == null
              ? Text(
                  name.isNotEmpty ? name[0].toUpperCase() : '?',
                  style: AppTextStyles.h1.copyWith(color: AppColors.primary),
                )
              : null,
          ),
          const SizedBox(height: 12),
          Text(name.isNotEmpty ? name : 'ผู้ใช้งาน', style: AppTextStyles.h3),
          Text(_user?['email'] ?? '', style: AppTextStyles.body2),
        ],
      ),
    );
  }

  Widget _buildInfoSection() {
    return Container(
      color: AppColors.surface,
      child: Column(
        children: [
          _infoTile('ชื่อ', _user?['first_name']),
          _infoTile('นามสกุล', _user?['last_name']),
          _infoTile('เบอร์โทร', _user?['phone'] ?? '-'),
          _infoTile('วันเกิด', _user?['date_of_birth'] ?? '-'),
          _infoTile('เพศ', _user?['sex'] == 'M' ? 'ชาย' : _user?['sex'] == 'F' ? 'หญิง' : '-'),
        ],
      ),
    );
  }

  Widget _infoTile(String label, String? value) => Padding(
    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
    child: Row(
      children: [
        Text(label, style: AppTextStyles.body2),
        const Spacer(),
        Text(value ?? '-', style: AppTextStyles.body1),
      ],
    ),
  );

  Widget _buildEditForm() {
    return Container(
      color: AppColors.surface,
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          TextField(controller: _firstNameCtrl,
            decoration: const InputDecoration(labelText: 'ชื่อ')),
          const SizedBox(height: 12),
          TextField(controller: _lastNameCtrl,
            decoration: const InputDecoration(labelText: 'นามสกุล')),
          const SizedBox(height: 12),
          TextField(controller: _phoneCtrl, keyboardType: TextInputType.phone,
            decoration: const InputDecoration(labelText: 'เบอร์โทร')),
        ],
      ),
    );
  }

  Widget _buildMenuSection() {
    return Container(
      color: AppColors.surface,
      child: Column(
        children: [
          _menuTile(Icons.history_outlined, 'ประวัติการประเมิน', () {}),
          _menuTile(Icons.bookmark_outline, 'รายการบันทึก', () {}),
          const Divider(height: 1),
          _menuTile(Icons.logout, 'ออกจากระบบ', () {
            showDialog(
              context: context,
              builder: (_) => AlertDialog(
                title: const Text('ออกจากระบบ'),
                content: const Text('ต้องการออกจากระบบใช่หรือไม่?'),
                actions: [
                  TextButton(onPressed: () => Navigator.pop(context), child: const Text('ยกเลิก')),
                  TextButton(
                    onPressed: () { Navigator.pop(context); _logout(); },
                    child: const Text('ออกจากระบบ', style: TextStyle(color: AppColors.error)),
                  ),
                ],
              ),
            );
          }, color: AppColors.error),
        ],
      ),
    );
  }

  Widget _menuTile(IconData icon, String label, VoidCallback onTap, {Color? color}) => ListTile(
    leading: Icon(icon, color: color ?? AppColors.textSecondary, size: 22),
    title: Text(label, style: AppTextStyles.body1.copyWith(color: color)),
    trailing: color == null ? const Icon(Icons.chevron_right, color: AppColors.textSecondary) : null,
    onTap: onTap,
  );
}
