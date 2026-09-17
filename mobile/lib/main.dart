import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'data/services/api_service.dart';
import 'data/services/auth_service.dart';
import 'data/services/local_notification_service.dart';
import 'data/services/push_notification_service.dart';
import 'app.dart';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await LocalNotificationService.instance.initialize();

  if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
    await Firebase.initializeApp();
    FirebaseMessaging.onBackgroundMessage(
      firebaseMessagingBackgroundHandler,
    );
    await PushNotificationService.instance.initialize();
  }

  final apiService = ApiService();
  PushNotificationService.instance.bindApi(apiService);
  final authService = AuthService();
  apiService.setSessionToken(await authService.getSessionToken());

  runApp(CheckupApp(apiService: apiService, authService: authService));
}
