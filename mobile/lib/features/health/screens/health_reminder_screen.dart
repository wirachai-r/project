import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:mobile/data/services/central_http_client.dart' as http;

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../data/services/local_notification_service.dart';
import '../../../shared/widgets/app_feedback.dart';

class HealthReminderScreen extends StatefulWidget {
  final String token;

  const HealthReminderScreen({super.key, required this.token});

  @override
  State<HealthReminderScreen> createState() => _HealthReminderScreenState();
}

class _HealthReminderScreenState extends State<HealthReminderScreen> {
  static const _dayLabels = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'];

  Map<String, dynamic>? _reminder;
  bool _loading = true;
  bool _saving = false;
  bool _testing = false;
  bool _enabled = false;
  TimeOfDay _time = const TimeOfDay(hour: 10, minute: 0);
  Set<int> _selectedDays = {1, 2, 3, 4, 5, 6, 7};

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

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.healthReminders}'),
        headers: _headers,
      );
      if (response.statusCode != 200) throw Exception();
      final body = jsonDecode(utf8.decode(response.bodyBytes));
      final items = List<Map<String, dynamic>>.from(body['data'] ?? []);
      final dailyRecords = items
          .where((item) => item['reminder_type'] == 'daily_record')
          .toList();
      final reminder = dailyRecords.isEmpty ? null : dailyRecords.first;
      if (reminder != null) {
        await LocalNotificationService.instance.schedule(reminder);
      }
      if (!mounted) return;
      setState(() {
        _reminder = reminder;
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
    final previous = Set<int>.from(_selectedDays);
    setState(() {
      if (_selectedDays.contains(day)) {
        if (_selectedDays.length > 1) _selectedDays.remove(day);
      } else {
        _selectedDays.add(day);
      }
    });
    if (!await _save()) {
      if (mounted) setState(() => _selectedDays = previous);
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
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text('ตั้งค่าการแจ้งเตือน', style: AppTextStyles.h4),
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, thickness: 1, color: AppColors.border),
        ),
      ),
      body: _loading
          ? const AppLoadingView()
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(18, 20, 18, 24),
                children: [
                  _buildIntro(),
                  const SizedBox(height: 16),
                  _buildSettingsCard(),
                  const SizedBox(height: 16),
                  _buildTestButton(),
                  const SizedBox(height: 16),
                  _buildPermissionNote(),
                ],
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
                color: AppColors.textSecondary,
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
      color: AppColors.white,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: AppColors.border),
    ),
    child: Column(
      children: [
        SwitchListTile.adaptive(
          contentPadding: const EdgeInsets.symmetric(horizontal: 16),
          title: Text(
            'แจ้งเตือนบันทึกสุขภาพประจำวัน',
            style: AppTextStyles.body1Bold.copyWith(
              fontSize: 15,
              height: 1.35,
            ),
          ),
          subtitle: Text(
            _enabled ? 'เปิดใช้งานอยู่' : 'ปิดใช้งานอยู่',
            style: AppTextStyles.body3.copyWith(
              color: _enabled
                  ? AppColors.successText
                  : AppColors.textSecondary,
              fontWeight: FontWeight.w500,
            ),
          ),
          value: _enabled,
          onChanged: _saving ? null : _setEnabled,
        ),
        const Divider(height: 1),
        ListTile(
          enabled: _enabled && !_saving,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 16,
            vertical: 4,
          ),
          leading: Icon(
            Icons.schedule_rounded,
            color: _enabled ? AppColors.textSecondary : AppColors.textHint,
          ),
          title: Text(
            'เวลาแจ้งเตือน',
            style: AppTextStyles.body1.copyWith(
              color: _enabled ? AppColors.textPrimary : AppColors.textHint,
              fontWeight: FontWeight.w500,
            ),
          ),
          trailing: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                _timeValue,
                style: AppTextStyles.body1Bold.copyWith(
                  color: _enabled
                      ? AppColors.primary
                      : AppColors.textSecondary,
                ),
              ),
              const SizedBox(width: 4),
              Icon(
                Icons.chevron_right_rounded,
                color: _enabled
                    ? AppColors.textSecondary
                    : AppColors.textHint,
              ),
            ],
          ),
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
                        ? AppColors.textPrimary
                        : AppColors.textHint,
                  ),
                  const SizedBox(width: 14),
                  Text(
                    'วันที่แจ้งเตือน',
                    style: AppTextStyles.body1.copyWith(
                      color: _enabled
                          ? AppColors.textPrimary
                          : AppColors.textHint,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const Spacer(),
                  Text(
                    _selectedDays.length == 7 ? 'ทุกวัน' : 'เลือกวัน',
                    style: AppTextStyles.body3.copyWith(
                      color: AppColors.textSecondary,
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
                                ? AppColors.surface
                                : selected
                                ? AppColors.surfacePrimary
                                : AppColors.white,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(
                              color: selected && _enabled
                                  ? AppColors.primary
                                  : AppColors.border,
                            ),
                          ),
                          child: Text(
                            _dayLabels[index],
                            style: AppTextStyles.body3.copyWith(
                              color: selected && _enabled
                                  ? AppColors.primary
                                  : AppColors.textSecondary,
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

  Widget _buildPermissionNote() => Container(
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(
      color: AppColors.surfacePrimary,
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
              color: AppColors.textSecondary,
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
      label: Text(
        _testing ? 'กำลังส่งการแจ้งเตือน...' : 'ทดสอบการแจ้งเตือน',
      ),
      style: OutlinedButton.styleFrom(
        backgroundColor: AppColors.surfacePrimary,
        foregroundColor: AppColors.primary,
        side: const BorderSide(color: AppColors.primary, width: 1.2),
      ),
    ),
  );
}
