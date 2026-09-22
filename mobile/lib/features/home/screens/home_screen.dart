import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:checkup/data/services/central_http_client.dart' as http;
import 'package:provider/provider.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/utils/media_url.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../data/repositories/personal_health_repository.dart';
import '../../../shared/widgets/app_logo.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../shared/widgets/login_bottom_sheet.dart';
import '../../article/screens/article_list_screen.dart';
import '../../article/screens/article_detail_screen.dart';
import '../../article/providers/article_provider.dart';
import '../../assessment/screens/body_area_group_screen.dart';
import '../../assessment/screens/assessment_screen.dart';
import '../../assessment/providers/assessment_provider.dart';
import '../../assessment/providers/assessment_mode_provider.dart';
import '../../auth/providers/auth_provider.dart';
import '../../auth/screens/login_screen.dart';
import '../../disease/screens/disease_list_screen.dart';
import '../../facility/screens/facility_screen.dart';
import '../../first_aid/screens/first_aid_list_screen.dart';
import '../../health/screens/daily_health_record_screen.dart';
import '../../health/screens/health_dashboard_screen.dart';
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
  String _articleInitialSort = 'all';

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
      if (articles.articles.isEmpty && !articles.isLoading) {
        articles.loadArticles(refresh: true);
      }
    });
  }

  void _onTabTap(int index) {
    final auth = context.read<AuthProvider>();
    final isLoggedIn = auth.token?.isNotEmpty ?? false;

    if (!isLoggedIn && (index == 2 || index == 3)) {
      LoginBottomSheet.show(context);
      return;
    }
    setState(() {
      _tab = index;
      if (index == HomeScreen.articlesTab) {
        _articleInitialSort = 'all';
      }
    });
  }

  void _showPopularArticles() {
    setState(() {
      _articleInitialSort = 'popular';
      _tab = HomeScreen.articlesTab;
    });
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final colors = Theme.of(context).colorScheme;
    final token = auth.token ?? '';
    final isLoggedIn = token.isNotEmpty;

    if (!isLoggedIn && (_tab == 2 || _tab == 3)) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) setState(() => _tab = 0);
      });
    }

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: switch (_tab) {
        0 => _HomeTab(
          onNavigateToTab: _onTabTap,
          onSeeAllPopularArticles: _showPopularArticles,
        ),
        1 => ArticleListScreen(
          key: ValueKey(_articleInitialSort),
          initialSort: _articleInitialSort,
        ),
        2 =>
          isLoggedIn
              ? const DailyHealthRecordScreen()
              : _HomeTab(
                  onNavigateToTab: _onTabTap,
                  onSeeAllPopularArticles: _showPopularArticles,
                ),
        3 =>
          isLoggedIn
              ? HistoryListScreen()
              : _HomeTab(
                  onNavigateToTab: _onTabTap,
                  onSeeAllPopularArticles: _showPopularArticles,
                ),
        4 => ProfileScreen(
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
        ),
        _ => _HomeTab(
          onNavigateToTab: _onTabTap,
          onSeeAllPopularArticles: _showPopularArticles,
        ),
      },
      bottomNavigationBar: DecoratedBox(
        decoration: BoxDecoration(
          color: colors.surfaceContainerLowest,
          boxShadow: [
            BoxShadow(
              color: colors.shadow.withValues(alpha: 0.07),
              blurRadius: 12,
              offset: const Offset(0, -2),
            ),
          ],
          border: Border(
            top: BorderSide(color: colors.outlineVariant, width: 1),
          ),
        ),
        child: NavigationBar(
          height: 76,
          elevation: 0,
          backgroundColor: colors.surfaceContainerLowest,
          indicatorColor: Colors.transparent,
          overlayColor: const WidgetStatePropertyAll(Colors.transparent),
          labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
          selectedIndex: _tab,
          onDestinationSelected: _onTabTap,
          destinations: const [
            NavigationDestination(
              icon: Icon(Icons.home_rounded),
              selectedIcon: Icon(Icons.home_rounded),
              label: 'หน้าแรก',
            ),
            NavigationDestination(
              icon: Icon(Icons.article_rounded),
              selectedIcon: Icon(Icons.article_rounded),
              label: 'บทความ',
            ),
            NavigationDestination(
              icon: Icon(Icons.favorite_rounded),
              selectedIcon: Icon(Icons.favorite_rounded),
              label: 'สุขภาพ',
            ),
            NavigationDestination(
              icon: Icon(Icons.history_rounded),
              selectedIcon: Icon(Icons.history_rounded),
              label: 'ประวัติ',
            ),
            NavigationDestination(
              icon: Icon(Icons.person_rounded),
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
  final VoidCallback onSeeAllPopularArticles;

  const _HomeTab({
    required this.onNavigateToTab,
    required this.onSeeAllPopularArticles,
  });

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    final colors = Theme.of(context).colorScheme;
    final auth = context.watch<AuthProvider>();
    final token = auth.token ?? '';
    final isLoggedIn = token.isNotEmpty;
    final greeting = auth.user == null
        ? 'ดูแลสุขภาพได้ง่ายขึ้น'
        : 'สวัสดี ${auth.user!.firstName}';

    return SafeArea(
      child: ResponsiveContent(
        child: RefreshIndicator(
          color: AppColors.primary,
          onRefresh: () => context.read<ArticleProvider>().loadArticles(
            refresh: true,
          ),
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
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
              SizedBox(height: Responsive.dp(28)),
              Text(greeting, style: Theme.of(context).textTheme.headlineMedium),
              const SizedBox(height: 6),
              Text(
                'วันนี้คุณรู้สึกอย่างไร?',
                style: Theme.of(
                  context,
                ).textTheme.bodyLarge?.copyWith(color: colors.onSurfaceVariant),
              ),
              SizedBox(height: Responsive.dp(16)),
              Semantics(
                button: true,
                label: 'ค้นหาโรค บทความ และปฐมพยาบาล',
                child: SearchBar(
                  elevation: const WidgetStatePropertyAll(0),
                  backgroundColor: WidgetStatePropertyAll(colors.surface),
                  side: WidgetStatePropertyAll(
                    BorderSide(color: colors.outlineVariant),
                  ),
                  shape: WidgetStatePropertyAll(
                    RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  padding: const WidgetStatePropertyAll(
                    EdgeInsets.symmetric(horizontal: 16),
                  ),
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
              SizedBox(height: Responsive.dp(12)),
              _AssessmentModeSwitcher(isLoggedIn: isLoggedIn),
              SizedBox(height: Responsive.dp(12)),
              _AssessmentCard(
                isLoggedIn: isLoggedIn,
                onTap: () async {
                  if (!isLoggedIn) {
                    LoginBottomSheet.show(context);
                    return;
                  }

                  // Adaptive assessments are stored separately and do not use
                  // the classic pending-diagram resume flow.
                  if (context.read<AssessmentModeProvider>().isAdaptive) {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => const BodyAreaGroupScreen(),
                      ),
                    );
                    return;
                  }

                  final provider = context.read<AssessmentProvider>();
                  final pending = await provider.findPendingAssessment();
                  if (!context.mounted) return;

                  if (pending == null) {
                    if (provider.error != null) {
                      showAppError(context, provider.error!);
                      return;
                    }
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => const BodyAreaGroupScreen(),
                      ),
                    );
                    return;
                  }

                  final action = await showDialog<String>(
                    context: context,
                    barrierDismissible: false,
                    builder: (dialogContext) => AppActionDialog(
                      icon: Icons.assignment_outlined,
                      title: 'มีการประเมินที่ยังไม่เสร็จ',
                      message:
                          'คุณมีการประเมิน${pending.symptomName != null ? ' “${pending.symptomName}”' : ''}ที่ยังทำไม่เสร็จ ต้องการทำต่อหรือเริ่มใหม่?',
                      primaryLabel: 'ทำต่อ',
                      primaryIcon: Icons.arrow_forward_rounded,
                      onPrimary: () =>
                          Navigator.pop(dialogContext, 'resume'),
                      secondaryLabel: 'เริ่มใหม่',
                      onSecondary: () =>
                          Navigator.pop(dialogContext, 'restart'),
                    ),
                  );
                  if (!context.mounted) return;

                  if (action == 'resume') {
                    provider.resumeAssessment(pending.symptomId, pending);
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => AssessmentScreen(
                          symptomId: pending.symptomId,
                          symptomName: pending.symptomName,
                          resumeExisting: true,
                        ),
                      ),
                    );
                  } else if (action == 'restart') {
                    final abandoned = await provider.abandonPendingAssessment(
                      pending,
                    );
                    if (!context.mounted) return;
                    if (!abandoned) {
                      showAppError(
                        context,
                        provider.error ?? 'กรุณาลองใหม่อีกครั้ง',
                      );
                      return;
                    }
                    provider.reset();
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => const BodyAreaGroupScreen(),
                      ),
                    );
                  }
                },
              ),
              if (isLoggedIn) ...[
                SizedBox(height: Responsive.dp(14)),
                _HomeHealthInsight(onNavigateToTab: onNavigateToTab),
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
              Text(
                'บริการสุขภาพ',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
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
                  final scaledHeightAdjustment =
                      ((textScale - 1).clamp(0.0, 0.25) * 80).toDouble();
                  final itemHeight = useSingleColumn
                      ? null
                      : 160.0 + scaledHeightAdjustment;
                  return Wrap(
                    spacing: 12,
                    runSpacing: 12,
                    children: [
                      _QuickMenu(
                        width: itemWidth,
                        height: itemHeight,
                        icon: Icons.article_outlined,
                        title: 'ความรู้สุขภาพ',
                        subtitle: 'บทความน่าอ่าน',
                        onTap: () => onNavigateToTab(1),
                      ),
                      _QuickMenu(
                        width: itemWidth,
                        height: itemHeight,
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
                        height: itemHeight,
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
                        height: itemHeight,
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
                        height: itemHeight,
                        icon: Icons.favorite_border_rounded,
                        title: 'บันทึกสุขภาพ',
                        subtitle: 'บันทึกข้อมูลสุขภาพประจำวัน',
                        onTap: () => onNavigateToTab(2),
                      ),
                      _QuickMenu(
                        width: itemWidth,
                        height: itemHeight,
                        icon: Icons.insights_outlined,
                        title: 'แนวโน้มสุขภาพ',
                        subtitle: 'ดูสถิติและการเปลี่ยนแปลง',
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
              _RecommendedArticles(onSeeAll: onSeeAllPopularArticles),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _HomeHealthInsight extends StatefulWidget {
  final void Function(int) onNavigateToTab;

  const _HomeHealthInsight({required this.onNavigateToTab});

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
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final todayKey =
        '${today.year.toString().padLeft(4, '0')}-'
        '${today.month.toString().padLeft(2, '0')}-'
        '${today.day.toString().padLeft(2, '0')}';
    final dashboardFuture = repository.dashboard(days: 30);
    final episodesFuture = repository.healthEpisodes(
      from: todayKey,
      to: todayKey,
    );
    final dashboard = await dashboardFuture;
    final episodes = await episodesFuture;
    final summary = Map<String, dynamic>.from(dashboard['summary'] ?? {});
    final todayCheckInCount =
        (summary['today_check_in_count'] as num?)?.toInt() ?? 0;
    final pendingEpisodes = episodes.where((episode) {
      if (episode.status != 'A') return false;
      final startedAt = episode.startedAt.toLocal();
      final startedDate = DateTime(
        startedAt.year,
        startedAt.month,
        startedAt.day,
      );
      if (startedDate.isAfter(today)) return false;
      final hasActiveSymptom = episode.symptoms.any((symptom) {
        if (symptom.status != 'A') return false;
        final firstObserved = symptom.firstObservedAt?.toLocal();
        if (symptom.isPrimary || firstObserved == null) return true;
        final firstObservedDate = DateTime(
          firstObserved.year,
          firstObserved.month,
          firstObserved.day,
        );
        return !firstObservedDate.isAfter(today);
      });
      if (!hasActiveSymptom) return false;
      return !episode.symptoms.any(
        (symptom) => symptom.entries.any((entry) {
          final recordedAt = entry.recordedAt.toLocal();
          return recordedAt.year == today.year &&
              recordedAt.month == today.month &&
              recordedAt.day == today.day;
        }),
      );
    }).toList();

    return _HomeHealthInsightData(
      activeEpisodeCount: pendingEpisodes.length,
      todayCheckInCount: todayCheckInCount,
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
        return const _HomeHealthInsightLoading();
      }

      if (snapshot.hasError || snapshot.data == null) {
        return _HomeHealthInsightShell(
          icon: Icons.sync_problem_rounded,
          title: 'ตรวจสอบรายการวันนี้ไม่ได้',
          message: 'แตะเพื่อลองตรวจสอบรายการที่ยังไม่ได้ทำอีกครั้ง',
          actionLabel: 'ลองโหลดอีกครั้ง',
          onAction: _refresh,
        );
      }

      final data = snapshot.data!;
      final hasFollowUps = data.activeEpisodeCount > 0;
      final needsDailyRecord = data.todayCheckInCount == 0;
      if (!needsDailyRecord && !hasFollowUps) {
        return const _HomeHealthInsightShell(
          icon: Icons.task_alt_rounded,
          title: 'วันนี้ไม่มีรายการที่ยังไม่ได้ทำ',
          message: 'บันทึกสุขภาพและติดตามอาการประจำวันเรียบร้อยแล้ว',
        );
      }

      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(left: 2, bottom: 8),
            child: Text(
              'สิ่งที่ยังไม่ได้ทำวันนี้',
              style: AppTextStyles.body1Bold,
            ),
          ),
          if (needsDailyRecord)
            _HomeHealthInsightShell(
              icon: Icons.edit_calendar_outlined,
              title: 'ยังไม่ได้บันทึกสุขภาพวันนี้',
              message: 'ใช้เวลาสั้น ๆ บันทึกว่าตอนนี้คุณรู้สึกอย่างไร',
              actionLabel: 'บันทึกสุขภาพ',
              onAction: () => widget.onNavigateToTab(HomeScreen.healthTab),
            ),
          if (needsDailyRecord && hasFollowUps) const SizedBox(height: 12),
          if (hasFollowUps)
            _HomeHealthInsightShell(
              icon: Icons.monitor_heart_outlined,
              title:
                  'ยังไม่ได้ติดตามอาการวันนี้ ${data.activeEpisodeCount} รายการ',
              message: 'บันทึกอาการประจำวันเพื่อให้ข้อมูลการติดตามต่อเนื่อง',
              actionLabel: 'ติดตามอาการ',
              onAction: () => widget.onNavigateToTab(HomeScreen.healthTab),
            ),
        ],
      );
    },
  );
}

class _HomeHealthInsightData {
  final int activeEpisodeCount;
  final int todayCheckInCount;

  const _HomeHealthInsightData({
    required this.activeEpisodeCount,
    required this.todayCheckInCount,
  });
}

class _HomeHealthInsightLoading extends StatelessWidget {
  const _HomeHealthInsightLoading();

  @override
  Widget build(BuildContext context) => Semantics(
    label: 'กำลังโหลดข้อมูลสุขภาพ',
    liveRegion: true,
    child: Container(
      width: double.infinity,
      height: 82,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surfaceContainer,
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                FractionallySizedBox(
                  widthFactor: .62,
                  child: Container(
                    height: 11,
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.outlineVariant,
                      borderRadius: BorderRadius.circular(99),
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                FractionallySizedBox(
                  widthFactor: .88,
                  child: Container(
                    height: 9,
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.surfaceContainer,
                      borderRadius: BorderRadius.circular(99),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    ),
  );
}

class _HomeHealthInsightShell extends StatelessWidget {
  final IconData icon;
  final String title;
  final String message;
  final String? actionLabel;
  final VoidCallback? onAction;

  const _HomeHealthInsightShell({
    required this.icon,
    required this.title,
    required this.message,
    this.actionLabel,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) => Material(
    color: Theme.of(context).colorScheme.surface,
    borderRadius: BorderRadius.circular(16),
    child: InkWell(
      onTap: onAction,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 42,
              height: 42,
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
                  Text(title, style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 5),
                  Text(
                    message,
                    maxLines: 4,
                    overflow: TextOverflow.ellipsis,
                    style: AppTextStyles.body2.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                  ),
                  if (actionLabel != null && onAction != null) ...[
                    const SizedBox(height: 10),
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          actionLabel!,
                          style: AppTextStyles.body2Bold.copyWith(
                            color: AppColors.primary,
                          ),
                        ),
                        const SizedBox(width: 4),
                        const Icon(
                          Icons.arrow_forward_rounded,
                          size: 18,
                          color: AppColors.primary,
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
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

    if (!provider.isLoading && articles.isEmpty && provider.error == null) {
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
        else if (provider.error != null && articles.isEmpty)
          _ArticleLoadError(
            onRetry: () => provider.loadArticles(refresh: true),
          )
        else
          SizedBox(
            height: 244,
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
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: Theme.of(context).colorScheme.outlineVariant),
        ),
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
                        resolveMediaUrl(thumbnail)!,
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
                        style: Theme.of(context).textTheme.titleSmall,
                      ),
                      const Spacer(),
                      Wrap(
                        spacing: 10,
                        runSpacing: 4,
                        children: [
                          _articleMeta(
                            context,
                            Icons.calendar_today_outlined,
                            _formatArticleDate(article['published_at']),
                          ),
                          _articleMeta(
                            context,
                            Icons.visibility_outlined,
                            '${article['view_count'] ?? 0} ครั้ง',
                          ),
                        ],
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

  Widget _articleMeta(
    BuildContext context,
    IconData icon,
    String label,
  ) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Icon(
        icon,
        size: 13,
        color: Theme.of(context).colorScheme.onSurfaceVariant,
      ),
      const SizedBox(width: 3),
      Text(
        label,
        style: AppTextStyles.body3.copyWith(
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
      ),
    ],
  );

  String _formatArticleDate(dynamic value) {
    final date = DateTime.tryParse(value?.toString() ?? '')?.toLocal();
    return date == null ? '-' : formatThaiDate(date);
  }
}

class _ArticleLoadError extends StatelessWidget {
  final VoidCallback onRetry;

  const _ArticleLoadError({required this.onRetry});

  @override
  Widget build(BuildContext context) => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(18),
    decoration: BoxDecoration(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(16),
      border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
    ),
    child: Column(
      children: [
        Icon(
          Icons.cloud_off_outlined,
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
        const SizedBox(height: 8),
        const Text('โหลดบทความยอดนิยมไม่สำเร็จ'),
        TextButton.icon(
          onPressed: onRetry,
          icon: const Icon(Icons.refresh_rounded),
          label: const Text('ลองอีกครั้ง'),
        ),
      ],
    ),
  );
}

class _ArticleImagePlaceholder extends StatelessWidget {
  const _ArticleImagePlaceholder();

  @override
  Widget build(BuildContext context) => ColoredBox(
    color: Theme.of(context).colorScheme.surfaceContainerLow,
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
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
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
      const AppLogoSmall(size: 36),
      const SizedBox(width: 10),
      Text('CHECKUP', style: AppTextStyles.logo_h2.copyWith(fontSize: 21)),
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
        Stack(
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
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(18),
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
                        horizontal: 16,
                        vertical: 11,
                      ),
                      decoration: BoxDecoration(
                        color: Theme.of(context).brightness == Brightness.dark
                            ? Theme.of(context).colorScheme.primaryContainer
                            : Theme.of(context).colorScheme.surface,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        isLoggedIn ? 'เริ่มประเมิน' : 'เข้าสู่ระบบเพื่อเริ่ม',
                        style: AppTextStyles.body2Bold.copyWith(
                          color: Theme.of(context).brightness == Brightness.dark
                              ? Theme.of(context).colorScheme.onPrimaryContainer
                              : AppColors.primary,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              if (MediaQuery.textScalerOf(context).scale(16) / 16 <= 1.25) ...[
                const SizedBox(width: 16),
                const Icon(
                  Icons.arrow_forward_rounded,
                  size: 28,
                  color: AppColors.white,
                ),
              ],
            ],
          ),
        ),
      ),
    ),
  );
}

class _AssessmentModeSwitcher extends StatelessWidget {
  final bool isLoggedIn;

  const _AssessmentModeSwitcher({required this.isLoggedIn});

  Future<void> _selectMode(
    BuildContext context,
    AssessmentModeProvider provider,
    AssessmentMode mode,
  ) async {
    if (provider.mode == mode || provider.saving) return;

    try {
      await provider.setAdaptive(
        mode == AssessmentMode.adaptive,
        syncProfile: isLoggedIn,
      );
    } catch (_) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('เปลี่ยนรูปแบบการประเมินไม่สำเร็จ')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<AssessmentModeProvider>();
    final colors = Theme.of(context).colorScheme;

    return Semantics(
      label: 'เลือกรูปแบบการประเมินอาการ',
      child: Container(
        padding: const EdgeInsets.all(4),
        decoration: BoxDecoration(
          color: colors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: colors.outlineVariant),
        ),
        child: Row(
          children: [
            Expanded(
              child: _AssessmentModeButton(
                label: 'แบบแผนผังอาการ',
                icon: Icons.account_tree_outlined,
                selected: provider.mode == AssessmentMode.classic,
                enabled: !provider.saving,
                onTap: () => _selectMode(
                  context,
                  provider,
                  AssessmentMode.classic,
                ),
              ),
            ),
            const SizedBox(width: 4),
            Expanded(
              child: _AssessmentModeButton(
                label: 'แบบปรับตามคำตอบ',
                icon: Icons.question_answer_outlined,
                selected: provider.mode == AssessmentMode.adaptive,
                enabled: !provider.saving,
                onTap: () => _selectMode(
                  context,
                  provider,
                  AssessmentMode.adaptive,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _AssessmentModeButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final bool selected;
  final bool enabled;
  final VoidCallback onTap;

  const _AssessmentModeButton({
    required this.label,
    required this.icon,
    required this.selected,
    required this.enabled,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;

    return Semantics(
      button: true,
      selected: selected,
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: enabled ? onTap : null,
          borderRadius: BorderRadius.circular(10),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 180),
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
            decoration: BoxDecoration(
              color: selected ? AppColors.primary : Colors.transparent,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  icon,
                  size: 18,
                  color: selected ? AppColors.white : colors.onSurfaceVariant,
                ),
                const SizedBox(width: 6),
                Flexible(
                  child: Text(
                    label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: AppTextStyles.body3.copyWith(
                      fontWeight: FontWeight.w700,
                      color: selected
                          ? AppColors.white
                          : colors.onSurfaceVariant,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _EmergencyCard extends StatelessWidget {
  final VoidCallback onTap;

  const _EmergencyCard({required this.onTap});

  @override
  Widget build(BuildContext context) => Semantics(
    button: true,
    label: 'กรณีฉุกเฉิน โทร 1669',
    child: Material(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.danger.withValues(alpha: 0.25)),
          ),
          child: Row(
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.errorContainer,
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  Icons.phone_in_talk_rounded,
                  color: Theme.of(context).colorScheme.onErrorContainer,
                ),
              ),
              const SizedBox(width: 13),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'กรณีฉุกเฉิน โทร 1669',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    Text(
                      'บริการการแพทย์ฉุกเฉิน 24 ชั่วโมง',
                      style: AppTextStyles.body2.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.chevron_right_rounded,
                color: Theme.of(context).colorScheme.onSurfaceVariant,
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
  final double? height;
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  const _QuickMenu({
    required this.width,
    this.height,
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) => SizedBox(
    width: width,
    height: height,
    child: Material(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          constraints: const BoxConstraints(minHeight: 126),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: Theme.of(context).colorScheme.outlineVariant,
            ),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: AppColors.primary, size: 22),
              ),
              const SizedBox(height: 12),
              Text(
                title,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: Theme.of(context).textTheme.titleSmall,
              ),
              const SizedBox(height: 2),
              Text(
                subtitle,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: AppTextStyles.body3.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ),
        ),
      ),
    ),
  );
}
