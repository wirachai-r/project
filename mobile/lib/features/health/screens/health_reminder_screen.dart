import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:checkup/data/services/central_http_client.dart' as http;

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../data/services/local_notification_service.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../shared/widgets/app_layout.dart';
import '../../../shared/widgets/symptom_icon.dart';

class HealthReminderScreen extends StatefulWidget {
  final String token;

  const HealthReminderScreen({super.key, required this.token});

  @override
  State<HealthReminderScreen> createState() => _HealthReminderScreenState();
}

class _HealthReminderScreenState extends State<HealthReminderScreen> {
  static const _dayLabels = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'];

  Map<String, dynamic>? _reminder;
  List<Map<String, dynamic>> _followUpReminders = [];
  Map<String, Map<String, dynamic>> _activeEpisodesById = {};
  final Set<dynamic> _followUpBusyIds = {};
  bool _loading = true;
  bool _saving = false;
  bool _testing = false;
  bool _enabled = false;
  TimeOfDay _time = const TimeOfDay(hour: 8, minute: 0);
  Set<int> _selectedDays = {1, 2, 3, 4, 5, 6, 7};
  Timer? _daySaveTimer;
  Set<int>? _daysBeforePendingSave;

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _daySaveTimer?.cancel();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final responses = await Future.wait([
        http.get(
          Uri.parse('${ApiConstants.baseUrl}${ApiConstants.healthReminders}'),
          headers: _headers,
        ),
        http.get(
          Uri.parse('${ApiConstants.baseUrl}${ApiConstants.healthEpisodes}'),
          headers: _headers,
        ),
      ]);
      if (responses.any((response) => response.statusCode != 200)) {
        throw Exception();
      }
      final body = jsonDecode(utf8.decode(responses[0].bodyBytes));
      final items = List<Map<String, dynamic>>.from(body['data'] ?? []);
      final dailyRecords = items
          .where((item) => item['reminder_type'] == 'daily_record')
          .toList();
      final reminder = dailyRecords.isEmpty ? null : dailyRecords.first;
      final followUpReminders = items
          .where((item) => item['reminder_type'] == 'follow_up')
          .toList();
      final episodeBody = jsonDecode(utf8.decode(responses[1].bodyBytes));
      final activeEpisodes = List<Map<String, dynamic>>.from(
        episodeBody['data'] ?? [],
      ).where((episode) => episode['status'] == 'A').toList();
      if (reminder != null) {
        await LocalNotificationService.instance.schedule(reminder);
      }
      if (!mounted) return;
      setState(() {
        _reminder = reminder;
        _followUpReminders = followUpReminders;
        _activeEpisodesById = {
          for (final episode in activeEpisodes)
            episode['id'].toString(): episode,
        };
        _enabled = reminder?['is_enabled'] == true;
        _time = _parseTime(reminder?['time_of_day']) ?? _time;
        final days = List<int>.from(reminder?['days_of_week'] ?? const []);
        _selectedDays = reminder?['frequency'] == 'weekly' && days.isNotEmpty
            ? days.toSet()
            : {1, 2, 3, 4, 5, 6, 7};
      });
    } catch (_) {
      if (mounted) _message('ไม่สามารถโหลดการตั้งค่าการแจ้งเตือนได้');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _episodeTitle(Map<String, dynamic> episode) {
    final symptoms = List<Map<String, dynamic>>.from(
      episode['symptoms'] ?? const [],
    );
    final names = symptoms
        .where((item) => item['status'] == 'A')
        .map((item) => item['symptom_name']?.toString().trim())
        .whereType<String>()
        .where((name) => name.isNotEmpty)
        .toList();
    if (names.isEmpty) return 'ติดตามอาการ';
    if (names.length == 1) return names.first;
    return '${names.first} และอีก ${names.length - 1} อาการ';
  }

  Map<String, dynamic>? _episodeForReminder(Map<String, dynamic> reminder) =>
      _activeEpisodesById[reminder['health_episode_id']?.toString()];

  String? _episodeIcon(Map<String, dynamic>? episode) {
    if (episode == null) return null;
    final symptoms = List<Map<String, dynamic>>.from(
      episode['symptoms'] ?? const [],
    );
    if (symptoms.isEmpty) return null;
    final primary = symptoms.firstWhere(
      (item) => item['is_primary'] == true,
      orElse: () => symptoms.first,
    );
    return primary['symptom_icon']?.toString();
  }

  Widget _symptomIconBox(Map<String, dynamic>? episode) => Container(
    width: 42,
    height: 42,
    decoration: BoxDecoration(
      color: AppColors.primaryLight,
      borderRadius: BorderRadius.circular(12),
    ),
    alignment: Alignment.center,
    child: SymptomIcon(
      iconName: _episodeIcon(episode),
      size: 24,
      color: AppColors.primary,
    ),
  );

  Future<void> _createFollowUpReminder(
    Map<String, dynamic> episode,
  ) async {
    final selected = await showTimePicker(
      context: context,
      initialTime: const TimeOfDay(hour: 8, minute: 0),
      helpText: 'เลือกเวลาเตือนติดตามอาการ',
      cancelText: 'ยกเลิก',
      confirmText: 'ตั้งเวลา',
    );
    if (selected == null || !mounted) return;
    final allowed = await LocalNotificationService.instance
        .requestPermission();
    if (!allowed) {
      _message('กรุณาอนุญาตการแจ้งเตือนในการตั้งค่าโทรศัพท์');
      return;
    }

    final episodeId = episode['id'];
    if (_followUpBusyIds.contains(episodeId)) return;
    setState(() => _followUpBusyIds.add(episodeId));
    try {
      final title = _episodeTitle(episode);
      final time =
          '${selected.hour.toString().padLeft(2, '0')}:${selected.minute.toString().padLeft(2, '0')}';
      final response = await http.post(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.healthReminders}'),
        headers: _headers,
        body: jsonEncode({
          'health_episode_id': episodeId,
          'title': title,
          'reminder_type': 'follow_up',
          'frequency': 'daily',
          'time_of_day': time,
          'timezone': 'Asia/Bangkok',
          'is_enabled': true,
        }),
      );
      if (response.statusCode != 200 && response.statusCode != 201) {
        throw Exception();
      }
      final reminder = Map<String, dynamic>.from(
        jsonDecode(utf8.decode(response.bodyBytes))['data'],
      );
      await LocalNotificationService.instance.schedule(reminder);
      if (!mounted) return;
      setState(() {
        _followUpReminders.add(reminder);
      });
      await LocalNotificationService.instance.showActivity(
        title: 'ตั้งค่าแจ้งเตือนติดตามอาการแล้ว',
        body: 'ระบบจะเตือนทุกวัน เวลา $time น.',
        payload: 'health_episode:$episodeId',
      );
      _message('ตั้งเวลาเตือนทุกวัน เวลา $time น. แล้ว');
    } catch (_) {
      if (mounted) _message('ตั้งเวลาเตือนติดตามอาการไม่สำเร็จ');
    } finally {
      if (mounted) setState(() => _followUpBusyIds.remove(episodeId));
    }
  }

  Future<void> _setFollowUpEnabled(
    Map<String, dynamic> reminder,
    bool enabled,
  ) async {
    await _updateFollowUpReminder(reminder, isEnabled: enabled);
  }

  Future<void> _chooseFollowUpTime(Map<String, dynamic> reminder) async {
    final current =
        _parseTime(reminder['time_of_day']) ??
        const TimeOfDay(hour: 8, minute: 0);
    final selected = await showTimePicker(
      context: context,
      initialTime: current,
      helpText: 'เลือกเวลาเตือนติดตามอาการ',
      cancelText: 'ยกเลิก',
      confirmText: 'ตกลง',
    );
    if (selected == null || !mounted) return;
    await _updateFollowUpReminder(
      reminder,
      timeOfDay:
          '${selected.hour.toString().padLeft(2, '0')}:${selected.minute.toString().padLeft(2, '0')}',
    );
  }

  Future<void> _updateFollowUpReminder(
    Map<String, dynamic> reminder, {
    bool? isEnabled,
    String? timeOfDay,
  }) async {
    final id = reminder['id'];
    if (_followUpBusyIds.contains(id)) return;
    setState(() => _followUpBusyIds.add(id));
    try {
      final payload = {
        'health_episode_id': reminder['health_episode_id'],
        'title': reminder['title'],
        'reminder_type': 'follow_up',
        'frequency': reminder['frequency'] ?? 'daily',
        'time_of_day': timeOfDay ?? reminder['time_of_day'],
        'days_of_week': reminder['days_of_week'],
        'timezone': reminder['timezone'] ?? 'Asia/Bangkok',
        'is_enabled': isEnabled ?? reminder['is_enabled'] == true,
      };
      final response = await http.put(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.healthReminder(id)}'),
        headers: _headers,
        body: jsonEncode(payload),
      );
      if (response.statusCode != 200) throw Exception();
      final updated = Map<String, dynamic>.from(
        jsonDecode(utf8.decode(response.bodyBytes))['data'],
      );
      try {
        await LocalNotificationService.instance.schedule(updated);
      } catch (_) {
        // The server value is authoritative. Notification permissions or an
        // OS scheduling failure must not make a successful save look failed.
      }
      if (!mounted) return;
      setState(() {
        final index = _followUpReminders.indexWhere((item) => item['id'] == id);
        if (index >= 0) _followUpReminders[index] = updated;
      });
    } catch (_) {
      if (mounted) _message('บันทึกการตั้งค่าเตือนติดตามอาการไม่สำเร็จ');
    } finally {
      if (mounted) setState(() => _followUpBusyIds.remove(id));
    }
  }

  Future<void> _deleteFollowUpReminder(Map<String, dynamic> reminder) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('ลบการแจ้งเตือนนี้?'),
        content: Text('ระบบจะหยุดเตือน “${reminder['title']}”'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('ยกเลิก'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: const Text('ลบ'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;
    final id = reminder['id'];
    setState(() => _followUpBusyIds.add(id));
    try {
      final response = await http.delete(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.healthReminder(id)}'),
        headers: _headers,
      );
      if (response.statusCode != 200) throw Exception();
      final notificationId = int.tryParse(id.toString());
      if (notificationId != null) {
        try {
          await LocalNotificationService.instance.cancel(notificationId);
        } catch (_) {
          // The server deletion is authoritative; stale local scheduling must
          // not hide the still-active tracking episode from this screen.
        }
      }
      if (mounted) {
        setState(() {
          _followUpReminders.removeWhere((item) => item['id'] == id);
        });
      }
    } catch (_) {
      if (mounted) _message('ลบการแจ้งเตือนไม่สำเร็จ');
    } finally {
      if (mounted) setState(() => _followUpBusyIds.remove(id));
    }
  }

  TimeOfDay? _parseTime(dynamic value) {
    final parts = value?.toString().split(':');
    if (parts == null || parts.length < 2) return null;
    final hour = int.tryParse(parts[0]);
    final minute = int.tryParse(parts[1]);
    if (hour == null || minute == null) return null;
    return TimeOfDay(hour: hour, minute: minute);
  }

  String get _timeValue =>
      '${_time.hour.toString().padLeft(2, '0')}:${_time.minute.toString().padLeft(2, '0')}';

  Future<void> _setEnabled(bool value) async {
    if (_saving) return;
    _daySaveTimer?.cancel();
    _daysBeforePendingSave = null;
    if (value) {
      final allowed = await LocalNotificationService.instance
          .requestPermission();
      if (!allowed) {
        _message('กรุณาอนุญาตการแจ้งเตือนในการตั้งค่าโทรศัพท์');
        return;
      }
    }
    final previous = _enabled;
    setState(() => _enabled = value);
    if (!await _save()) {
      if (mounted) setState(() => _enabled = previous);
    }
  }

  Future<void> _chooseTime() async {
    if (!_enabled || _saving) return;
    final selected = await showTimePicker(
      context: context,
      initialTime: _time,
      helpText: 'เลือกเวลาแจ้งเตือน',
      cancelText: 'ยกเลิก',
      confirmText: 'ตกลง',
    );
    if (selected == null || !mounted) return;
    final previous = _time;
    setState(() => _time = selected);
    if (!await _save()) {
      if (mounted) setState(() => _time = previous);
    }
  }

  Future<void> _toggleDay(int day) async {
    if (!_enabled || _saving) return;
    _daysBeforePendingSave ??= Set<int>.from(_selectedDays);
    setState(() {
      if (_selectedDays.contains(day)) {
        if (_selectedDays.length > 1) _selectedDays.remove(day);
      } else {
        _selectedDays.add(day);
      }
    });
    _daySaveTimer?.cancel();
    _daySaveTimer = Timer(const Duration(milliseconds: 500), _saveSelectedDays);
  }

  Future<void> _saveSelectedDays() async {
    final previous = _daysBeforePendingSave;
    _daysBeforePendingSave = null;
    if (!await _save() && mounted && previous != null) {
      setState(() => _selectedDays = previous);
    }
  }

  Future<bool> _save() async {
    if (_saving) return false;
    setState(() => _saving = true);
    try {
      final isDaily = _selectedDays.length == 7;
      final payload = {
        'title': 'บันทึกสุขภาพประจำวัน',
        'reminder_type': 'daily_record',
        'frequency': isDaily ? 'daily' : 'weekly',
        'time_of_day': _timeValue,
        'days_of_week': isDaily ? null : (_selectedDays.toList()..sort()),
        'timezone': 'Asia/Bangkok',
        'is_enabled': _enabled,
      };
      final id = _reminder?['id'];
      final uri = Uri.parse(
        id == null
            ? '${ApiConstants.baseUrl}${ApiConstants.healthReminders}'
            : '${ApiConstants.baseUrl}${ApiConstants.healthReminder(id)}',
      );
      final response = id == null
          ? await http.post(uri, headers: _headers, body: jsonEncode(payload))
          : await http.put(uri, headers: _headers, body: jsonEncode(payload));
      if (response.statusCode != 200 && response.statusCode != 201) {
        throw Exception();
      }
      final data = Map<String, dynamic>.from(
        jsonDecode(utf8.decode(response.bodyBytes))['data'],
      );
      await LocalNotificationService.instance.schedule(data);
      if (mounted) setState(() => _reminder = data);
      if (id == null) {
        await LocalNotificationService.instance.showActivity(
          title: 'ตั้งค่าแจ้งเตือนสุขภาพประจำวันแล้ว',
          body: 'ระบบจะเตือนตามวันและเวลาที่เลือกไว้',
          payload: 'daily_health_record:settings',
        );
      }
      return true;
    } catch (_) {
      if (mounted) _message('ไม่สามารถบันทึกการตั้งค่าได้ กรุณาลองใหม่');
      return false;
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _testNotification() async {
    if (_testing) return;
    setState(() => _testing = true);
    try {
      final allowed = await LocalNotificationService.instance
          .requestPermission();
      if (!allowed) {
        _message('กรุณาอนุญาตการแจ้งเตือนในการตั้งค่าโทรศัพท์');
        return;
      }
      await LocalNotificationService.instance.showTestNotification();
      if (mounted) _message('ส่งการแจ้งเตือนทดสอบแล้ว');
    } catch (_) {
      if (mounted) _message('ไม่สามารถส่งการแจ้งเตือนทดสอบได้');
    } finally {
      if (mounted) setState(() => _testing = false);
    }
  }

  void _message(String text) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(text)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text('ตั้งค่าการแจ้งเตือน', style: AppTextStyles.h4),
        bottom: PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(
            height: 1,
            thickness: 1,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: _loading
          ? const AppLoadingView()
          : RefreshIndicator(
              onRefresh: _load,
              child: AppContentWidth(
                child: ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.fromLTRB(18, 20, 18, 24),
                  children: [
                    _buildIntro(),
                    const SizedBox(height: 16),
                    _buildSettingsCard(),
                    const SizedBox(height: 24),
                    _buildFollowUpSection(),
                    const SizedBox(height: 16),
                    _buildTestButton(),
                    const SizedBox(height: 16),
                    _buildPermissionNote(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildIntro() => Row(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Container(
        width: 48,
        height: 48,
        decoration: BoxDecoration(
          color: AppColors.primaryLight,
          borderRadius: BorderRadius.circular(14),
        ),
        child: const Icon(Icons.alarm_rounded, color: AppColors.primary),
      ),
      const SizedBox(width: 12),
      Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('เตือนบันทึกสุขภาพ', style: AppTextStyles.body1Bold),
            const SizedBox(height: 3),
            Text(
              'ช่วยให้คุณไม่พลาดการบันทึกและติดตามสุขภาพประจำวัน',
              style: AppTextStyles.body2.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
                height: 1.45,
              ),
            ),
          ],
        ),
      ),
    ],
  );

  Widget _buildSettingsCard() => Container(
    decoration: BoxDecoration(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
    ),
    child: Column(
      children: [
        SwitchListTile.adaptive(
          contentPadding: const EdgeInsets.symmetric(horizontal: 16),
          title: Text(
            'แจ้งเตือนบันทึกสุขภาพประจำวัน',
            style: AppTextStyles.body1Bold.copyWith(fontSize: 15, height: 1.35),
          ),
          subtitle: Text(
            _enabled ? 'เปิดใช้งานอยู่' : 'ปิดใช้งานอยู่',
            style: AppTextStyles.body3.copyWith(
              color: _enabled
                  ? AppColors.successText
                  : Theme.of(context).colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w500,
            ),
          ),
          value: _enabled,
          onChanged: _saving ? null : _setEnabled,
        ),
        const Divider(height: 1),
        _buildTimeTile(
          enabled: _enabled && !_saving,
          timeLabel: _timeValue,
          onTap: _chooseTime,
        ),
        const Divider(height: 1),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(
                    Icons.calendar_month_outlined,
                    color: _enabled
                        ? Theme.of(context).colorScheme.onSurface
                        : Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                  const SizedBox(width: 14),
                  Text(
                    'วันที่แจ้งเตือน',
                    style: AppTextStyles.body1.copyWith(
                      color: _enabled
                          ? Theme.of(context).colorScheme.onSurface
                          : Theme.of(context).colorScheme.onSurfaceVariant,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const Spacer(),
                  Text(
                    _selectedDays.length == 7 ? 'ทุกวัน' : 'เลือกวัน',
                    style: AppTextStyles.body3.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              Row(
                children: List.generate(7, (index) {
                  final day = index + 1;
                  final selected = _selectedDays.contains(day);
                  return Expanded(
                    child: Padding(
                      padding: EdgeInsets.only(left: index == 0 ? 0 : 5),
                      child: InkWell(
                        onTap: _enabled && !_saving
                            ? () => _toggleDay(day)
                            : null,
                        borderRadius: BorderRadius.circular(10),
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 180),
                          height: 38,
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                            color: !_enabled
                                ? Theme.of(context).colorScheme.surfaceContainer
                                : selected
                                ? Theme.of(
                                    context,
                                  ).colorScheme.surfaceContainerLow
                                : Theme.of(context).colorScheme.surface,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(
                              color: selected && _enabled
                                  ? AppColors.primary
                                  : Theme.of(
                                      context,
                                    ).colorScheme.outlineVariant,
                            ),
                          ),
                          child: Text(
                            _dayLabels[index],
                            style: AppTextStyles.body3.copyWith(
                              color: selected && _enabled
                                  ? AppColors.primary
                                  : Theme.of(
                                      context,
                                    ).colorScheme.onSurfaceVariant,
                              fontWeight: selected
                                  ? FontWeight.w600
                                  : FontWeight.w400,
                            ),
                          ),
                        ),
                      ),
                    ),
                  );
                }),
              ),
            ],
          ),
        ),
        if (_saving)
          const LinearProgressIndicator(
            minHeight: 2,
            color: AppColors.primary,
            backgroundColor: AppColors.primaryLight,
          ),
      ],
    ),
  );

  Widget _buildTimeTile({
    required bool enabled,
    required String timeLabel,
    required VoidCallback onTap,
  }) => ListTile(
    enabled: enabled,
    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
    minLeadingWidth: 24,
    horizontalTitleGap: 12,
    leading: Icon(
      Icons.schedule_rounded,
      color: Theme.of(context).colorScheme.onSurfaceVariant,
    ),
    title: Text(
      'เวลาแจ้งเตือน',
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
      style: AppTextStyles.body1.copyWith(
        color: enabled
            ? Theme.of(context).colorScheme.onSurface
            : Theme.of(context).colorScheme.onSurfaceVariant,
        fontWeight: FontWeight.w500,
      ),
    ),
    trailing: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          timeLabel,
          style: AppTextStyles.body1Bold.copyWith(
            color: enabled
                ? AppColors.primary
                : Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
        const SizedBox(width: 4),
        Icon(
          Icons.chevron_right_rounded,
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
      ],
    ),
    onTap: enabled ? onTap : null,
  );

  Widget _buildPermissionNote() => Container(
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(
      color: Theme.of(context).colorScheme.surfaceContainerLow,
      borderRadius: BorderRadius.circular(14),
    ),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Icon(
          Icons.info_outline_rounded,
          color: AppColors.primary,
          size: 20,
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            'การแจ้งเตือนจะทำงานเมื่อคุณอนุญาตการแจ้งเตือนสำหรับแอปนี้',
            style: AppTextStyles.body2.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
              height: 1.45,
            ),
          ),
        ),
      ],
    ),
  );

  Widget _buildTestButton() => SizedBox(
    width: double.infinity,
    child: OutlinedButton.icon(
      onPressed: _testing ? null : _testNotification,
      icon: _testing
          ? const SizedBox.square(
              dimension: 18,
              child: CircularProgressIndicator(strokeWidth: 2),
            )
          : const Icon(Icons.notifications_active_outlined),
      label: Text(_testing ? 'กำลังส่งการแจ้งเตือน...' : 'ทดสอบการแจ้งเตือน'),
      style: OutlinedButton.styleFrom(
        backgroundColor: Theme.of(context).colorScheme.surfaceContainerLow,
        foregroundColor: AppColors.primary,
        side: const BorderSide(color: AppColors.primary, width: 1.2),
      ),
    ),
  );

  Widget _buildFollowUpSection() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Row(
        children: [
          const Icon(Icons.monitor_heart_outlined, color: AppColors.primary),
          const SizedBox(width: 9),
          Text('เตือนติดตามอาการ', style: AppTextStyles.body1Bold),
        ],
      ),
      const SizedBox(height: 5),
      Text(
        'ตั้งค่าแยกตามรายการ และหยุดเตือนอัตโนมัติเมื่อสิ้นสุดการติดตาม',
        style: AppTextStyles.body3.copyWith(
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
      ),
      const SizedBox(height: 12),
      if (_followUpReminders.isEmpty && _activeEpisodesById.isEmpty)
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: Theme.of(context).colorScheme.outlineVariant,
            ),
          ),
          child: Text(
            'ยังไม่มีรายการติดตามอาการที่กำลังใช้งาน',
            textAlign: TextAlign.center,
            style: AppTextStyles.body2.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
              height: 1.5,
            ),
          ),
        ),
      ..._orderedFollowUpCards(),
    ],
  );

  List<Widget> _orderedFollowUpCards() {
    final widgets = <Widget>[];
    final renderedReminderIds = <String>{};

    for (final episode in _activeEpisodesById.values) {
      Map<String, dynamic>? matchingReminder;
      for (final reminder in _followUpReminders) {
        if (reminder['health_episode_id']?.toString() ==
            episode['id']?.toString()) {
          matchingReminder = reminder;
          break;
        }
      }

      if (matchingReminder == null) {
        widgets.add(_buildEpisodeWithoutReminderCard(episode));
      } else {
        renderedReminderIds.add(matchingReminder['id'].toString());
        widgets.add(_buildFollowUpCard(matchingReminder));
      }
    }

    for (final reminder in _followUpReminders) {
      if (!renderedReminderIds.contains(reminder['id'].toString())) {
        widgets.add(_buildFollowUpCard(reminder));
      }
    }

    return widgets;
  }

  Widget _buildEpisodeWithoutReminderCard(Map<String, dynamic> episode) {
    final id = episode['id'];
    final busy = _followUpBusyIds.contains(id);

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.fromLTRB(16, 14, 12, 14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Row(
        children: [
          _symptomIconBox(episode),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(_episodeTitle(episode), style: AppTextStyles.body1Bold),
                const SizedBox(height: 2),
                Text(
                  'กำลังติดตามอยู่ · ยังไม่ได้ตั้งเวลาเตือน',
                  style: AppTextStyles.body3.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          FilledButton.tonal(
            onPressed: busy ? null : () => _createFollowUpReminder(episode),
            child: busy
                ? const SizedBox.square(
                    dimension: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text('ตั้งเวลา'),
          ),
        ],
      ),
    );
  }

  Widget _buildFollowUpCard(Map<String, dynamic> reminder) {
    final id = reminder['id'];
    final enabled = reminder['is_enabled'] == true;
    final busy = _followUpBusyIds.contains(id);
    final time = _parseTime(reminder['time_of_day']);
    final timeLabel = time == null
        ? '--:--'
        : '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Column(
        children: [
          SwitchListTile.adaptive(
            contentPadding: const EdgeInsets.only(left: 16, right: 10),
            secondary: _symptomIconBox(_episodeForReminder(reminder)),
            title: Text(
              reminder['title']?.toString() ?? 'ติดตามอาการ',
              style: AppTextStyles.body1Bold,
            ),
            subtitle: Text(
              enabled ? 'เปิดใช้งานอยู่' : 'ปิดใช้งานอยู่',
              style: AppTextStyles.body3.copyWith(
                color: enabled
                    ? AppColors.successText
                    : Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
            value: enabled,
            onChanged: busy
                ? null
                : (value) => _setFollowUpEnabled(reminder, value),
          ),
          const Divider(height: 1),
          _buildTimeTile(
            enabled: enabled && !busy,
            timeLabel: timeLabel,
            onTap: () => _chooseFollowUpTime(reminder),
          ),
          const Divider(height: 1),
          Align(
            alignment: Alignment.centerRight,
            child: TextButton.icon(
              onPressed: busy ? null : () => _deleteFollowUpReminder(reminder),
              icon: const Icon(Icons.delete_outline_rounded),
              label: const Text('ลบการแจ้งเตือน'),
              style: TextButton.styleFrom(foregroundColor: AppColors.danger),
            ),
          ),
          if (busy)
            const LinearProgressIndicator(
              minHeight: 2,
              color: AppColors.primary,
              backgroundColor: AppColors.primaryLight,
            ),
        ],
      ),
    );
  }
}
