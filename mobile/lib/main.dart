import 'package:flutter/material.dart';
import 'data/services/api_service.dart';
import 'data/services/auth_service.dart';
import 'data/services/local_notification_service.dart';
import 'app.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await LocalNotificationService.instance.initialize();

  final apiService = ApiService();
  final authService = AuthService();

  runApp(CheckupApp(apiService: apiService, authService: authService));
}
