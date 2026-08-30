import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:timezone/data/latest.dart' as tz;
import 'package:timezone/timezone.dart' as tz;

class LocalNotificationService {
  LocalNotificationService._();

  static final instance = LocalNotificationService._();
  final _plugin = FlutterLocalNotificationsPlugin();

  Future<void> initialize() async {
    if (kIsWeb) return;

    tz.initializeTimeZones();
    tz.setLocalLocation(tz.getLocation('Asia/Bangkok'));

    await _plugin.initialize(
      const InitializationSettings(
        android: AndroidInitializationSettings('@mipmap/ic_launcher'),
        iOS: DarwinInitializationSettings(),
        windows: WindowsInitializationSettings(
          appName: 'Health Checkup',
          appUserModelId: 'com.healthcheckup.mobile',
          guid: '8d90ce5e-1d62-4c86-9840-4b85b96d42f7',
        ),
      ),
    );
  }

  Future<bool> requestPermission() async {
    if (kIsWeb) return true;

    final android = await _plugin
        .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin
        >()
        ?.requestNotificationsPermission();
    final ios = await _plugin
        .resolvePlatformSpecificImplementation<
          IOSFlutterLocalNotificationsPlugin
        >()
        ?.requestPermissions(alert: true, badge: true, sound: true);
    return android ?? ios ?? true;
  }

  Future<void> schedule(Map<String, dynamic> reminder) async {
    if (kIsWeb) return;

    final id = reminder['id'] as int;
    await cancel(id);
    if (reminder['is_enabled'] != true) return;

    final parts = (reminder['time_of_day'] as String).split(':');
    final hour = int.parse(parts[0]);
    final minute = int.parse(parts[1]);
    final details = NotificationDetails(
      android: const AndroidNotificationDetails(
        'health_reminders',
        'การแจ้งเตือนสุขภาพ',
        channelDescription: 'เตือนบันทึกและติดตามสุขภาพ',
        importance: Importance.high,
        priority: Priority.high,
      ),
      iOS: const DarwinNotificationDetails(),
    );

    if (reminder['frequency'] == 'weekly') {
      final days = List<int>.from(reminder['days_of_week'] ?? []);
      for (final day in days) {
        final scheduled = _nextTime(hour, minute, weekday: day);
        await _plugin.zonedSchedule(
          id * 10 + day,
          reminder['title'] as String,
          'ถึงเวลาบันทึกและติดตามสุขภาพของคุณแล้ว',
          scheduled,
          details,
          androidScheduleMode: AndroidScheduleMode.inexactAllowWhileIdle,
          matchDateTimeComponents: DateTimeComponents.dayOfWeekAndTime,
          payload: 'health_reminder:$id',
        );
      }
      return;
    }

    await _plugin.zonedSchedule(
      id * 10,
      reminder['title'] as String,
      'ถึงเวลาบันทึกและติดตามสุขภาพของคุณแล้ว',
      _nextTime(hour, minute),
      details,
      androidScheduleMode: AndroidScheduleMode.inexactAllowWhileIdle,
      matchDateTimeComponents: DateTimeComponents.time,
      payload: 'health_reminder:$id',
    );
  }

  Future<void> showTestNotification() async {
    if (kIsWeb) return;

    const details = NotificationDetails(
      android: AndroidNotificationDetails(
        'health_reminders',
        'การแจ้งเตือนสุขภาพ',
        channelDescription: 'เตือนบันทึกและติดตามสุขภาพ',
        importance: Importance.high,
        priority: Priority.high,
      ),
      iOS: DarwinNotificationDetails(),
    );
    await _plugin.show(
      900000,
      'ทดสอบการแจ้งเตือนสุขภาพ',
      'การแจ้งเตือนทำงานเรียบร้อยแล้ว',
      details,
      payload: 'health_reminder:test',
    );
  }

  Future<void> cancel(int reminderId) async {
    if (kIsWeb) return;

    await _plugin.cancel(reminderId * 10);
    for (var day = 1; day <= 7; day++) {
      await _plugin.cancel(reminderId * 10 + day);
    }
  }

  tz.TZDateTime _nextTime(int hour, int minute, {int? weekday}) {
    final now = tz.TZDateTime.now(tz.local);
    var scheduled = tz.TZDateTime(
      tz.local,
      now.year,
      now.month,
      now.day,
      hour,
      minute,
    );
    if (!scheduled.isAfter(now))
      scheduled = scheduled.add(const Duration(days: 1));
    if (weekday != null) {
      while (scheduled.weekday != weekday) {
        scheduled = scheduled.add(const Duration(days: 1));
      }
    }
    return scheduled;
  }
}
