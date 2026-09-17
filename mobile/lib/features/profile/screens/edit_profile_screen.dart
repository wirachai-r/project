import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:checkup/data/services/central_http_client.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/buddhist_calendar_delegate.dart';
import '../../../core/utils/media_url.dart';
import '../../../core/utils/responsive.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_layout.dart';

class EditProfileScreen extends StatefulWidget {
  final String token;
  final Map<String, dynamic>? user;

  const EditProfileScreen({super.key, required this.token, this.user});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final _firstNameCtrl = TextEditingController();
  final _lastNameCtrl = TextEditingController();
  final _picker = ImagePicker();

  DateTime? _dateOfBirth;
  String? _sex;
  XFile? _selectedImage;
  Uint8List? _selectedImageBytes;
  bool _removeImage = false;
  bool _isSaving = false;

  String? get _currentImageUrl {
    return resolveMediaUrl(widget.user?['profile_image']);
  }

  String? get _systemImagePath {
    final value = widget.user?['system_profile_image']?.toString().trim();
    return value == null || value.isEmpty ? null : value;
  }

  bool get _usesGoogleAvatar {
    final googleId = widget.user?['google_id']?.toString().trim();
    return _selectedImage == null &&
        !_removeImage &&
        _systemImagePath == null &&
        _currentImageUrl != null &&
        googleId != null &&
        googleId.isNotEmpty;
  }

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  @override
  void initState() {
    super.initState();
    _firstNameCtrl.text = widget.user?['first_name']?.toString() ?? '';
    _lastNameCtrl.text = widget.user?['last_name']?.toString() ?? '';
    _dateOfBirth = DateTime.tryParse(
      widget.user?['date_of_birth']?.toString() ?? '',
    );
    final sex = widget.user?['sex']?.toString();
    _sex = sex == 'M' || sex == 'F' ? sex : null;
  }

  @override
  void dispose() {
    _firstNameCtrl.dispose();
    _lastNameCtrl.dispose();
    super.dispose();
  }

  void _showMessage(String message, {bool error = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: error ? AppColors.danger : AppColors.success,
      ),
    );
  }

  Future<void> _pickImage(ImageSource source) async {
    Navigator.pop(context);
    try {
      final image = await _picker.pickImage(
        source: source,
        imageQuality: 85,
        maxWidth: 1200,
      );
      if (image != null && mounted) {
        final bytes = await image.readAsBytes();
        if (!mounted) return;
        setState(() {
          _selectedImage = image;
          _selectedImageBytes = bytes;
          _removeImage = false;
        });
      }
    } catch (_) {
      _showMessage(
        'ไม่สามารถเลือกรูปภาพได้ กรุณาตรวจสอบสิทธิ์การเข้าถึง',
        error: true,
      );
    }
  }

  void _removeSelectedImage() {
    Navigator.pop(context);
    setState(() {
      _selectedImage = null;
      _selectedImageBytes = null;
      _removeImage = true;
    });
  }

  void _showImageOptions() {
    showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      backgroundColor: Theme.of(context).colorScheme.surface,
      builder: (_) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                leading: const Icon(Icons.photo_library_outlined),
                title: const Text('เลือกรูปจากคลังภาพ'),
                onTap: () => _pickImage(ImageSource.gallery),
              ),
              ListTile(
                leading: const Icon(Icons.camera_alt_outlined),
                title: const Text('ถ่ายรูปใหม่'),
                onTap: () => _pickImage(ImageSource.camera),
              ),
              if (_selectedImage != null ||
                  (!_removeImage && _systemImagePath != null))
                ListTile(
                  leading: const Icon(
                    Icons.delete_outline,
                    color: AppColors.danger,
                  ),
                  title: const Text(
                    'ลบรูปโปรไฟล์',
                    style: TextStyle(color: AppColors.danger),
                  ),
                  onTap: _removeSelectedImage,
                ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _selectDate() async {
    final now = DateTime.now();
    final selected = await showDatePicker(
      context: context,
      calendarDelegate: const BuddhistCalendarDelegate(),
      initialDate: _dateOfBirth ?? DateTime(now.year - 20),
      firstDate: DateTime(1900),
      lastDate: now,
    );
    if (selected != null && mounted) setState(() => _dateOfBirth = selected);
  }

  Future<Map<String, String>> _uploadImage(XFile image) async {
    final request =
        http.MultipartRequest(
            'POST',
            Uri.parse('${ApiConstants.baseUrl}/uploads/image'),
          )
          ..headers.addAll(_headers)
          ..fields['folder'] = 'profiles'
          ..files.add(
            http.MultipartFile.fromBytes(
              'image',
              await image.readAsBytes(),
              filename: image.name,
            ),
          );
    final response = await http.Response.fromStream(await http.send(request));
    if (response.statusCode != 201) throw Exception('upload failed');
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    return {'url': data['url'].toString(), 'path': data['path'].toString()};
  }

  Future<void> _deleteImage(String? path) async {
    if (path == null || path.isEmpty) return;
    await http.delete(
      Uri.parse('${ApiConstants.baseUrl}/uploads/image'),
      headers: {..._headers, 'Content-Type': 'application/json'},
      body: jsonEncode({'path': path}),
    );
  }

  String _errorMessage(http.Response response) {
    try {
      final data = jsonDecode(response.body) as Map<String, dynamic>;
      final errors = data['errors'];
      if (errors is Map && errors.values.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) return first.first.toString();
      }
      return data['message']?.toString() ?? 'บันทึกข้อมูลไม่สำเร็จ';
    } catch (_) {
      return 'บันทึกข้อมูลไม่สำเร็จ';
    }
  }

  Future<void> _save() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() => _isSaving = true);
    String? newImagePath;
    try {
      String? profileImage = _removeImage ? null : _systemImagePath;
      if (_selectedImage != null) {
        final uploaded = await _uploadImage(_selectedImage!);
        profileImage = uploaded['path'];
        newImagePath = uploaded['path'];
      }

      final response = await http.put(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.profile}'),
        headers: {..._headers, 'Content-Type': 'application/json'},
        body: jsonEncode({
          'first_name': _firstNameCtrl.text.trim(),
          'last_name': _lastNameCtrl.text.trim(),
          'date_of_birth': _dateOfBirth == null
              ? null
              : DateFormat('yyyy-MM-dd').format(_dateOfBirth!),
          'sex': _sex,
          'profile_image': profileImage,
        }),
      );

      if (response.statusCode != 200) {
        await _deleteImage(newImagePath);
        _showMessage(_errorMessage(response), error: true);
        return;
      }

      if ((_removeImage || _selectedImage != null) &&
          _systemImagePath != null) {
        await _deleteImage(_systemImagePath);
      }
      if (!mounted) return;
      _showMessage('บันทึกข้อมูลสำเร็จ');
      Navigator.pop(context, true);
    } catch (_) {
      if (newImagePath != null) await _deleteImage(newImagePath);
      _showMessage('ไม่สามารถเชื่อมต่อหรือบันทึกข้อมูลได้', error: true);
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    final name = '${_firstNameCtrl.text} ${_lastNameCtrl.text}'.trim();
    final initial = name.isEmpty ? '?' : name[0].toUpperCase();

    ImageProvider? imageProvider;
    if (_selectedImageBytes != null) {
      imageProvider = MemoryImage(_selectedImageBytes!);
    } else if (!_removeImage && _currentImageUrl != null) {
      imageProvider = NetworkImage(_currentImageUrl!);
    }
    var imageHelperText = 'แตะเพื่อเปลี่ยนรูป';
    if (_usesGoogleAvatar) {
      imageHelperText = 'รูปโปรไฟล์จาก Google • แตะเพื่อเปลี่ยนรูป';
    } else if (_selectedImage != null ||
        (!_removeImage && _systemImagePath != null)) {
      imageHelperText = 'แตะเพื่อเปลี่ยนหรือลบรูป';
    }

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        surfaceTintColor: Colors.transparent,
        leading: BackButton(color: Theme.of(context).colorScheme.onSurface),
        title: Text('แก้ไขข้อมูลส่วนตัว', style: AppTextStyles.h4),
        centerTitle: true,
        bottom: PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(
            height: 1,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: AppContentWidth(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: EdgeInsets.fromLTRB(
              Responsive.horizontalPadding,
              Responsive.dp(24),
              Responsive.horizontalPadding,
              Responsive.dp(32),
            ),
            children: [
              Center(
                child: InkWell(
                  onTap: _isSaving ? null : _showImageOptions,
                  borderRadius: BorderRadius.circular(60),
                  child: Stack(
                    children: [
                      CircleAvatar(
                        radius: 52,
                        backgroundColor: AppColors.primaryLight,
                        foregroundImage: imageProvider,
                        onForegroundImageError: imageProvider != null
                            ? (_, _) {}
                            : null,
                        child: Text(
                          initial,
                          style: AppTextStyles.h2.copyWith(
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                      Positioned(
                        right: 0,
                        bottom: 0,
                        child: Container(
                          width: 34,
                          height: 34,
                          decoration: BoxDecoration(
                            color: AppColors.primary,
                            shape: BoxShape.circle,
                            border: Border.all(
                              color: AppColors.white,
                              width: 2,
                            ),
                          ),
                          child: const Icon(
                            Icons.camera_alt_outlined,
                            size: 18,
                            color: AppColors.white,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                imageHelperText,
                textAlign: TextAlign.center,
                style: AppTextStyles.body2.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
              SizedBox(height: Responsive.dp(28)),
              _label('ชื่อ-นามสกุล'),
              const SizedBox(height: 8),
              LayoutBuilder(
                builder: (context, constraints) {
                  final firstName = _field(
                    _firstNameCtrl,
                    'ชื่อ',
                    requiredField: true,
                  );
                  final lastName = _field(
                    _lastNameCtrl,
                    'นามสกุล',
                    requiredField: true,
                  );
                  if (constraints.maxWidth < 360) {
                    return Column(
                      children: [
                        firstName,
                        const SizedBox(height: 10),
                        lastName,
                      ],
                    );
                  }
                  return Row(
                    children: [
                      Expanded(child: firstName),
                      const SizedBox(width: 10),
                      Expanded(child: lastName),
                    ],
                  );
                },
              ),
              const SizedBox(height: 20),
              _label('วันเกิด'),
              const SizedBox(height: 8),
              _selectionTile(
                text: _dateOfBirth == null
                    ? 'เลือกวันเกิด'
                    : formatThaiDate(_dateOfBirth!),
                icon: Icons.calendar_today_outlined,
                onTap: _selectDate,
              ),
              const SizedBox(height: 20),
              _label('เพศ'),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                initialValue: _sex,
                decoration: _inputDecoration('เลือกเพศ'),
                items: const [
                  DropdownMenuItem(value: 'M', child: Text('ชาย')),
                  DropdownMenuItem(value: 'F', child: Text('หญิง')),
                ],
                onChanged: _isSaving
                    ? null
                    : (value) => setState(() => _sex = value),
              ),
              SizedBox(height: Responsive.dp(36)),
              AppButton(
                label: 'บันทึกข้อมูล',
                loading: _isSaving,
                onTap: _isSaving ? null : _save,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _label(String value) => Text(value, style: AppTextStyles.body1Bold);

  InputDecoration _inputDecoration(String hint) => InputDecoration(
    hintText: hint,
    hintStyle: AppTextStyles.body1.copyWith(
      color: Theme.of(context).colorScheme.onSurfaceVariant,
    ),
    filled: true,
    fillColor: Theme.of(context).colorScheme.surface,
    contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
    border: OutlineInputBorder(
      borderRadius: BorderRadius.circular(16),
      borderSide: BorderSide(
        color: Theme.of(context).colorScheme.outlineVariant,
      ),
    ),
    enabledBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(16),
      borderSide: BorderSide(
        color: Theme.of(context).colorScheme.outlineVariant,
      ),
    ),
    focusedBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(16),
      borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
    ),
  );

  Widget _field(
    TextEditingController controller,
    String hint, {
    TextInputType? inputType,
    bool requiredField = false,
  }) => TextFormField(
    controller: controller,
    keyboardType: inputType,
    decoration: _inputDecoration(hint),
    validator: (value) {
      final text = value?.trim() ?? '';
      if (requiredField && text.isEmpty) return 'กรุณากรอกข้อมูล';
      return null;
    },
  );

  Widget _selectionTile({
    required String text,
    required IconData icon,
    required VoidCallback onTap,
  }) => InkWell(
    onTap: _isSaving ? null : onTap,
    borderRadius: BorderRadius.circular(16),
    child: InputDecorator(
      decoration: _inputDecoration(''),
      child: Row(
        children: [
          Expanded(child: Text(text, style: AppTextStyles.body1)),
          Icon(
            icon,
            size: 20,
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ],
      ),
    ),
  );
}
