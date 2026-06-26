import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'core/theme/app_theme.dart';
import 'data/services/api_service.dart';
import 'data/services/auth_service.dart';
import 'data/repositories/auth_repository.dart';
import 'data/repositories/symptom_repository.dart';
import 'data/repositories/assessment_repository.dart';
import 'features/auth/providers/auth_provider.dart';
import 'features/assessment/providers/assessment_provider.dart';
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
        ChangeNotifierProvider(create: (_) => AssessmentProvider()),
      ],
      child: MaterialApp(
        title: 'Checkup',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.theme,
        home: const SplashScreen(),
      ),
    );
  }
}
