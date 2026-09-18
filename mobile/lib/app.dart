import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/gestures.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:provider/provider.dart';
import 'core/theme/app_theme.dart';

import 'data/services/api_service.dart';
import 'data/services/auth_service.dart';
import 'data/services/google_auth_service.dart';
import 'data/repositories/auth_repository.dart';
import 'data/repositories/symptom_repository.dart';
import 'data/repositories/assessment_repository.dart';
import 'data/repositories/adaptive_assessment_repository.dart';
import 'data/repositories/disease_repository.dart';
import 'data/repositories/history_repository.dart';
import 'data/repositories/personal_health_repository.dart';

import 'features/auth/providers/auth_provider.dart';
import 'features/assessment/providers/assessment_provider.dart';
import 'features/assessment/providers/assessment_mode_provider.dart';
import 'features/disease/providers/disease_provider.dart';
import 'features/disease/providers/disease_detail_provider.dart';
import 'features/history/providers/history_provider.dart';
import 'features/history/providers/history_detail_provider.dart';
import 'features/article/providers/article_provider.dart';
import 'features/auth/screens/splash_screen.dart';
import 'features/accessibility/providers/accessibility_provider.dart';
import 'features/theme/providers/theme_provider.dart';
import 'data/services/local_notification_service.dart';
import 'features/history/screens/history_detail_screen.dart';
import 'features/health/screens/daily_health_record_screen.dart';
import 'features/health/screens/follow_up_screen.dart';

class CheckupApp extends StatefulWidget {
  final ApiService apiService;
  final AuthService authService;

  const CheckupApp({
    super.key,
    required this.apiService,
    required this.authService,
  });

  @override
  State<CheckupApp> createState() => _CheckupAppState();
}

class _CheckupAppState extends State<CheckupApp> {
  final _navigatorKey = GlobalKey<NavigatorState>();
  StreamSubscription<String>? _notificationSubscription;

  @override
  void initState() {
    super.initState();
    _notificationSubscription = LocalNotificationService.instance.payloads
        .listen(_openNotificationTarget);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final payload = LocalNotificationService.instance.takeLaunchPayload();
      if (payload != null) {
        Future<void>.delayed(
          const Duration(seconds: 2),
          () => _openNotificationTarget(payload),
        );
      }
    });
  }

  @override
  void dispose() {
    _notificationSubscription?.cancel();
    super.dispose();
  }

  void _openNotificationTarget(String payload) {
    final parts = payload.split(':');
    if (parts.length < 2) return;
    final navigator = _navigatorKey.currentState;
    if (navigator == null) return;

    final Widget? screen = switch (parts[0]) {
      'assessment' => HistoryDetailScreen(assessmentId: parts[1]),
      'health_episode' => FollowUpScreen(
          episodeId: parts[1],
          symptomName: 'รายละเอียดการติดตามอาการ',
        ),
      'daily_health_record' => DailyHealthRecordScreen(
          initialDate: parts.length > 2 ? DateTime.tryParse(parts[2]) : null,
        ),
      _ => null,
    };
    if (screen != null) {
      navigator.push(MaterialPageRoute(builder: (_) => screen));
    }
  }

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AccessibilityProvider()..load()),
        ChangeNotifierProvider(create: (_) => ThemeProvider()..load()),
        ChangeNotifierProvider(
          create: (_) => AuthProvider(
            repo: AuthRepository(
              api: widget.apiService,
              authService: widget.authService,
              googleAuthService: GoogleAuthService(),
            ),
          )..init(),
        ),
        Provider<SymptomRepository>(
          create: (_) => SymptomRepository(api: widget.apiService),
        ),
        Provider<AssessmentRepository>(
          create: (_) =>
              AssessmentRepository(
                api: widget.apiService,
                authService: widget.authService,
              ),
        ),
        Provider<AdaptiveAssessmentRepository>(
          create: (_) => AdaptiveAssessmentRepository(api: widget.apiService, authService: widget.authService),
        ),
        ChangeNotifierProvider(
          create: (_) => AssessmentModeProvider(widget.apiService)..load(),
        ),
        Provider<DiseaseRepository>(
          create: (_) => DiseaseRepository(api: widget.apiService),
        ),
        Provider<HistoryRepository>(
          create: (_) => HistoryRepository(api: widget.apiService),
        ),
        Provider<PersonalHealthRepository>(
          create: (_) => PersonalHealthRepository(api: widget.apiService),
        ),
        ChangeNotifierProvider(
          create: (context) => AssessmentProvider(
            repository: context.read<AssessmentRepository>(),
          ),
        ),
        ChangeNotifierProvider(
          create: (context) =>
              DiseaseProvider(repository: context.read<DiseaseRepository>()),
        ),
        ChangeNotifierProvider(
          create: (context) => DiseaseDetailProvider(
            repository: context.read<DiseaseRepository>(),
          ),
        ),
        ChangeNotifierProvider(
          create: (context) =>
              HistoryProvider(repository: context.read<HistoryRepository>()),
        ),
        ChangeNotifierProvider(
          create: (context) => HistoryDetailProvider(
            repository: context.read<HistoryRepository>(),
          ),
        ),
        ChangeNotifierProvider(
          create: (_) => ArticleProvider()..sort = 'popular',
        ),
      ],
      child: Consumer2<AccessibilityProvider, ThemeProvider>(
        builder: (context, accessibility, themeProvider, _) => MaterialApp(
          navigatorKey: _navigatorKey,
          title: 'Checkup',
          debugShowCheckedModeBanner: false,
          locale: const Locale('th', 'TH'),
          supportedLocales: const [Locale('th', 'TH'), Locale('en', 'US')],
          localizationsDelegates: const [
            GlobalMaterialLocalizations.delegate,
            GlobalWidgetsLocalizations.delegate,
            GlobalCupertinoLocalizations.delegate,
          ],
          theme: accessibility.highContrast
              ? AppTheme.highContrast
              : AppTheme.theme,
          darkTheme: accessibility.highContrast
              ? AppTheme.darkHighContrast
              : AppTheme.darkTheme,
          themeMode: themeProvider.themeMode,
          builder: (context, child) {
            final systemScale = MediaQuery.textScalerOf(context).scale(16) / 16;
            final combinedScale = (systemScale * accessibility.textScale)
                .clamp(0.9, 1.6)
                .toDouble();
            return MediaQuery(
              data: MediaQuery.of(
                context,
              ).copyWith(textScaler: TextScaler.linear(combinedScale)),
              child: GestureDetector(
                behavior: HitTestBehavior.translucent,
                onTap: () => FocusManager.instance.primaryFocus?.unfocus(),
                child: child ?? const SizedBox.shrink(),
              ),
            );
          },
          scrollBehavior: const _AppScrollBehavior(),
          home: const SplashScreen(),
        ),
      ),
    );
  }
}

class _AppScrollBehavior extends MaterialScrollBehavior {
  const _AppScrollBehavior();

  @override
  Set<PointerDeviceKind> get dragDevices => const {
    PointerDeviceKind.touch,
    PointerDeviceKind.mouse,
    PointerDeviceKind.trackpad,
    PointerDeviceKind.stylus,
  };
}
