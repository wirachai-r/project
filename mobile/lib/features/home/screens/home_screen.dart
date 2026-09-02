import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:mobile/data/services/central_http_client.dart' as http;
import 'package:provider/provider.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../data/repositories/personal_health_repository.dart';
import '../../../shared/widgets/app_logo.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../shared/widgets/login_bottom_sheet.dart';
import '../../article/screens/article_list_screen.dart';
import '../../article/screens/article_detail_screen.dart';
import '../../article/providers/article_provider.dart';
import '../../assessment/screens/body_area_group_screen.dart';
import '../../assessment/providers/assessment_provider.dart';
import '../../auth/providers/auth_provider.dart';
import '../../auth/screens/login_screen.dart';
import '../../disease/screens/disease_list_screen.dart';
import '../../facility/screens/facility_screen.dart';
import '../../first_aid/screens/first_aid_list_screen.dart';
import '../../health/screens/daily_health_record_screen.dart';
import '../../health/screens/health_dashboard_screen.dart';
import '../../health/screens/follow_up_screen.dart';
import '../../history/screens/history_list_screen.dart';
import '../../history/providers/history_provider.dart';
import '../../history/providers/history_detail_provider.dart';
import '../../notification/screens/notification_screen.dart';
import '../../profile/screens/profile_screen.dart';
import '../../search/screens/unified_search_screen.dart';
import 'emergency_contacts_screen.dart';

class HomeScreen extends StatefulWidget {
  static const int homeTab = 0;
  static const int articlesTab = 1;
  static const int healthTab = 2;
  static const int historyTab = 3;
  static const int profileTab = 4;

  final int initialTab;

  const HomeScreen({super.key, this.initialTab = homeTab});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late int _tab;

  @override
  void initState() {
    super.initState();
    _tab = widget.initialTab >= 0 && widget.initialTab <= 4
        ? widget.initialTab
        : HomeScreen.homeTab;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      prefetchFacilities();
      final articles = context.read<ArticleProvider>();
      if (articles.articles.isEmpty &&
          !articles.isLoading &&
          articles.error == null) {
        articles.loadArticles(refresh: true);
      }
    });
  }

  void _onTabTap(int index) {
    final auth = context.read<AuthProvider>();
    final isLoggedIn = auth.token?.isNotEmpty ?? false;

    if (!isLoggedIn && index >= 2) {
      LoginBottomSheet.show(context);
      return;
    }
    setState(() => _tab = index);
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final token = auth.token ?? '';
    final isLoggedIn = token.isNotEmpty;

    if (!isLoggedIn && _tab >= 2) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) setState(() => _tab = 0);
      });
    }

    return Scaffold(
      backgroundColor: AppColors.background,
      body: switch (_tab) {
        0 => _HomeTab(onNavigateToTab: _onTabTap),
        1 => const ArticleListScreen(),
        2 =>
          isLoggedIn
              ? const DailyHealthRecordScreen()
              : _HomeTab(onNavigateToTab: _onTabTap),
        3 =>
          isLoggedIn
              ? HistoryListScreen()
              : _HomeTab(onNavigateToTab: _onTabTap),
        4 =>
          isLoggedIn
              ? ProfileScreen(
                  token: token,
                  onLogout: () async {
                    await context.read<AuthProvider>().logout();
                    if (!context.mounted) return;
                    context.read<AssessmentProvider>().reset();
                    context.read<HistoryProvider>().reset();
                    context.read<HistoryDetailProvider>().reset();
                    setState(() => _tab = 0);
                    showAppSuccess(context, 'ออกจากระบบสำเร็จ');
                  },
                )
              : _HomeTab(onNavigateToTab: _onTabTap),
        _ => _HomeTab(onNavigateToTab: _onTabTap),
      },
      bottomNavigationBar: DecoratedBox(
        decoration: BoxDecoration(
          color: AppColors.surfaceElevated,
          border: Border(top: BorderSide(color: AppColors.border)),
          boxShadow: [
            BoxShadow(
              color: AppColors.textPrimary.withValues(alpha: 0.08),
              blurRadius: 20,
              offset: const Offset(0, -3),
            ),
          ],
        ),
        child: NavigationBar(
          selectedIndex: _tab,
          onDestinationSelected: _onTabTap,
          destinations: const [
            NavigationDestination(
              icon: Icon(Icons.home_outlined),
              selectedIcon: Icon(Icons.home_rounded),
              label: 'หน้าแรก',
            ),
            NavigationDestination(
              icon: Icon(Icons.article_outlined),
              selectedIcon: Icon(Icons.article_rounded),
              label: 'บทความ',
            ),
            NavigationDestination(
              icon: Icon(Icons.favorite_border_rounded),
              selectedIcon: Icon(Icons.favorite_rounded),
              label: 'สุขภาพ',
            ),
            NavigationDestination(
              icon: Icon(Icons.history_outlined),
              selectedIcon: Icon(Icons.history_rounded),
              label: 'ประวัติ',
            ),
            NavigationDestination(
              icon: Icon(Icons.person_outline),
              selectedIcon: Icon(Icons.person_rounded),
              label: 'โปรไฟล์',
            ),
          ],
        ),
      ),
    );
  }
}

class _HomeTab extends StatelessWidget {
  final void Function(int) onNavigateToTab;

  const _HomeTab({required this.onNavigateToTab});

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    final auth = context.watch<AuthProvider>();
    final token = auth.token ?? '';
    final isLoggedIn = token.isNotEmpty;
    final greeting = auth.user == null
        ? 'ดูแลสุขภาพได้ง่ายขึ้น'
        : 'สวัสดี ${auth.user!.firstName}';

    return SafeArea(
      child: ResponsiveContent(
        child: SingleChildScrollView(
          padding: EdgeInsets.fromLTRB(
            Responsive.horizontalPadding,
            12,
            Responsive.horizontalPadding,
            28,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _Header(isLoggedIn: isLoggedIn, token: token),
              SizedBox(height: Responsive.dp(24)),
              Text(greeting, style: AppTextStyles.h3),
              const SizedBox(height: 6),
              Text(
                'วันนี้คุณรู้สึกอย่างไร?',
                style: AppTextStyles.body1.copyWith(
                  color: AppColors.textSecondary,
                ),
              ),
              SizedBox(height: Responsive.dp(14)),
              Semantics(
                button: true,
                label: 'ค้นหาโรค บทความ และปฐมพยาบาล',
                child: SearchBar(
                  hintText: 'ค้นหาโรค บทความ และปฐมพยาบาล',
                  leading: const Icon(Icons.search_rounded),
                  onTap: () => Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => const UnifiedSearchScreen(),
                    ),
                  ),
                ),
              ),
              SizedBox(height: Responsive.dp(18)),
              _AssessmentCard(
                isLoggedIn: isLoggedIn,
                onTap: () {
                  if (!isLoggedIn) {
                    LoginBottomSheet.show(context);
                    return;
                  }
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => const BodyAreaGroupScreen(),
                    ),
                  );
                },
              ),
              if (isLoggedIn) ...[
                SizedBox(height: Responsive.dp(14)),
                const _HomeHealthInsight(),
              ],
              SizedBox(height: Responsive.dp(14)),
              _EmergencyCard(
                onTap: () => Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const EmergencyContactsScreen(),
                  ),
                ),
              ),
              SizedBox(height: Responsive.dp(24)),
              Text('บริการสุขภาพ', style: AppTextStyles.h4),
              const SizedBox(height: 12),
              LayoutBuilder(
                builder: (context, constraints) {
                  final textScale =
                      MediaQuery.textScalerOf(context).scale(14) / 14;
                  final useSingleColumn =
                      constraints.maxWidth < 350 || textScale > 1.25;
                  final itemWidth = useSingleColumn
                      ? constraints.maxWidth
                      : (constraints.maxWidth - 12) / 2;
                  return Wrap(
                    spacing: 12,
                    runSpacing: 12,
                    children: [
                      _QuickMenu(
                        width: itemWidth,
                        icon: Icons.article_outlined,
                        title: 'ความรู้สุขภาพ',
                        subtitle: 'บทความน่าอ่าน',
                        onTap: () => onNavigateToTab(1),
                      ),
                      _QuickMenu(
                        width: itemWidth,
                        icon: Icons.health_and_safety_outlined,
                        title: 'ข้อมูลโรค',
                        subtitle: 'ค้นหาและเรียนรู้',
                        onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const DiseaseListScreen(),
                          ),
                        ),
                      ),
                      _QuickMenu(
                        width: itemWidth,
                        icon: Icons.medical_services_outlined,
                        title: 'การปฐมพยาบาล',
                        subtitle: 'คู่มือเบื้องต้น',
                        onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const FirstAidListScreen(),
                          ),
                        ),
                      ),
                      _QuickMenu(
                        width: itemWidth,
                        icon: Icons.location_on_outlined,
                        title: 'สถานบริการใกล้คุณ',
                        subtitle: 'ร้านขายยาและคลินิก',
                        onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const FacilityScreen(),
                          ),
                        ),
                      ),
                      _QuickMenu(
                        width: itemWidth,
                        icon: Icons.favorite_border_rounded,
                        title: 'บันทึกสุขภาพ',
                        subtitle: 'บันทึกข้อมูลสุขภาพประจำวัน',
                        onTap: () => onNavigateToTab(2),
                      ),
                      _QuickMenu(
                        width: itemWidth,
                        icon: Icons.insights_rounded,
                        title: 'แนวโน้มสุขภาพ',
                        subtitle: 'ดูกราฟสุขภาพย้อนหลัง',
                        onTap: () {
                          if (!isLoggedIn) {
                            LoginBottomSheet.show(context);
                            return;
                          }
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => const HealthDashboardScreen(),
                            ),
                          );
                        },
                      ),
                    ],
                  );
                },
              ),
              const SizedBox(height: 28),
              _RecommendedArticles(onSeeAll: () => onNavigateToTab(1)),
            ],
          ),
        ),
      ),
    );
  }
}

class _HomeHealthInsight extends StatefulWidget {
  const _HomeHealthInsight();

  @override
  State<_HomeHealthInsight> createState() => _HomeHealthInsightState();
}

class _HomeHealthInsightState extends State<_HomeHealthInsight> {
  late Future<_HomeHealthInsightData> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<_HomeHealthInsightData> _load() async {
    final repository = context.read<PersonalHealthRepository>();
    final dashboard = await repository.dashboard(days: 30);
    final summary = Map<String, dynamic>.from(dashboard['summary'] ?? {});
    final followUpCount = (summary['follow_up_count'] as num?)?.toInt() ?? 0;
    final activeEpisodeCount =
        (summary['active_episode_count'] as num?)?.toInt() ?? 0;
    final todayCheckInCount =
        (summary['today_check_in_count'] as num?)?.toInt() ?? 0;
    final latestAssessment = dashboard['latest_assessment'] == null
        ? null
        : Map<String, dynamic>.from(dashboard['latest_assessment']);

    Map<String, dynamic>? aiSummary;
    if (followUpCount > 0) {
      try {
        aiSummary = await repository.aiTrendSummary(days: 30);
      } catch (_) {
        // The health card still provides a useful, non-diagnostic fallback.
      }
    }

    return _HomeHealthInsightData(
      followUpCount: followUpCount,
      activeEpisodeCount: activeEpisodeCount,
      todayCheckInCount: todayCheckInCount,
      latestAssessment: latestAssessment,
      aiSummary: aiSummary,
    );
  }

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  @override
  Widget build(BuildContext context) => FutureBuilder<_HomeHealthInsightData>(
    future: _future,
    builder: (context, snapshot) {
      if (snapshot.connectionState != ConnectionState.done) {
        return const _HomeHealthInsightShell(
          icon: Icons.auto_awesome_rounded,
          title: 'กำลังสรุปสุขภาพของคุณ',
          message: 'ตรวจสอบข้อมูลบันทึกและการติดตามอาการล่าสุด...',
        );
      }

      if (snapshot.hasError || snapshot.data == null) {
        return _HomeHealthInsightShell(
          icon: Icons.favorite_outline_rounded,
          title: 'บันทึกสุขภาพวันนี้',
          message: 'เพิ่มข้อมูลสั้น ๆ เพื่อให้เห็นแนวโน้มสุขภาพได้ต่อเนื่อง',
          actionLabel: 'ลองโหลดอีกครั้ง',
          onAction: _refresh,
        );
      }

      final data = snapshot.data!;
      final aiText = data.summaryText;
      final hasFollowUps = data.activeEpisodeCount > 0;
      return _HomeHealthInsightShell(
        icon: hasFollowUps
            ? Icons.auto_awesome_rounded
            : Icons.edit_calendar_outlined,
        title: hasFollowUps
            ? 'มี ${data.activeEpisodeCount} รายการที่กำลังติดตาม'
            : (data.todayCheckInCount > 0
                  ? 'บันทึกสุขภาพวันนี้แล้ว'
                  : 'บันทึกสุขภาพวันนี้'),
        message: hasFollowUps
            ? (aiText ??
                  'มีข้อมูลติดตาม ${data.followUpCount} ครั้ง ดูแนวโน้มเพื่อเปรียบเทียบการเปลี่ยนแปลงของอาการ')
            : (data.todayCheckInCount > 0
                  ? 'คุณ Check-in แล้ว ${data.todayCheckInCount} ครั้ง และยังบันทึกเพิ่มได้เมื่อมีการเปลี่ยนแปลง'
                  : 'ใช้เวลาสั้นๆ เพื่อบันทึกว่าตอนนี้คุณรู้สึกอย่างไร'),
        actionLabel: hasFollowUps
            ? (data.latestAssessment == null ? 'ดูแนวโน้ม' : 'ติดตามอาการ')
            : 'บันทึกสุขภาพ',
        onAction: () {
          if (!hasFollowUps) {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => const DailyHealthRecordScreen(),
              ),
            ).then((_) => _refresh());
            return;
          }
          final assessmentId = data.latestAssessment?['id'];
          final symptomName = data.latestAssessment?['symptom_name']
              ?.toString()
              .trim();
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => assessmentId == null
                  ? const HealthDashboardScreen()
                  : FollowUpScreen(
                      assessmentId: assessmentId,
                      symptomName: symptomName?.isNotEmpty == true
                          ? symptomName!
                          : 'อาการที่กำลังติดตาม',
                    ),
            ),
          ).then((_) => _refresh());
        },
        secondaryActionLabel: hasFollowUps ? 'ดูแนวโน้ม' : null,
        onSecondaryAction: hasFollowUps
            ? () => Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const HealthDashboardScreen(),
                ),
              )
            : null,
      );
    },
  );
}

class _HomeHealthInsightData {
  final int followUpCount;
  final int activeEpisodeCount;
  final int todayCheckInCount;
  final Map<String, dynamic>? latestAssessment;
  final Map<String, dynamic>? aiSummary;

  const _HomeHealthInsightData({
    required this.followUpCount,
    required this.activeEpisodeCount,
    required this.todayCheckInCount,
    required this.latestAssessment,
    required this.aiSummary,
  });

  String? get summaryText {
    final value =
        aiSummary?['summary'] ??
        aiSummary?['overview'] ??
        aiSummary?['recommendation'];
    if (value is String && value.trim().isNotEmpty) return value.trim();
    if (value is List && value.isNotEmpty) {
      return value.map((item) => item.toString()).join(' • ');
    }
    return null;
  }
}

class _HomeHealthInsightShell extends StatelessWidget {
  final IconData icon;
  final String title;
  final String message;
  final String? actionLabel;
  final VoidCallback? onAction;
  final String? secondaryActionLabel;
  final VoidCallback? onSecondaryAction;

  const _HomeHealthInsightShell({
    required this.icon,
    required this.title,
    required this.message,
    this.actionLabel,
    this.onAction,
    this.secondaryActionLabel,
    this.onSecondaryAction,
  });

  @override
  Widget build(BuildContext context) => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(18),
    decoration: BoxDecoration(
      gradient: const LinearGradient(
        colors: [AppColors.primary, AppColors.primaryMid],
      ),
      borderRadius: BorderRadius.circular(22),
    ),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 46,
          height: 46,
          decoration: BoxDecoration(
            color: AppColors.white.withValues(alpha: 0.16),
            borderRadius: BorderRadius.circular(14),
          ),
          child: Icon(icon, color: AppColors.white),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: AppTextStyles.body1Bold.copyWith(color: AppColors.white),
              ),
              const SizedBox(height: 5),
              Text(
                message,
                maxLines: 4,
                overflow: TextOverflow.ellipsis,
                style: AppTextStyles.body2.copyWith(
                  color: AppColors.white.withValues(alpha: 0.9),
                ),
              ),
              if (actionLabel != null && onAction != null) ...[
                const SizedBox(height: 10),
                Wrap(
                  spacing: 8,
                  children: [
                    TextButton(
                      onPressed: onAction,
                      style: TextButton.styleFrom(
                        foregroundColor: AppColors.white,
                        padding: EdgeInsets.zero,
                      ),
                      child: Text(actionLabel!),
                    ),
                    if (secondaryActionLabel != null &&
                        onSecondaryAction != null)
                      TextButton(
                        onPressed: onSecondaryAction,
                        style: TextButton.styleFrom(
                          foregroundColor: AppColors.white,
                        ),
                        child: Text(secondaryActionLabel!),
                      ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ],
    ),
  );
}

class _RecommendedArticles extends StatelessWidget {
  final VoidCallback onSeeAll;

  const _RecommendedArticles({required this.onSeeAll});

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<ArticleProvider>();
    final articles = provider.articles.take(4).toList();

    if (!provider.isLoading && articles.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(child: Text('บทความยอดนิยม', style: AppTextStyles.h4)),
            TextButton(onPressed: onSeeAll, child: const Text('ดูทั้งหมด')),
          ],
        ),
        const SizedBox(height: 10),
        if (provider.isLoading && articles.isEmpty)
          const _ArticleLoadingCard()
        else
          SizedBox(
            height: 214,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: articles.length,
              separatorBuilder: (_, _) => const SizedBox(width: 12),
              itemBuilder: (_, index) =>
                  _RecommendedArticleCard(article: articles[index]),
            ),
          ),
      ],
    );
  }
}

class _RecommendedArticleCard extends StatelessWidget {
  final dynamic article;

  const _RecommendedArticleCard({required this.article});

  @override
  Widget build(BuildContext context) {
    final thumbnail = article['thumbnail']?.toString();
    final category = article['category']?['category_name']?.toString();

    return SizedBox(
      width: 238,
      child: Card(
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => ArticleDetailScreen(
                articleId: article['article_id'].toString(),
              ),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SizedBox(
                height: 112,
                width: double.infinity,
                child: thumbnail != null && thumbnail.isNotEmpty
                    ? Image.network(
                        thumbnail,
                        fit: BoxFit.cover,
                        errorBuilder: (_, _, _) =>
                            const _ArticleImagePlaceholder(),
                      )
                    : const _ArticleImagePlaceholder(),
              ),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(14, 11, 14, 12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (category != null && category.isNotEmpty)
                        Text(
                          category,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: AppTextStyles.body3Bold.copyWith(
                            color: AppColors.primary,
                          ),
                        ),
                      const SizedBox(height: 4),
                      Text(
                        article['title']?.toString() ?? 'บทความสุขภาพ',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: AppTextStyles.body2Bold,
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

class _ArticleImagePlaceholder extends StatelessWidget {
  const _ArticleImagePlaceholder();

  @override
  Widget build(BuildContext context) => ColoredBox(
    color: AppColors.surfacePrimary,
    child: const Center(
      child: Icon(Icons.menu_book_rounded, color: AppColors.primary, size: 32),
    ),
  );
}

class _ArticleLoadingCard extends StatelessWidget {
  const _ArticleLoadingCard();

  @override
  Widget build(BuildContext context) => Container(
    height: 180,
    decoration: BoxDecoration(
      color: AppColors.surfaceElevated,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: AppColors.border),
    ),
    child: const Center(child: AppLoadingSpinner(size: 32)),
  );
}

class _Header extends StatefulWidget {
  final bool isLoggedIn;
  final String token;

  const _Header({required this.isLoggedIn, required this.token});

  @override
  State<_Header> createState() => _HeaderState();
}

class _HeaderState extends State<_Header> with WidgetsBindingObserver {
  int _unreadCount = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _loadUnreadCount();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _loadUnreadCount();
  }

  @override
  void didUpdateWidget(covariant _Header oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.token != widget.token ||
        oldWidget.isLoggedIn != widget.isLoggedIn) {
      _loadUnreadCount();
    }
  }

  Future<void> _loadUnreadCount() async {
    if (!widget.isLoggedIn || widget.token.isEmpty) {
      if (mounted) setState(() => _unreadCount = 0);
      return;
    }

    try {
      final response = await http.get(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.notificationsUnreadCount}',
        ),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer ${widget.token}',
        },
      );
      if (!mounted || response.statusCode != 200) return;
      final data = jsonDecode(utf8.decode(response.bodyBytes));
      setState(() => _unreadCount = data['unread_count'] as int? ?? 0);
    } catch (_) {
      // Keep the last known count when the network is temporarily unavailable.
    }
  }

  Future<void> _openNotifications() async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => NotificationScreen(token: widget.token),
      ),
    );
    await _loadUnreadCount();
  }

  @override
  Widget build(BuildContext context) => Row(
    children: [
      const AppLogoSmall(size: 38),
      const SizedBox(width: 9),
      Text('CHECKUP', style: AppTextStyles.logo_h2.copyWith(fontSize: 23)),
      const Spacer(),
      if (!widget.isLoggedIn)
        FilledButton.tonal(
          onPressed: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const LoginScreen()),
          ),
          style: FilledButton.styleFrom(
            minimumSize: const Size(0, 42),
            padding: const EdgeInsets.symmetric(horizontal: 16),
          ),
          child: const Text('เข้าสู่ระบบ'),
        )
      else
        Container(
          decoration: BoxDecoration(
            color: AppColors.surfaceElevated,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: AppColors.border),
          ),
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              IconButton(
                tooltip: 'การแจ้งเตือน',
                icon: const Icon(Icons.notifications_none_rounded),
                onPressed: _openNotifications,
              ),
              if (_unreadCount > 0)
                Positioned(
                  right: 2,
                  top: 2,
                  child: Container(
                    constraints: const BoxConstraints(minWidth: 18),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 4,
                      vertical: 1,
                    ),
                    decoration: BoxDecoration(
                      color: AppColors.danger,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: AppColors.white, width: 1.5),
                    ),
                    child: Text(
                      _unreadCount > 99 ? '99+' : '$_unreadCount',
                      textAlign: TextAlign.center,
                      style: AppTextStyles.body3.copyWith(
                        color: AppColors.white,
                        fontSize: 10,
                        height: 1.2,
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
    ],
  );
}

class _AssessmentCard extends StatelessWidget {
  final bool isLoggedIn;
  final VoidCallback onTap;

  const _AssessmentCard({required this.isLoggedIn, required this.onTap});

  @override
  Widget build(BuildContext context) => Semantics(
    button: true,
    label: 'เริ่มประเมินอาการ',
    child: Material(
      color: AppColors.primary,
      borderRadius: BorderRadius.circular(22),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(22),
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'ประเมินอาการเบื้องต้น',
                      style: AppTextStyles.h4.copyWith(color: AppColors.white),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      isLoggedIn
                          ? 'เลือกอาการที่กำลังกังวล เพื่อรับคำแนะนำเบื้องต้น'
                          : 'เข้าสู่ระบบเพื่อบันทึกผลและติดตามอาการอย่างต่อเนื่อง',
                      style: AppTextStyles.body2.copyWith(
                        color: AppColors.white.withValues(alpha: 0.82),
                      ),
                    ),
                    const SizedBox(height: 18),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 10,
                      ),
                      decoration: BoxDecoration(
                        color: AppColors.white,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        isLoggedIn ? 'เริ่มประเมิน' : 'เข้าสู่ระบบเพื่อเริ่ม',
                        style: AppTextStyles.body2Bold.copyWith(
                          color: AppColors.primary,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              if (MediaQuery.textScalerOf(context).scale(16) / 16 <= 1.25) ...[
                const SizedBox(width: 12),
                Container(
                  width: 72,
                  height: 72,
                  decoration: BoxDecoration(
                    color: AppColors.white.withValues(alpha: 0.15),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.health_and_safety_rounded,
                    size: 38,
                    color: AppColors.white,
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    ),
  );
}

class _EmergencyCard extends StatelessWidget {
  final VoidCallback onTap;

  const _EmergencyCard({required this.onTap});

  @override
  Widget build(BuildContext context) => Semantics(
    button: true,
    label: 'กรณีฉุกเฉิน โทร 1669',
    child: Material(
      color: AppColors.surfaceElevated,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: AppColors.danger.withValues(alpha: 0.25)),
          ),
          child: Row(
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  color: AppColors.danger.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(
                  Icons.phone_in_talk_rounded,
                  color: AppColors.danger,
                ),
              ),
              const SizedBox(width: 13),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'กรณีฉุกเฉิน โทร 1669',
                      style: AppTextStyles.body1Bold,
                    ),
                    Text(
                      'บริการการแพทย์ฉุกเฉิน 24 ชั่วโมง',
                      style: AppTextStyles.body3.copyWith(
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              const Icon(
                Icons.chevron_right_rounded,
                color: AppColors.textSecondary,
              ),
            ],
          ),
        ),
      ),
    ),
  );
}

class _QuickMenu extends StatelessWidget {
  final double width;
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  const _QuickMenu({
    required this.width,
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) => SizedBox(
    width: width,
    child: Material(
      color: AppColors.surfaceElevated,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: Container(
          constraints: const BoxConstraints(minHeight: 126),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: AppColors.border),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(13),
                ),
                child: Icon(icon, color: AppColors.primary, size: 22),
              ),
              const SizedBox(height: 14),
              Text(
                title,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: AppTextStyles.body2Bold,
              ),
              const SizedBox(height: 2),
              Text(
                subtitle,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: AppTextStyles.body3.copyWith(
                  color: AppColors.textSecondary,
                ),
              ),
            ],
          ),
        ),
      ),
    ),
  );
}
