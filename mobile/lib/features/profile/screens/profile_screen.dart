import 'dart:convert';

import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:http/http.dart' as http;

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../health/screens/bookmarks_screen.dart';
import '../../health/screens/health_dashboard_screen.dart';
import '../../health/screens/health_reminder_screen.dart';
import '../../health/screens/health_report_screen.dart';
import '../../notification/screens/notification_screen.dart';
import 'change_password_screen.dart';
import 'account_activity_screen.dart';
import 'edit_profile_screen.dart';
import 'feedback_screen.dart';
import 'privacy_center_screen.dart';
import 'session_management_screen.dart';
import '../../accessibility/screens/accessibility_screen.dart';

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

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (mounted) setState(() => _isLoading = true);
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.profile}'),
        headers: _headers,
      );
      if (response.statusCode == 200 && mounted) {
        setState(() => _user = jsonDecode(response.body)['data']);
      }
    } catch (_) {
      // Pull to refresh lets the user retry without interrupting the screen.
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _openEditProfile() async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => EditProfileScreen(token: widget.token, user: _user),
      ),
    );
    await _load();
  }

  void _confirmLogout() {
    showDialog<void>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text('ออกจากระบบ', style: AppTextStyles.h4),
        content: Text(
          'ต้องการออกจากระบบใช่หรือไม่?',
          style: AppTextStyles.body2.copyWith(color: AppColors.textSecondary),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: Text(
              'ยกเลิก',
              style: AppTextStyles.body2Bold.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(dialogContext);
              widget.onLogout();
            },
            child: Text(
              'ออกจากระบบ',
              style: AppTextStyles.body2Bold.copyWith(color: AppColors.danger),
            ),
          ),
        ],
      ),
    );
  }

  void _showInformation(String title, String message, IconData icon) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: AppColors.background,
      showDragHandle: true,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (sheetContext) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(24, 8, 24, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 52,
                height: 52,
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(icon, color: AppColors.primary, size: 26),
              ),
              const SizedBox(height: 16),
              Text(title, style: AppTextStyles.h4),
              const SizedBox(height: 8),
              Text(
                message,
                textAlign: TextAlign.center,
                style: AppTextStyles.body2.copyWith(
                  color: AppColors.textSecondary,
                  height: 1.55,
                ),
              ),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: () => Navigator.pop(sheetContext),
                  child: Text(
                    'ตกลง',
                    style: AppTextStyles.body1Bold.copyWith(
                      color: AppColors.white,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    final horizontalPadding = Responsive.horizontalPadding;
    final name = '${_user?['first_name'] ?? ''} ${_user?['last_name'] ?? ''}'
        .trim();
    final displayName = name.isEmpty ? 'ผู้ใช้งาน' : name;
    final email = (_user?['email'] as String?)?.trim();
    final imageUrl = (_user?['profile_image'] as String?)?.trim();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        automaticallyImplyLeading: false,
        centerTitle: true,
        backgroundColor: AppColors.white,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        title: Text('ข้อมูลส่วนตัว', style: AppTextStyles.h4),
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, thickness: 1, color: AppColors.border),
        ),
      ),
      body: SafeArea(
        top: false,
        bottom: false,
        child: _isLoading
            ? const AppLoadingView()
            : RefreshIndicator(
                color: AppColors.primary,
                onRefresh: _load,
                child: ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: EdgeInsets.fromLTRB(
                    horizontalPadding,
                    Responsive.dp(20),
                    horizontalPadding,
                    Responsive.dp(32),
                  ),
                  children: [
                    _ProfileHeader(
                      name: displayName,
                      email: email?.isNotEmpty == true
                          ? email!
                          : 'ดูและแก้ไขข้อมูลส่วนตัว',
                      imageUrl: imageUrl,
                      onTap: _openEditProfile,
                    ),
                    SizedBox(height: Responsive.dp(28)),
                    _Section(
                      title: 'การตั้งค่าบัญชี',
                      children: [
                        _MenuItem(
                          icon: Icons.person_outline_rounded,
                          title: 'ข้อมูลส่วนตัว',
                          subtitle: 'ชื่อ วันเกิด และเพศ',
                          onTap: _openEditProfile,
                        ),
                        _MenuItem(
                          icon: Icons.lock_outline_rounded,
                          title: 'เปลี่ยนรหัสผ่าน',
                          subtitle: 'ตั้งค่ารหัสผ่านสำหรับเข้าสู่ระบบ',
                          onTap: () => Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) =>
                                  ChangePasswordScreen(token: widget.token),
                            ),
                          ),
                        ),
                        _MenuItem(
                          icon: Icons.text_fields_rounded,
                          title: 'การแสดงผลและการเข้าถึง',
                          subtitle: 'ขนาดตัวอักษร Contrast และลดภาพเคลื่อนไหว',
                          onTap: () => Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => const AccessibilityScreen(),
                            ),
                          ),
                        ),
                        // _MenuItem(
                        //   icon: Icons.devices_rounded,
                        //   title: 'อุปกรณ์และการเข้าสู่ระบบ',
                        //   subtitle: 'ตรวจสอบและออกจากระบบอุปกรณ์',
                        //   onTap: () => Navigator.push(
                        //     context,
                        //     MaterialPageRoute(
                        //       builder: (_) => SessionManagementScreen(
                        //         token: widget.token,
                        //         onCurrentSessionRevoked: widget.onLogout,
                        //       ),
                        //     ),
                        //   ),
                        // ),
                        // _MenuItem(
                        //   icon: Icons.history_rounded,
                        //   title: 'ประวัติการใช้งานบัญชี',
                        //   subtitle:
                        //       'ตรวจสอบการเข้าสู่ระบบและกิจกรรมด้านความปลอดภัย',
                        //   onTap: () => Navigator.push(
                        //     context,
                        //     MaterialPageRoute(
                        //       builder: (_) =>
                        //           AccountActivityScreen(token: widget.token),
                        //     ),
                        //   ),
                        // ),
                        _MenuItem(
                          icon: Icons.notifications_none_rounded,
                          title: 'การแจ้งเตือน',
                          subtitle: 'ติดตามข่าวสารและการแจ้งเตือนสุขภาพ',
                          onTap: () => Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) =>
                                  NotificationScreen(token: widget.token),
                            ),
                          ),
                        ),
                        _MenuItem(
                          icon: Icons.alarm_rounded,
                          title: 'ตั้งค่าการแจ้งเตือน',
                          subtitle: 'เปิด–ปิด เลือกเวลาและวันที่แจ้งเตือน',
                          isLast: true,
                          onTap: () => Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) =>
                                  HealthReminderScreen(token: widget.token),
                            ),
                          ),
                        ),
                        // _MenuItem(
                        //   icon: Icons.privacy_tip_outlined,
                        //   title: 'ความเป็นส่วนตัว',
                        //   subtitle: 'ดาวน์โหลดข้อมูลหรือลบบัญชี',
                        //   isLast: true,
                        //   onTap: () => Navigator.push(
                        //     context,
                        //     MaterialPageRoute(
                        //       builder: (_) => PrivacyCenterScreen(
                        //         token: widget.token,
                        //         onAccountDeleted: widget.onLogout,
                        //       ),
                        //     ),
                        //   ),
                        // ),
                      ],
                    ),
                    SizedBox(height: Responsive.dp(28)),
                    _Section(
                      title: 'สุขภาพของฉัน',
                      children: [
                        _MenuItem(
                          icon: Icons.insights_rounded,
                          title: 'แนวโน้มสุขภาพ',
                          subtitle: 'ดูกราฟสุขภาพย้อนหลัง 7, 30 หรือ 90 วัน',
                          onTap: () => Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => const HealthDashboardScreen(),
                            ),
                          ),
                        ),
                        _MenuItem(
                          icon: Icons.picture_as_pdf_outlined,
                          title: 'รายงานประวัติสุขภาพ',
                          subtitle: 'เลือกช่วงเวลา ดาวน์โหลด และแชร์ PDF',
                          onTap: () => Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) =>
                                  HealthReportScreen(token: widget.token),
                            ),
                          ),
                        ),
                        _MenuItem(
                          icon: Icons.bookmark_border_rounded,
                          title: 'รายการโปรด',
                          subtitle: 'บทความและข้อมูลสุขภาพที่บันทึกไว้',
                          isLast: true,
                          onTap: () => Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => const BookmarksScreen(),
                            ),
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: Responsive.dp(28)),
                    _Section(
                      title: 'เกี่ยวกับแอป',
                      children: [
                        _MenuItem(
                          icon: Icons.feedback_outlined,
                          title: 'ความคิดเห็นและรายงานข้อมูลผิด',
                          subtitle: 'ส่งข้อเสนอแนะและติดตามสถานะรายงาน',
                          onTap: () => Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) =>
                                  FeedbackScreen(token: widget.token),
                            ),
                          ),
                        ),
                        _MenuItem(
                          icon: Icons.lightbulb_outline_rounded,
                          title: 'คำแนะนำการใช้งาน',
                          subtitle: 'วิธีใช้งานและข้อควรทราบ',
                          onTap: () => _showInformation(
                            'คำแนะนำการใช้งาน',
                            'ผลการประเมินเป็นคำแนะนำเบื้องต้น ไม่ใช่การวินิจฉัย หากมีอาการรุนแรงหรือไม่แน่ใจควรพบแพทย์',
                            Icons.lightbulb_outline_rounded,
                          ),
                        ),
                        _MenuItem(
                          icon: Icons.info_outline_rounded,
                          title: 'เครดิต',
                          subtitle: 'แหล่งข้อมูลและผู้จัดทำ',
                          isLast: true,
                          onTap: () => _showInformation(
                            'เครดิต',
                            'เนื้อหาอ้างอิงจาก ตำราการตรวจรักษาโรคทั่วไป \nของ นายแพทย์สุรเกียรติ อาชานานุภาพ\nพัฒนาเพื่อช่วยประเมินอาการเบื้องต้น',
                            Icons.info_outline_rounded,
                            // Icons.favorite_outline_rounded,
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: Responsive.dp(20)),
                    InkWell(
                      onTap: _confirmLogout,
                      borderRadius: BorderRadius.circular(16),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 4,
                          vertical: 14,
                        ),
                        child: Row(
                          children: [
                            const Icon(
                              Icons.logout_rounded,
                              color: AppColors.danger,
                            ),
                            const SizedBox(width: 14),
                            Text(
                              'ออกจากระบบ',
                              style: AppTextStyles.body1Bold.copyWith(
                                color: AppColors.danger,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
      ),
    );
  }
}

class _ProfileHeader extends StatelessWidget {
  final String name;
  final String email;
  final String? imageUrl;
  final VoidCallback onTap;

  const _ProfileHeader({
    required this.name,
    required this.email,
    required this.imageUrl,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final hasImage = imageUrl != null && imageUrl!.isNotEmpty;
    final initial = name.isNotEmpty ? name[0].toUpperCase() : '?';

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.primaryLight.withValues(alpha: 0.62),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Row(
          children: [
            CircleAvatar(
              radius: 34,
              backgroundColor: AppColors.white,
              backgroundImage: hasImage ? NetworkImage(imageUrl!) : null,
              child: hasImage
                  ? null
                  : Text(
                      initial,
                      style: AppTextStyles.h3.copyWith(
                        color: AppColors.primary,
                      ),
                    ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: AppTextStyles.body1Bold,
                  ),
                  const SizedBox(height: 3),
                  Text(
                    email,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: AppTextStyles.body3.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            const Icon(Icons.chevron_right_rounded, color: AppColors.primary),
          ],
        ),
      ),
    );
  }
}

class _Section extends StatelessWidget {
  final String title;
  final List<Widget> children;
  final int? maxItems;

  const _Section({required this.title, required this.children, this.maxItems});

  @override
  Widget build(BuildContext context) {
    final firstIcon = children.isNotEmpty && children.first is _MenuItem
        ? (children.first as _MenuItem).icon
        : null;
    final displayTitle = switch (firstIcon) {
      Icons.person_outline_rounded => 'การตั้งค่าบัญชี',
      Icons.lightbulb_outline_rounded => 'เกี่ยวกับแอป',
      _ => title,
    };
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(displayTitle, style: AppTextStyles.h4),
        const SizedBox(height: 8),
        ...children.take(maxItems ?? children.length),
      ],
    );
  }
}

class _MenuItem extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
  final bool isLast;

  const _MenuItem({
    required this.icon,
    required this.title,
    required this.onTap,
    required this.subtitle,
    this.isLast = false,
  });

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    final (displayTitle, displaySubtitle) = switch (icon) {
      Icons.person_outline_rounded => (
        'แก้ไขข้อมูลส่วนตัว',
        'ชื่อ วันเกิด และเพศ',
      ),
      Icons.lock_outline_rounded => (
        'เปลี่ยนรหัสผ่าน',
        'ตั้งค่ารหัสผ่านสำหรับเข้าสู่ระบบ',
      ),
      Icons.notifications_none_rounded => (
        'การแจ้งเตือน',
        'ติดตามข่าวสารและการแจ้งเตือนสุขภาพ',
      ),
      Icons.lightbulb_outline_rounded => (
        'คำแนะนำการใช้งาน',
        'วิธีใช้งานและข้อควรทราบ',
      ),
      Icons.info_outline_rounded => ('เครดิต', 'แหล่งข้อมูลและผู้จัดทำ'),
      _ => (title, subtitle),
    };
    return Column(
      children: [
        InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: EdgeInsets.symmetric(
              horizontal: Responsive.dp(16),
              vertical: Responsive.dp(14),
            ),
            child: Row(
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: AppColors.primaryLight,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(icon, color: AppColors.primary, size: 22),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(displayTitle, style: AppTextStyles.body1Bold),
                      const SizedBox(height: 2),
                      Text(
                        displaySubtitle,
                        style: AppTextStyles.body2.copyWith(
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
                const Icon(
                  Icons.arrow_forward_ios,
                  size: 14,
                  color: AppColors.textSecondary,
                ),
              ],
            ),
          ),
        ),
        if (!isLast)
          const Divider(
            height: 1,
            thickness: 1,
            indent: 0,
            endIndent: 0,
            color: AppColors.border,
          ),
      ],
    );
  }
}
