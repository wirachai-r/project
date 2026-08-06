import 'package:flutter/material.dart';
import 'package:flutter/gestures.dart';
import 'package:provider/provider.dart';
import 'core/theme/app_theme.dart';

import 'data/services/api_service.dart';
import 'data/services/auth_service.dart';
import 'data/repositories/auth_repository.dart';
import 'data/repositories/symptom_repository.dart';
import 'data/repositories/assessment_repository.dart';
import 'data/repositories/disease_repository.dart';
import 'data/repositories/history_repository.dart';
import 'data/repositories/personal_health_repository.dart';

import 'features/auth/providers/auth_provider.dart';
import 'features/assessment/providers/assessment_provider.dart';
import 'features/disease/providers/disease_provider.dart';
import 'features/disease/providers/disease_detail_provider.dart';
import 'features/history/providers/history_provider.dart';
import 'features/history/providers/history_detail_provider.dart';
import 'features/auth/screens/splash_screen.dart';

class CheckupApp extends StatelessWidget {
  final ApiService apiService;
  final AuthService authService;

  const CheckupApp({
    super.key,
    required this.apiService,
    required this.authService,
  });

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(
          create: (_) => AuthProvider(
            repo: AuthRepository(api: apiService, authService: authService),
          )..init(),
        ),
        Provider<SymptomRepository>(
          create: (_) => SymptomRepository(api: apiService),
        ),
        Provider<AssessmentRepository>(
          create: (_) => AssessmentRepository(api: apiService),
        ),
        Provider<DiseaseRepository>(
          create: (_) => DiseaseRepository(api: apiService),
        ),
        Provider<HistoryRepository>(
          create: (_) => HistoryRepository(api: apiService),
        ),
        Provider<PersonalHealthRepository>(
          create: (_) => PersonalHealthRepository(api: apiService),
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
      ],
      child: MaterialApp(
        title: 'Checkup',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.theme,
        scrollBehavior: const _AppScrollBehavior(),
        home: const SplashScreen(),
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
