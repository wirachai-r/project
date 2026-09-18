import 'dart:async';

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import 'local_notification_service.dart';
import 'api_service.dart';
import '../../core/constants/api_constants.dart';

class PushNotificationService {
  PushNotificationService._();

  static final instance = PushNotificationService._();

  // Resolve Messaging only when Android notification setup runs. Creating it
  // eagerly would access the default Firebase app on web, where Firebase is
  // intentionally not initialized by main.dart.
  FirebaseMessaging get _messaging => FirebaseMessaging.instance;
  StreamSubscription<RemoteMessage>? _foregroundSubscription;
  StreamSubscription<RemoteMessage>? _openedSubscription;
  StreamSubscription<String>? _tokenSubscription;

  String? _token;
  ApiService? _api;

  String? get token => _token;

  void bindApi(ApiService api) {
    _api = api;
  }

  Future<void> syncToken() async {
    final token = _token;
    final api = _api;
    if (token == null || token.isEmpty || api == null || kIsWeb) return;
    try {
      await api.post(
        ApiConstants.devices,
        body: {
          'device_token': token,
          'device_type': defaultTargetPlatform == TargetPlatform.iOS
              ? 'ios'
              : 'android',
        },
      );
    } catch (error) {
      if (kDebugMode) debugPrint('Unable to sync FCM token: $error');
    }
  }

  Future<void> unregisterToken() async {
    final token = _token;
    final api = _api;
    if (token == null || token.isEmpty || api == null || kIsWeb) return;
    try {
      await api.delete(ApiConstants.currentDevice, body: {'device_token': token});
    } catch (error) {
      if (kDebugMode) debugPrint('Unable to unregister FCM token: $error');
    }
  }

  Future<void> initialize() async {
    await _messaging.requestPermission(alert: true, badge: true, sound: true);
    await _messaging.setForegroundNotificationPresentationOptions(
      alert: true,
      badge: true,
      sound: true,
    );

    _token = await _messaging.getToken();
    if (kDebugMode) debugPrint('FCM registration token: $_token');
    _tokenSubscription = _messaging.onTokenRefresh.listen((token) {
      _token = token;
      if (kDebugMode) debugPrint('FCM registration token refreshed: $token');
      unawaited(syncToken());
    });

    _foregroundSubscription = FirebaseMessaging.onMessage.listen((message) {
      final notification = message.notification;
      if (notification == null) return;
      unawaited(
        LocalNotificationService.instance.showPushNotification(
          title: notification.title ?? 'Checkup',
          body: notification.body ?? '',
          payload: _payloadFor(message),
        ),
      );
    });

    _openedSubscription = FirebaseMessaging.onMessageOpenedApp.listen(
      _dispatchMessage,
    );

    final initialMessage = await _messaging.getInitialMessage();
    if (initialMessage != null) {
      final payload = _payloadFor(initialMessage);
      if (payload != null) {
        LocalNotificationService.instance.queueLaunchPayload(payload);
      }
    }
  }

  void _dispatchMessage(RemoteMessage message) {
    final payload = _payloadFor(message);
    if (payload != null) {
      LocalNotificationService.instance.dispatchPayload(payload);
    }
  }

  String? _payloadFor(RemoteMessage message) {
    final data = message.data;
    final explicitPayload = data['payload'];
    if (explicitPayload != null && explicitPayload.isNotEmpty) {
      return explicitPayload;
    }

    final type = data['target_type'] ?? data['type'];
    final id = data['target_id'] ?? data['id'];
    if (type != null && type.isNotEmpty && id != null && id.isNotEmpty) {
      return '$type:$id';
    }
    return null;
  }

  Future<void> dispose() async {
    await _foregroundSubscription?.cancel();
    await _openedSubscription?.cancel();
    await _tokenSubscription?.cancel();
  }
}
