import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_logo.dart';
import '../../../shared/widgets/app_button.dart';
import '../../auth/providers/auth_provider.dart';
import '../../assessment/screens/symptom_select_screen.dart';
import '../../article/screens/article_list_screen.dart';
import '../../history/screens/history_list_screen.dart';
import '../../profile/screens/profile_screen.dart';
import '../../notification/screens/notification_screen.dart';
import '../../disease/screens/disease_list_screen.dart';
import '../../first_aid/screens/first_aid_list_screen.dart';
import '../../facility/screens/facility_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _tab = 0;

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final token = auth.token ?? '';

    return ResponsiveBuilder(
      builder: (context) => Scaffold(
        backgroundColor: AppColors.surface,
        body: switch (_tab) {
          0 => const _HomeTab(),
          1 => const ArticleListScreen(),
          2 => HistoryListScreen(token: token),
          3 => ProfileScreen(token: token, onLogout: () => auth.logout()),
          _ => const _HomeTab(),
        },
        bottomNavigationBar: BottomNavigationBar(
          currentIndex: _tab,
          onTap: (i) => setState(() => _tab = i),
          items: const [
            BottomNavigationBarItem(
              icon: Icon(Icons.home_outlined),
              activeIcon: Icon(Icons.home),
              label: 'หน้าแรก',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.article_outlined),
              activeIcon: Icon(Icons.article),
              label: 'บทความ',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.history_outlined),
              activeIcon: Icon(Icons.history),
              label: 'ประวัติ',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.person_outline),
              activeIcon: Icon(Icons.person),
              label: 'ข้อมูลส่วนตัว',
            ),
          ],
        ),
      ),
    );
  }
}

class _HomeTab extends StatelessWidget {
  const _HomeTab();

  @override
  Widget build(BuildContext context) {
    final hp = Responsive.horizontalPadding;
    final token = context.read<AuthProvider>().token ?? '';

    return SafeArea(
      child: SingleChildScrollView(
        padding: EdgeInsets.symmetric(horizontal: hp),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(height: Responsive.dp(16)),

            Row(
              children: [
                const AppLogoSmall(size: 38),
                const SizedBox(width: 10),
                Text(
                  'Checkup',
                  style: AppTextStyles.h4.copyWith(color: AppColors.primary),
                ),
                const Spacer(),
                IconButton(
                  icon: const Icon(Icons.notifications_outlined),
                  color: AppColors.textPrimary,
                  onPressed: () => Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => NotificationScreen(token: token),
                    ),
                  ),
                ),
              ],
            ),
            SizedBox(height: Responsive.dp(24)),

            Text('วันนี้คุณรู้สึกอย่างไร?', style: AppTextStyles.h3),
            SizedBox(height: Responsive.dp(4)),
            Text(
              'แจ้งอาการของคุณให้เราทราบเพื่อรับคำแนะนำเบื้องต้น',
              style: AppTextStyles.body3.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
            SizedBox(height: Responsive.dp(20)),

            Container(
              width: double.infinity,
              padding: EdgeInsets.all(Responsive.dp(20)),
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(20),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: AppColors.white,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(
                          Icons.search,
                          color: AppColors.primary,
                          size: 24,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          'เริ่มประเมินอาการ',
                          style: AppTextStyles.body1Bold,
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: Responsive.dp(8)),
                  Text(
                    'แจ้งอาการของคุณให้เราทราบเพื่อรับคำแนะนำเบื้องต้น',
                    style: AppTextStyles.body3.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                  SizedBox(height: Responsive.dp(16)),
                  AppButton(
                    label: 'เริ่มการประเมิน',
                    onTap: () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => const SymptomSelectScreen(),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            SizedBox(height: Responsive.dp(12)),

            GestureDetector(
              onTap: () => launchUrl(Uri.parse('tel:1669')),
              child: Container(
                width: double.infinity,
                padding: EdgeInsets.symmetric(
                  horizontal: Responsive.dp(20),
                  vertical: Responsive.dp(16),
                ),
                decoration: BoxDecoration(
                  color: AppColors.danger,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: AppColors.white.withValues(alpha: 0.2),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.phone,
                        color: AppColors.white,
                        size: 22,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'กรณีฉุกเฉิน โทร 1669',
                            style: AppTextStyles.body1Bold.copyWith(
                              color: AppColors.white,
                            ),
                          ),
                          Text(
                            'บริการการแพทย์ฉุกเฉิน 24 ชั่วโมง',
                            style: AppTextStyles.body3.copyWith(
                              color: AppColors.white.withValues(alpha: 0.8),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const Icon(
                      Icons.arrow_forward_ios,
                      color: AppColors.white,
                      size: 16,
                    ),
                  ],
                ),
              ),
            ),
            SizedBox(height: Responsive.dp(24)),

            Text('เมนูแนะนำ', style: AppTextStyles.h4),
            SizedBox(height: Responsive.dp(12)),

            _MenuItem(
              icon: Icons.article_outlined,
              title: 'ความรู้สุขภาพ',
              subtitle: 'บทความน่าสนใจ',
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const ArticleListScreen()),
              ),
            ),
            _MenuItem(
              icon: Icons.medical_services_outlined,
              title: 'การปฐมพยาบาล',
              subtitle: 'คู่มือเบื้องต้น',
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const FirstAidListScreen()),
              ),
            ),
            _MenuItem(
              icon: Icons.health_and_safety_outlined,
              title: 'ข้อมูลโรค',
              subtitle: 'รายละเอียดเกี่ยวกับโรค',
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const DiseaseListScreen()),
              ),
            ),
            _MenuItem(
              icon: Icons.map_outlined,
              title: 'ค้นหาสถานบริการใกล้คุณ',
              subtitle: 'ค้นหาร้านขายยา คลินิก หรือสถานพยาบาล',
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const FacilityScreen()),
              ),
            ),
            SizedBox(height: Responsive.dp(24)),
          ],
        ),
      ),
    );
  }
}

class _MenuItem extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  const _MenuItem({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: EdgeInsets.only(bottom: Responsive.dp(12)),
        padding: EdgeInsets.symmetric(
          horizontal: Responsive.dp(16),
          vertical: Responsive.dp(14),
        ),
        decoration: BoxDecoration(
          color: AppColors.white,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: const BoxDecoration(
                color: AppColors.primaryLight,
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: AppColors.primary, size: 22),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: AppTextStyles.body2Bold),
                  Text(
                    subtitle,
                    style: AppTextStyles.body3.copyWith(
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
    );
  }
}
