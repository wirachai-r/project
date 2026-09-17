import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/buddhist_calendar_delegate.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../data/models/health_episode_model.dart';
import '../../../data/models/symptom_model.dart';
import '../../../data/repositories/personal_health_repository.dart';
import '../../../data/repositories/symptom_repository.dart';
import '../../../data/services/local_notification_service.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../shared/widgets/app_layout.dart';
import '../../../shared/widgets/symptom_icon.dart';
import 'daily_health_record_screen.dart';

class FollowUpScreen extends StatefulWidget {
  final dynamic assessmentId;
  final dynamic episodeId;
  final String symptomName;
  final bool offerReminder;
  final DateTime? recordDate;

  const FollowUpScreen({
    super.key,
    this.assessmentId,
    this.episodeId,
    required this.symptomName,
    this.offerReminder = false,
    this.recordDate,
  }) : assert(assessmentId != null || episodeId != null);

  @override
  State<FollowUpScreen> createState() => _FollowUpScreenState();
}

class _FollowUpScreenState extends State<FollowUpScreen> {
  HealthEpisodeModel? _episode;
  final Map<dynamic, _SymptomDraft> _drafts = {};
  final Set<dynamic> _expandedSymptomIds = {};
  bool _loading = true;
  bool _saving = false;
  String? _error;

  DateTime get _targetDate => widget.recordDate ?? DateTime.now();
  bool get _isToday {
    final now = DateTime.now();
    return _targetDate.year == now.year &&
        _targetDate.month == now.month &&
        _targetDate.day == now.day;
  }

  DateTime get _recordedAt {
    final now = DateTime.now();
    return DateTime(
      _targetDate.year,
      _targetDate.month,
      _targetDate.day,
      now.hour,
      now.minute,
      now.second,
    );
  }

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    for (final draft in _drafts.values) {
      draft.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final repository = context.read<PersonalHealthRepository>();
      if (widget.episodeId != null) {
        final episode = await repository.healthEpisode(widget.episodeId);
        if (!mounted) return;
        setState(() {
          _replaceEpisode(episode);
          _loading = false;
        });
        if (widget.offerReminder) {
          WidgetsBinding.instance.addPostFrameCallback(
            (_) => _offerReminder(episode),
          );
        }
        return;
      }
      final episodes = await repository.healthEpisodes();
      if (!mounted) return;
      final linked = episodes.where(
        (episode) => episode.assessments.any(
          (assessment) =>
              assessment.id.toString() == widget.assessmentId.toString(),
        ),
      );
      final selectedEpisodeId = linked.isNotEmpty
          ? linked.first.id
          : await _chooseTrackingDestination(
              episodes.where((episode) => episode.status == 'A').toList(),
            );
      if (!mounted) return;
      final isNewTracking = linked.isEmpty;
      final episode = linked.isNotEmpty
          ? await repository.healthEpisode(selectedEpisodeId)
          : await repository.startHealthEpisode(
              widget.assessmentId,
              healthEpisodeId: selectedEpisodeId,
            );
      if (!mounted) return;
      setState(() {
        _replaceEpisode(episode);
        _loading = false;
      });
      if (isNewTracking && selectedEpisodeId == null) {
        await LocalNotificationService.instance.showActivity(
          title: 'เริ่มติดตามอาการแล้ว',
          body: 'แตะเพื่อดูรายละเอียดการติดตามอาการ',
          payload: 'health_episode:${episode.id}',
        );
        WidgetsBinding.instance.addPostFrameCallback(
          (_) => _offerReminder(episode),
        );
      }
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error =
            'ไม่สามารถเปิดการติดตามอาการได้ กรุณาเข้าสู่ระบบแล้วลองอีกครั้ง';
      });
    }
  }

  Future<void> _offerReminder(HealthEpisodeModel episode) async {
    if (!mounted) return;
    final wantsReminder = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AppActionDialog(
        icon: Icons.notifications_active_outlined,
        title: 'ตั้งเวลาเตือนติดตามอาการ',
        message: 'ให้แอปเตือนทุกวันเพื่อบันทึกการเปลี่ยนแปลงของอาการหรือไม่?',
        primaryLabel: 'ตั้งเวลา',
        onPrimary: () => Navigator.pop(dialogContext, true),
        secondaryLabel: 'ไว้ภายหลัง',
        onSecondary: () => Navigator.pop(dialogContext, false),
      ),
    );
    if (wantsReminder != true || !mounted) return;
    final selected = await showTimePicker(
      context: context,
      initialTime: const TimeOfDay(hour: 8, minute: 0),
      helpText: 'เลือกเวลาเตือนติดตามอาการ',
    );
    if (selected == null || !mounted) return;
    final time =
        '${selected.hour.toString().padLeft(2, '0')}:${selected.minute.toString().padLeft(2, '0')}';
    try {
      await context.read<PersonalHealthRepository>().createFollowUpReminder(
        healthEpisodeId: episode.id,
        title: episode.symptoms.isEmpty
            ? 'ติดตามอาการ'
            : 'ติดตาม ${episode.symptoms.map((item) => item.symptomName).join(', ')}',
        timeOfDay: time,
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('ตั้งเวลาเตือนทุกวัน เวลา $time น. แล้ว')),
        );
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('ตั้งเวลาเตือนไม่สำเร็จ กรุณาลองใหม่')),
        );
      }
    }
  }

  Future<dynamic> _chooseTrackingDestination(
    List<HealthEpisodeModel> activeEpisodes,
  ) async {
    if (activeEpisodes.isEmpty) return null;
    return showModalBottomSheet<dynamic>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (sheetContext) => SafeArea(
        child: AppContentWidth(
          shrinkWrapHeight: true,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'ต้องการรวมกับรายการติดตามเดิมหรือแยกใหม่?',
                  style: AppTextStyles.h3,
                ),
                const SizedBox(height: 6),
                Text(
                  'เลือกรายการเดิมเมื่อผลประเมินนี้เป็นเหตุการณ์สุขภาพเดียวกัน',
                  style: AppTextStyles.body2.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                ),
                const SizedBox(height: 14),
                ...activeEpisodes.map(
                  (episode) => Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Material(
                      color: Theme.of(context).colorScheme.surface,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                        side: BorderSide(
                          color: Theme.of(context).colorScheme.outlineVariant,
                        ),
                      ),
                      clipBehavior: Clip.antiAlias,
                      child: ListTile(
                        minTileHeight: 68,
                        leading: const CircleAvatar(
                          backgroundColor: AppColors.primaryLight,
                          child: Icon(
                            Icons.monitor_heart_outlined,
                            color: AppColors.primary,
                          ),
                        ),
                        title: Text(
                          episode.symptoms
                              .map((item) => item.symptomName)
                              .join(', '),
                          style: AppTextStyles.body1Bold,
                        ),
                        subtitle: Text(
                          'เริ่ม ${formatThaiDateTime(episode.startedAt.toLocal())}',
                        ),
                        trailing: const Icon(Icons.arrow_forward_rounded),
                        onTap: () => Navigator.pop(sheetContext, episode.id),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                AppButton(
                  label: 'แยกเป็นรายการติดตามใหม่',
                  outlined: true,
                  icon: const Icon(Icons.add_rounded),
                  onTap: () => Navigator.pop(sheetContext),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _endTracking() async {
    if (_episode == null) return;
    const reasons = <String, String>{
      'recovered': 'อาการหายแล้ว',
      'improved': 'อาการดีขึ้นและไม่ต้องการติดตามต่อ',
      'consulted_provider': 'พบบุคลากรทางการแพทย์แล้ว',
      'stopped_by_user': 'ต้องการหยุดบันทึก',
      'other': 'เหตุผลอื่น',
    };
    final reason = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (sheetContext) => DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.62,
        minChildSize: 0.4,
        maxChildSize: 0.9,
        builder: (_, controller) => SafeArea(
          child: AppContentWidth(
            child: ListView(
              controller: controller,
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.outlineVariant,
                      borderRadius: BorderRadius.circular(99),
                    ),
                  ),
                ),
                const SizedBox(height: 20),
                Text('เหตุผลที่สิ้นสุดการติดตาม', style: AppTextStyles.h3),
                const SizedBox(height: 10),
                ...reasons.entries.map(
                  (item) => Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Material(
                      color: Theme.of(context).colorScheme.surface,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                        side: BorderSide(
                          color: Theme.of(context).colorScheme.outlineVariant,
                        ),
                      ),
                      clipBehavior: Clip.antiAlias,
                      child: ListTile(
                        minTileHeight: 56,
                        title: Text(item.value, style: AppTextStyles.body1),
                        trailing: const Icon(Icons.chevron_right_rounded),
                        onTap: () => Navigator.pop(sheetContext, item.key),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
    if (reason == null || !mounted) return;
    await context.read<PersonalHealthRepository>().updateHealthEpisodeStatus(
      _episode!.id,
      status: 'E',
      endReason: reason,
    );
    await LocalNotificationService.instance.showActivity(
      title: 'สิ้นสุดการติดตามอาการแล้ว',
      body: 'แตะเพื่อดูรายละเอียดการติดตามอาการ',
      payload: 'health_episode:${_episode!.id}',
    );
    if (mounted) Navigator.pop(context, true);
  }

  Future<void> _setTrackingStatus(String status) async {
    if (_episode == null) return;
    try {
      final episode = await context
          .read<PersonalHealthRepository>()
          .updateHealthEpisodeStatus(_episode!.id, status: status);
      if (!mounted) return;
      setState(() => _replaceEpisode(episode));
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            status == 'P'
                ? 'หยุดการติดตามชั่วคราวแล้ว'
                : 'กลับมาติดตามอาการแล้ว',
          ),
        ),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('ไม่สามารถเปลี่ยนสถานะได้')));
    }
  }

  void _replaceEpisode(HealthEpisodeModel episode) {
    _episode = episode;
    for (final symptom in episode.symptoms) {
      _drafts.putIfAbsent(symptom.id, () {
        final today = _targetDate;
        final entriesToday = symptom.entries.where((entry) {
          final date = entry.recordedAt.toLocal();
          return date.year == today.year &&
              date.month == today.month &&
              date.day == today.day;
        }).toList()..sort((a, b) => b.recordedAt.compareTo(a.recordedAt));
        return _SymptomDraft(
          symptom.questions,
          initialEntry: entriesToday.isEmpty ? null : entriesToday.first,
        );
      });
    }
    if (_expandedSymptomIds.isEmpty && episode.symptoms.length == 1) {
      final primary = episode.symptoms.where((item) => item.isPrimary);
      _expandedSymptomIds.add(
        primary.isEmpty ? episode.symptoms.first.id : primary.first.id,
      );
    }
  }

  Future<void> _addSymptom() async {
    if (_episode == null) return;
    final symptoms = await context.read<SymptomRepository>().getSymptoms(
      status: '1',
      sort: 'popular',
    );
    if (!mounted) return;
    final existing = _episode!.symptoms
        .map((item) => item.symptomId)
        .whereType<String>()
        .toSet();
    final selected = await Navigator.push<List<_SymptomChoice>>(
      context,
      MaterialPageRoute(
        fullscreenDialog: true,
        builder: (_) => _SymptomPicker(
          symptoms: symptoms
              .where((item) => !existing.contains(item.symptomId))
              .toList(),
        ),
      ),
    );
    if (selected == null || selected.isEmpty || !mounted) return;
    try {
      final repository = context.read<PersonalHealthRepository>();
      for (final item in selected) {
        await repository.addEpisodeSymptom(
          _episode!.id,
          symptomId: item.symptomId,
          customSymptomText: item.customText,
        );
      }
      final refreshed = await repository.healthEpisode(_episode!.id);
      if (!mounted) return;
      setState(() => _replaceEpisode(refreshed));
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('เพิ่มอาการไม่สำเร็จ หรืออาการนี้อยู่ในการติดตามแล้ว'),
        ),
      );
    }
  }

  Future<void> _save() async {
    if (_episode == null || _saving) return;
    FocusManager.instance.primaryFocus?.unfocus();
    for (final draft in _drafts.values) {
      if (!draft.hasRequiredAnswers) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('กรุณาตอบคำถามที่มีเครื่องหมาย * ให้ครบ'),
          ),
        );
        return;
      }
    }
    // Capture rule matches before awaiting API calls so the exact answers the
    // user is submitting drive the follow-up dialogs.
    final responseActions = _matchedResponseActions;
    final responseAlerts = _matchedResponseAlerts;
    final suggestedAnswerEndReason = _suggestedEndReason;
    setState(() => _saving = true);
    try {
      final repository = context.read<PersonalHealthRepository>();
      for (final symptom in _activeSymptoms) {
        final draft = _drafts[symptom.id]!;
        if (draft.initialEntryId != null) {
          await repository.updateEpisodeFollowUp(
            draft.initialEntryId,
            severity: draft.severity,
            note: draft.note.text,
            answers: draft.serializedAnswers,
          );
        } else {
          await repository.addEpisodeFollowUp(
            symptom.id,
            severity: draft.severity,
            note: draft.note.text,
            recordedAt: _isToday ? null : _recordedAt,
            answers: draft.serializedAnswers,
          );
        }
      }
      if (!mounted) return;
      if (responseAlerts.isNotEmpty) {
        await _showResponseAlerts(responseAlerts);
        if (!mounted) return;
      }
      if (responseActions.contains('prompt_add_symptom')) {
        final shouldAdd = await _askWhetherToAddSymptom();
        if (!mounted) return;
        if (shouldAdd == true) await _addSymptom();
        if (!mounted) return;
      }
      final suggestedEndReason = suggestedAnswerEndReason ??
          (responseActions.contains('prompt_end_tracking')
              ? 'stopped_by_user'
              : null);
      if (suggestedEndReason != null) {
        final shouldEnd = await _askWhetherToEndTracking(suggestedEndReason);
        if (!mounted) return;
        if (shouldEnd == true) {
          try {
            await repository.updateHealthEpisodeStatus(
              _episode!.id,
              status: 'E',
              endReason: suggestedEndReason,
            );
          } catch (_) {
            if (!mounted) return;
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text(
                  'บันทึกข้อมูลแล้ว แต่ยังสิ้นสุดการติดตามไม่สำเร็จ กรุณาลองอีกครั้ง',
                ),
              ),
            );
            return;
          }
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('บันทึกและสิ้นสุดการติดตามแล้ว')),
          );
          await LocalNotificationService.instance.showActivity(
            title: 'สิ้นสุดการติดตามอาการแล้ว',
            body: 'แตะเพื่อดูรายละเอียดการติดตามอาการ',
            payload: 'health_episode:${_episode!.id}',
          );
          Navigator.pop(context, true);
          return;
        }
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _hasEntriesToday
                ? 'อัปเดตการติดตาม${_isToday ? 'วันนี้' : 'ย้อนหลัง'}แล้ว'
                : 'บันทึกการติดตามทุกอาการแล้ว',
          ),
        ),
      );
      Navigator.pop(context, true);
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('บันทึกไม่สำเร็จ กรุณาลองอีกครั้ง')),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  String? get _suggestedEndReason {
    if (_episode == null) return null;
    final primarySymptoms = _episode!.symptoms.where(
      (item) => item.status == 'A' && item.isPrimary,
    );
    if (primarySymptoms.isEmpty) return null;
    final symptom = primarySymptoms.first;
    final draft = _drafts[symptom.id];
    if (draft == null) return null;

    for (final question in symptom.questions) {
      final answer = draft.answers[question.id]?.toString().trim();
      if (answer == 'หายแล้ว') return 'recovered';
    }
    for (final question in symptom.questions) {
      final answer = draft.answers[question.id]?.toString().trim();
      if (answer == 'ดีขึ้น') return 'improved';
    }
    return null;
  }

  Set<String> get _matchedResponseActions {
    final actions = <String>{};
    for (final symptom in _activeSymptoms) {
      final draft = _drafts[symptom.id];
      if (draft == null) continue;
      for (final question in symptom.questions) {
        final answer = _draftAnswer(draft, question);
        for (final rule in question.responseRules) {
          if (_matchesResponseRule(answer, rule)) actions.add(rule.action);
        }
      }
    }
    return actions;
  }

  List<_MatchedResponseAlert> get _matchedResponseAlerts {
    final alerts = <_MatchedResponseAlert>[];
    for (final symptom in _activeSymptoms) {
      final draft = _drafts[symptom.id];
      if (draft == null) continue;
      for (final question in symptom.questions) {
        final answer = _draftAnswer(draft, question);
        for (final rule in question.responseRules) {
          final message = rule.message?.trim();
          if (rule.action == 'show_alert' &&
              message?.isNotEmpty == true &&
              _matchesResponseRule(answer, rule) &&
              !alerts.any((item) => item.message == message)) {
            alerts.add(_MatchedResponseAlert(
              level: rule.alertLevel,
              title: rule.title?.trim(),
              message: message!,
              requiresAcknowledgement:
                  rule.requiresAcknowledgement || rule.alertLevel == 'important',
            ));
          }
        }
      }
    }
    return alerts;
  }

  dynamic _draftAnswer(
    _SymptomDraft draft,
    FollowUpQuestionModel question,
  ) {
    final controller = draft.questionControllers[question.id];
    if (controller != null) {
      final enteredText = controller.text.trim();
      if (enteredText.isEmpty) return null;
      return question.answerType == 'number'
          ? double.tryParse(enteredText)
          : enteredText;
    }
    return draft.answers[question.id];
  }

  Future<void> _showResponseAlerts(List<_MatchedResponseAlert> alerts) async {
    for (final alert in alerts) {
      if (!mounted) return;
      final important = alert.level == 'important';
      final informational = alert.level == 'info';
      await showDialog<void>(
        context: context,
        barrierDismissible: !alert.requiresAcknowledgement,
        builder: (dialogContext) => AppActionDialog(
          icon: important
              ? Icons.error_outline_rounded
              : informational
              ? Icons.info_outline_rounded
              : Icons.warning_amber_rounded,
          iconColor: important
              ? AppColors.danger
              : informational
              ? AppColors.primary
              : AppColors.warning,
          iconBackgroundColor: important
              ? AppColors.surfaceDanger
              : informational
              ? AppColors.primaryLight
              : AppColors.warning.withValues(alpha: 0.14),
          title: alert.title?.isNotEmpty == true
              ? alert.title!
              : important
              ? 'ข้อความสำคัญ'
              : informational
              ? 'ข้อมูลทั่วไป'
              : 'ควรสังเกต',
          message: alert.message,
          primaryLabel: alert.requiresAcknowledgement ? 'รับทราบ' : 'ปิด',
          onPrimary: () => Navigator.pop(dialogContext),
        ),
      );
    }
  }

  bool _matchesResponseRule(dynamic answer, FollowUpResponseRuleModel rule) {
    final text = answer?.toString() ?? '';
    final expected = rule.value?.toString() ?? '';
    final answerNumber = answer is num ? answer.toDouble() : double.tryParse(text);
    final expectedNumber = rule.value is num
        ? (rule.value as num).toDouble()
        : double.tryParse(expected);
    final expectedToNumber = rule.valueTo is num
        ? (rule.valueTo as num).toDouble()
        : double.tryParse(rule.valueTo?.toString() ?? '');

    switch (rule.operator) {
      case 'not_equals':
        if (answerNumber != null && expectedNumber != null) {
          return answerNumber != expectedNumber;
        }
        return answer != rule.value && text != expected;
      case 'greater_than':
        return answerNumber != null && expectedNumber != null
            ? answerNumber > expectedNumber
            : expected.isNotEmpty && text.compareTo(expected) > 0;
      case 'greater_than_or_equal':
        return answerNumber != null && expectedNumber != null
            ? answerNumber >= expectedNumber
            : expected.isNotEmpty && text.compareTo(expected) >= 0;
      case 'less_than':
        return answerNumber != null && expectedNumber != null
            ? answerNumber < expectedNumber
            : expected.isNotEmpty && text.compareTo(expected) < 0;
      case 'less_than_or_equal':
        return answerNumber != null && expectedNumber != null
            ? answerNumber <= expectedNumber
            : expected.isNotEmpty && text.compareTo(expected) <= 0;
      case 'between':
        if (answerNumber != null && expectedNumber != null && expectedToNumber != null) {
          return answerNumber >= expectedNumber && answerNumber <= expectedToNumber;
        }
        final expectedTo = rule.valueTo?.toString() ?? '';
        return expected.isNotEmpty && expectedTo.isNotEmpty &&
            text.compareTo(expected) >= 0 && text.compareTo(expectedTo) <= 0;
      case 'contains':
        return answer is List
            ? answer.any((item) => item.toString() == expected)
            : text.toLowerCase().contains(expected.toLowerCase());
      case 'not_contains':
        return answer is List
            ? answer.every((item) => item.toString() != expected)
            : !text.toLowerCase().contains(expected.toLowerCase());
      case 'is_empty':
        return answer == null || text.trim().isEmpty || (answer is List && answer.isEmpty);
      case 'is_not_empty':
        return answer != null && text.trim().isNotEmpty && (answer is! List || answer.isNotEmpty);
      case 'equals':
      default:
        if (answerNumber != null && expectedNumber != null) {
          return answerNumber == expectedNumber;
        }
        return answer is List
            ? answer.any((item) => item == rule.value || item.toString() == expected)
            : answer == rule.value || text == expected;
    }
  }

  Future<bool?> _askWhetherToAddSymptom() => showDialog<bool>(
    context: context,
    builder: (dialogContext) => AppActionDialog(
      icon: Icons.add_circle_outline_rounded,
      title: 'มีอาการใหม่หรือไม่?',
      message: 'บันทึกข้อมูลวันนี้แล้ว คุณต้องการเพิ่มอาการใหม่เข้าสู่การติดตามนี้หรือไม่?',
      primaryLabel: 'เพิ่มอาการ',
      onPrimary: () => Navigator.pop(dialogContext, true),
      secondaryLabel: 'ยังไม่เพิ่ม',
      onSecondary: () => Navigator.pop(dialogContext, false),
    ),
  );

  Future<bool?> _askWhetherToEndTracking(String reason) => showDialog<bool>(
    context: context,
    barrierDismissible: false,
    builder: (dialogContext) => AppActionDialog(
      icon: reason == 'recovered'
          ? Icons.check_circle_outline_rounded
          : Icons.trending_up_rounded,
      iconColor: AppColors.success,
      iconBackgroundColor: AppColors.success.withValues(alpha: 0.14),
      title: reason == 'recovered'
          ? 'อาการหายแล้ว'
          : reason == 'improved'
          ? 'อาการดีขึ้น'
          : 'ติดตามอาการต่อหรือไม่?',
      message:
          'บันทึกข้อมูลวันนี้เรียบร้อยแล้ว คุณต้องการติดตามอาการนี้ต่อหรือสิ้นสุดการติดตาม?',
      primaryLabel: 'สิ้นสุดการติดตาม',
      onPrimary: () => Navigator.pop(dialogContext, true),
      secondaryLabel: 'ติดตามต่อ',
      onSecondary: () => Navigator.pop(dialogContext, false),
    ),
  );

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: Theme.of(context).scaffoldBackgroundColor,
    appBar: AppBar(
      title: Text('ติดตามอาการ', style: AppTextStyles.h4),
      centerTitle: true,
      actions: [
        if (_episode != null && _episode!.status != 'E')
          PopupMenuButton<String>(
            onSelected: (value) {
              if (value == 'end') _endTracking();
              if (value == 'pause') _setTrackingStatus('P');
              if (value == 'resume') _setTrackingStatus('A');
            },
            itemBuilder: (_) => [
              PopupMenuItem(
                value: _episode!.status == 'P' ? 'resume' : 'pause',
                child: Text(
                  _episode!.status == 'P'
                      ? 'กลับมาติดตามต่อ'
                      : 'หยุดติดตามชั่วคราว',
                ),
              ),
              const PopupMenuItem(
                value: 'end',
                child: Text('สิ้นสุดการติดตาม'),
              ),
            ],
          ),
      ],
      bottom: PreferredSize(
        preferredSize: Size.fromHeight(1),
        child: Divider(
          height: 1,
          color: Theme.of(context).colorScheme.outlineVariant,
        ),
      ),
    ),
    body: _loading
        ? const AppLoadingView(label: 'กำลังโหลดข้อมูลติดตามอาการ...')
        : _error != null
        ? AppMessageView.error(message: _error!, onAction: _load)
        : _body(),
    bottomNavigationBar: _loading || _error != null || _episode?.status != 'A'
        ? null
        : SafeArea(
            minimum: const EdgeInsets.fromLTRB(20, 10, 20, 12),
            child: AppContentWidth(
              shrinkWrapHeight: true,
              child: AppButton(
                label: _saving
                    ? 'กำลังบันทึก...'
                    : _hasEntriesToday
                    ? 'อัปเดตการติดตาม${_isToday ? 'วันนี้' : 'ย้อนหลัง'}'
                    : 'บันทึกการติดตาม${_isToday ? 'วันนี้' : 'ย้อนหลัง'}',
                loading: _saving,
                onTap: _save,
              ),
            ),
          ),
  );

  Widget _body() => SafeArea(
    child: AppContentWidth(
      child: ListView(
        keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 28),
        children: [
          _trackingHeader(),
          if (_episode!.status == 'E') ...[
            const SizedBox(height: 12),
            _endedSummary(),
            const SizedBox(height: 20),
            _timelineSection(),
          ] else ...[
            const SizedBox(height: 12),
            _trackingGuide(),
            const SizedBox(height: 18),
            Text(
              'อาการที่ติดตาม ${_activeSymptoms.length} รายการ',
              style: AppTextStyles.h3,
            ),
            const SizedBox(height: 4),
            Text(
              _activeSymptoms.length > 1
                  ? 'แตะอาการแต่ละรายการเพื่อกรอกข้อมูลติดตาม'
                  : 'แตะรายการเพื่อกรอกข้อมูลติดตาม',
              style: AppTextStyles.body2.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
            const SizedBox(height: 12),
            ..._activeSymptoms.where((item) => item.isPrimary).map(_symptomCard),
            if (_activeSymptoms.any((item) => !item.isPrimary)) ...[
              ..._activeSymptoms
                  .where((item) => !item.isPrimary)
                  .map(_symptomCard),
            ],
            OutlinedButton.icon(
              onPressed: _addSymptom,
              icon: const Icon(Icons.add_circle_outline_rounded),
              label: const Text('เพิ่มอาการร่วม'),
            ),
            const SizedBox(height: 20),
            _questionsSection(),
            if (_hasTrackingEntries) ...[
              const SizedBox(height: 24),
              _timelineSection(),
            ],
            const SizedBox(height: 20),
            // _linkedHealthRecordCard(),
          ],
        ],
      ),
    ),
  );

  List<EpisodeSymptomModel> get _timelineSymptoms =>
      _episode!.status == 'E' ? _episode!.symptoms : _activeSymptoms;

  bool get _hasTrackingEntries =>
      _timelineSymptoms.any((symptom) => symptom.entries.isNotEmpty);

  List<EpisodeSymptomModel> get _activeSymptoms =>
      _episode!.symptoms.where((item) {
        if (item.status != 'A') return false;
        // The primary symptom must remain editable when this screen is opened
        // from an older daily record. Otherwise every input disappears when
        // the episode was created after the selected record date.
        if (item.isPrimary) return true;
        final firstObserved = item.firstObservedAt?.toLocal();
        if (firstObserved == null) return true;
        final firstDate = DateTime(
          firstObserved.year,
          firstObserved.month,
          firstObserved.day,
        );
        final targetDate = DateTime(
          _targetDate.year,
          _targetDate.month,
          _targetDate.day,
        );
        return !firstDate.isAfter(targetDate);
      }).toList()
        ..sort((a, b) {
          if (a.isPrimary == b.isPrimary) return 0;
          return a.isPrimary ? -1 : 1;
        });

  EpisodeSymptomModel? get _primarySymptom {
    final symptoms = _episode?.symptoms ?? const <EpisodeSymptomModel>[];
    if (symptoms.isEmpty) return null;
    final primary = symptoms.where((item) => item.isPrimary);
    return primary.isEmpty ? symptoms.first : primary.first;
  }

  int get _trackingDay {
    final start = _episode!.startedAt.toLocal();
    final end = (_episode!.endedAt ?? _targetDate).toLocal();
    final days = DateTime(
          end.year,
          end.month,
          end.day,
        ).difference(DateTime(start.year, start.month, start.day)).inDays +
        1;
    return days < 1 ? 1 : days;
  }

  Widget _trackingGuide() => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(16),
      border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
    ),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Icon(Icons.info_outline_rounded, color: AppColors.primary),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            _isToday
                ? 'วันนี้เป็นวันที่ $_trackingDay ของการติดตาม ไม่มีการกำหนดจำนวนวัน '
                      'คุณบันทึกต่อได้ตามต้องการ และเลือกพักหรือสิ้นสุดได้ทุกเมื่อจากเมนูมุมขวาบน'
                : 'กำลังบันทึกการติดตามย้อนหลังสำหรับวันที่ '
                      '${formatThaiDate(_targetDate)}',
            style: AppTextStyles.body2.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
              height: 1.5,
            ),
          ),
        ),
      ],
    ),
  );

  bool get _hasEntriesToday =>
      _drafts.values.any((draft) => draft.initialEntryId != null);

  Widget _trackingHeader() => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(20),
    decoration: BoxDecoration(
      color: AppColors.primary,
      borderRadius: BorderRadius.circular(16),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: Theme.of(
                  context,
                ).colorScheme.surface.withValues(alpha: 0.16),
                borderRadius: BorderRadius.circular(15),
              ),
              child: Center(
                child: SymptomIcon(
                  iconName: _primarySymptom?.symptomIcon,
                  size: 27,
                  color: AppColors.white,
                ),
              ),
            ),
            const SizedBox(width: 13),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _primarySymptom?.symptomName ?? widget.symptomName,
                    style: AppTextStyles.h4.copyWith(color: AppColors.white),
                  ),
                  Text(
                    'บันทึกการเปลี่ยนแปลงของอาการ',
                    style: AppTextStyles.body2.copyWith(
                      color: AppColors.white.withValues(alpha: 0.86),
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    'เริ่มติดตาม ${formatThaiDateTime(_episode!.startedAt.toLocal())}',
                    style: AppTextStyles.body3.copyWith(
                      color: AppColors.white.withValues(alpha: 0.78),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        // const SizedBox(height: 18),
        // const Row(
        //   children: [
        //     Expanded(
        //       child: _TrackingStep(number: '1', label: 'ระดับอาการ'),
        //     ),
        //     SizedBox(width: 8),
        //     Expanded(
        //       child: _TrackingStep(number: '2', label: 'ข้อมูลร่วม'),
        //     ),
        //     SizedBox(width: 8),
        //     Expanded(
        //       child: _TrackingStep(number: '3', label: 'คำถามติดตาม'),
        //     ),
        //   ],
        // ),
      ],
    ),
  );

  Widget _endedSummary() {
    const reasons = <String, String>{
      'recovered': 'อาการหายแล้ว',
      'improved': 'อาการดีขึ้น',
      'consulted_provider': 'พบบุคลากรทางการแพทย์แล้ว',
      'stopped_by_user': 'ผู้ใช้สิ้นสุดการติดตาม',
      'other': 'เหตุผลอื่น',
    };
    final entries = _episode!.symptoms.expand((item) => item.entries).length;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.check_circle_outline_rounded, color: AppColors.success),
              const SizedBox(width: 8),
              Text('สรุปการติดตาม', style: AppTextStyles.body1Bold),
            ],
          ),
          const SizedBox(height: 12),
          _summaryRow('ระยะเวลา', '$_trackingDay วัน'),
          _summaryRow('จำนวนบันทึก', '$entries ครั้ง'),
          if (_episode!.endedAt != null)
            _summaryRow('สิ้นสุดเมื่อ', formatThaiDateTime(_episode!.endedAt!.toLocal())),
          _summaryRow(
            'เหตุผล',
            reasons[_episode!.endReason] ?? _episode!.endReason ?? 'ไม่ได้ระบุ',
          ),
          if (_episode!.endNote?.trim().isNotEmpty == true)
            _summaryRow('หมายเหตุ', _episode!.endNote!.trim()),
        ],
      ),
    );
  }

  Widget _summaryRow(String label, String value) => Padding(
    padding: const EdgeInsets.only(bottom: 7),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 100,
          child: Text(
            label,
            style: AppTextStyles.body3.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
        ),
        Expanded(child: Text(value, style: AppTextStyles.body2Bold)),
      ],
    ),
  );

  Widget _trendSection() {
    final series = _numericTrendSeries();
    final categoricalSeries = _categoricalTrendSeries();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('กราฟแนวโน้ม', style: AppTextStyles.h3),
        const SizedBox(height: 4),
        Text(
          'แสดงแยกตามอาการและข้อมูลตัวเลขที่บันทึกไว้',
          style: AppTextStyles.body3.copyWith(
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
        const SizedBox(height: 12),
        if (series.isEmpty && categoricalSeries.isEmpty)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
            ),
            child: Text(
              'ยังไม่มีข้อมูลตัวเลขสำหรับสร้างกราฟ',
              textAlign: TextAlign.center,
              style: AppTextStyles.body2.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
          )
        else
          ...[
            ...series.map(_trendCard),
            ...categoricalSeries.map(_categoricalTrendCard),
          ],
      ],
    );
  }

  List<_CategoricalTrendSeries> _categoricalTrendSeries() {
    final result = <_CategoricalTrendSeries>[];
    for (final symptom in _episode!.symptoms) {
      final questions = symptom.questions.where(
        (question) =>
            question.answerType != 'number' &&
            question.answerType != 'scale' &&
            question.answerType != 'text' &&
            question.answerType != 'date' &&
            question.answerType != 'time',
      );
      for (final question in questions) {
        final points = symptom.entries
            .where((entry) => entry.answers[question.id] != null)
            .map(
              (entry) => _CategoricalTrendPoint(
                entry.recordedAt,
                _categoricalAnswerLabel(
                  question,
                  entry.answers[question.id],
                ),
              ),
            )
            .where((point) => point.value.trim().isNotEmpty)
            .toList()
          ..sort((a, b) => a.date.compareTo(b.date));
        if (points.isNotEmpty) {
          result.add(
            _CategoricalTrendSeries(
              '${symptom.symptomName} · ${question.questionText}',
              points,
            ),
          );
        }
      }
    }
    return result;
  }

  String _categoricalAnswerLabel(
    FollowUpQuestionModel question,
    dynamic value,
  ) {
    if (question.answerType == 'boolean') {
      final normalized = value.toString().trim().toLowerCase();
      final isTrue = value == true || normalized == 'true' || normalized == '1';
      if (isTrue) {
        return question.options.isNotEmpty ? question.options.first : 'ใช่';
      }
      return question.options.length > 1 ? question.options[1] : 'ไม่ใช่';
    }
    if (value is List) return value.join(', ');
    return value.toString();
  }

  Widget _categoricalTrendCard(_CategoricalTrendSeries series) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(series.title, style: AppTextStyles.body1Bold),
          const SizedBox(height: 4),
          Text(
            '${series.points.length} ครั้ง · แสดงตามวันที่บันทึก',
            style: AppTextStyles.body3.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: 16),
          LayoutBuilder(
            builder: (context, constraints) {
              final contentWidth = series.points.length * 120.0;
              return SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: SizedBox(
                  width: contentWidth < constraints.maxWidth
                      ? constraints.maxWidth
                      : contentWidth,
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: series.points
                        .map(
                          (point) => Expanded(
                            child: Column(
                      children: [
                        Container(
                          width: 14,
                          height: 14,
                          decoration: const BoxDecoration(
                            color: AppColors.primary,
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          point.value,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          textAlign: TextAlign.center,
                          style: AppTextStyles.body3Bold.copyWith(
                            color: AppColors.primary,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          formatShortThaiDate(point.date.toLocal()),
                          textAlign: TextAlign.center,
                          style: AppTextStyles.body3.copyWith(
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurfaceVariant,
                          ),
                        ),
                      ],
                            ),
                          ),
                        )
                        .toList(),
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  List<_FollowUpTrendSeries> _numericTrendSeries() {
    final result = <_FollowUpTrendSeries>[];
    for (final symptom in _episode!.symptoms) {
      final entryDates = symptom.entries.map((entry) => entry.recordedAt).toList()
        ..sort((a, b) => a.compareTo(b));
      final rangeStart = entryDates.isEmpty ? null : entryDates.first;
      final rangeEnd = entryDates.isEmpty ? null : entryDates.last;
      final numericQuestions = symptom.questions.where(
        (item) => item.answerType == 'number' || item.answerType == 'scale',
      ).toList();
      if (numericQuestions.isEmpty) {
        final severityPoints = symptom.entries
            .where((entry) => entry.severity != null)
            .map((entry) => _FollowUpTrendPoint(entry.recordedAt, entry.severity!.toDouble()))
            .toList()
          ..sort((a, b) => a.date.compareTo(b.date));
        if (severityPoints.isNotEmpty) {
          result.add(_FollowUpTrendSeries(
            '${symptom.symptomName} · ระดับอาการ',
            severityPoints,
            '/10',
            rangeStart: rangeStart,
            rangeEnd: rangeEnd,
          ));
        }
      }
      for (final question in numericQuestions) {
        final points = symptom.entries
            .where((entry) => entry.answers[question.id] != null)
            .map((entry) {
              final value = double.tryParse(entry.answers[question.id].toString());
              return value == null ? null : _FollowUpTrendPoint(entry.recordedAt, value);
            })
            .whereType<_FollowUpTrendPoint>()
            .toList()
          ..sort((a, b) => a.date.compareTo(b.date));
        if (points.isNotEmpty) {
          result.add(_FollowUpTrendSeries(
            '${symptom.symptomName} · ${question.questionText}',
            points,
            question.unit ?? '',
            rangeStart: rangeStart,
            rangeEnd: rangeEnd,
          ));
        }
      }
    }
    return result;
  }

  Widget _trendCard(_FollowUpTrendSeries series) {
    final first = series.points.first;
    final last = series.points.last;
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(series.title, style: AppTextStyles.body1Bold),
          const SizedBox(height: 4),
          Text(
            series.points.length == 1
                ? '${_formatTrendValue(first.value)}${series.unit} · 1 ครั้ง'
                : '${_formatTrendValue(first.value)}${series.unit} → ${_formatTrendValue(last.value)}${series.unit} · ${series.points.length} ครั้ง',
            style: AppTextStyles.body3.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: 14),
          SizedBox(
            height: 130,
            width: double.infinity,
            child: CustomPaint(
              painter: _FollowUpLineChartPainter(
                values: series.points.map((item) => item.value).toList(),
                dates: series.points.map((item) => item.date).toList(),
                rangeStart: series.rangeStart,
                rangeEnd: series.rangeEnd,
                lineColor: AppColors.primary,
                gridColor: Theme.of(context).colorScheme.outlineVariant,
              ),
            ),
          ),
          const SizedBox(height: 8),
          if (series.rangeStart.isAtSameMomentAs(series.rangeEnd))
            Center(
              child: Text(
                formatThaiDate(series.rangeStart.toLocal()),
                style: AppTextStyles.body3,
              ),
            )
          else
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  formatThaiDate(series.rangeStart.toLocal()),
                  style: AppTextStyles.body3,
                ),
                Text(
                  formatThaiDate(series.rangeEnd.toLocal()),
                  style: AppTextStyles.body3,
                ),
              ],
            ),
        ],
      ),
    );
  }

  String _formatTrendValue(double value) =>
      value == value.roundToDouble() ? value.toInt().toString() : value.toStringAsFixed(1);

  Widget _timelineSection() {
    final hasEntries = _timelineSymptoms.any((item) => item.entries.isNotEmpty);
    final grouped =
        <
          String,
          List<({EpisodeSymptomModel symptom, FollowUpEntryModel entry})>
        >{};
    for (final symptom in _timelineSymptoms) {
      for (final entry in symptom.entries) {
        final local = entry.recordedAt.toLocal();
        final key =
            '${local.year.toString().padLeft(4, '0')}-'
            '${local.month.toString().padLeft(2, '0')}-'
            '${local.day.toString().padLeft(2, '0')}';
        grouped.putIfAbsent(key, () => []).add((
          symptom: symptom,
          entry: entry,
        ));
      }
    }
    final days = grouped.keys.toList()..sort((a, b) => b.compareTo(a));
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      clipBehavior: Clip.antiAlias,
      child: ExpansionTile(
        initiallyExpanded: _episode!.status == 'E',
        shape: const Border(),
        collapsedShape: const Border(),
        leading: CircleAvatar(
          backgroundColor: Theme.of(context).colorScheme.surfaceContainerLow,
          child: Icon(Icons.timeline_rounded, color: AppColors.primary),
        ),
        title: Text('ไทม์ไลน์การติดตาม', style: AppTextStyles.body1Bold),
        subtitle: Text(
          hasEntries ? 'กดเพื่อดูประวัติรายวัน' : 'ยังไม่มีบันทึกการติดตาม',
          style: AppTextStyles.body3.copyWith(
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
        tilePadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 8),
        childrenPadding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
        children: hasEntries
            ? days.map((day) {
                final items = grouped[day]!
                  ..sort(
                    (a, b) => b.entry.recordedAt.compareTo(a.entry.recordedAt),
                  );
                final date = items.first.entry.recordedAt.toLocal();
                return Container(
                  margin: const EdgeInsets.only(top: 10),
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Theme.of(context).scaffoldBackgroundColor,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: Theme.of(context).colorScheme.outlineVariant,
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Icon(
                            Icons.calendar_today_rounded,
                            color: AppColors.primary,
                            size: 20,
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              formatThaiDate(date),
                              style: AppTextStyles.body1Bold,
                            ),
                          ),
                          Text(
                            '${items.length} อาการ',
                            style: AppTextStyles.body3.copyWith(
                              color: Theme.of(
                                context,
                              ).colorScheme.onSurfaceVariant,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      ...items.map(
                        (item) => Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Container(
                                width: 32,
                                height: 32,
                                decoration: BoxDecoration(
                                  color: AppColors.primaryLight,
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: SymptomIcon(
                                  iconName: item.symptom.symptomIcon,
                                  color: AppColors.primary,
                                  size: 17,
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      item.symptom.symptomName,
                                      style: AppTextStyles.body2Bold,
                                    ),
                                    Text(
                                      formatThaiDateTime(
                                        item.entry.recordedAt.toLocal(),
                                      ),
                                      style: AppTextStyles.body3.copyWith(
                                        color: Theme.of(
                                          context,
                                        ).colorScheme.onSurfaceVariant,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                                  Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 10,
                                  vertical: 5,
                                ),
                                decoration: BoxDecoration(
                                  color: AppColors.primaryLight,
                                  borderRadius: BorderRadius.circular(999),
                                ),
                                child: Text(
                                  item.entry.severity == null
                                      ? 'บันทึกแล้ว'
                                      : '${item.entry.severity}/10',
                                  style: AppTextStyles.body2Bold.copyWith(
                                    color: AppColors.primary,
                                  ),
                                ),
                              ),
                                ],
                              ),
                              _timelineEntryDetails(item.symptom, item.entry),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                );
              }).toList()
            : const [],
      ),
    );
  }

  Widget _timelineEntryDetails(
    EpisodeSymptomModel symptom,
    FollowUpEntryModel entry,
  ) {
    // Keep the timeline in the same reading order as the form: symptom
    // details first, then the shared follow-up questions.
    final orderedQuestions = [
      ...symptom.questions.where((question) => !question.isGlobal),
      ...symptom.questions.where((question) => question.isGlobal),
    ];
    final answerRows = orderedQuestions
        .where((question) => entry.answers[question.id] != null)
        .map(
          (question) => (
            label: question.questionText,
            value: _timelineAnswerLabel(question, entry.answers[question.id]),
          ),
        )
        .where((row) => row.value.isNotEmpty)
        .toList();
    final note = entry.note?.trim();
    if (entry.temperature == null && note?.isEmpty != false && answerRows.isEmpty) {
      return const SizedBox.shrink();
    }

    final mutedColor = Theme.of(context).colorScheme.onSurfaceVariant;
    // 32 px symptom icon + 10 px gap: align details with the symptom label.
    return Padding(
      padding: const EdgeInsets.only(left: 42, top: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (entry.temperature != null)
            _timelineDetailRow(
              'อุณหภูมิร่างกาย',
              '${_formatTrendValue(entry.temperature!)} °C',
              mutedColor,
            ),
          ...answerRows.map(
            (row) => _timelineDetailRow(row.label, row.value, mutedColor),
          ),
          if (note?.isNotEmpty == true) ...[
            if (entry.temperature != null || answerRows.isNotEmpty)
              const SizedBox(height: 6),
            Text('บันทึกเพิ่มเติม', style: AppTextStyles.body3.copyWith(color: mutedColor)),
            const SizedBox(height: 2),
            Text(note!, style: AppTextStyles.body3),
          ],
        ],
      ),
    );
  }

  Widget _timelineDetailRow(String label, String value, Color mutedColor) => Padding(
    padding: const EdgeInsets.only(bottom: 5),
    child: RichText(
      text: TextSpan(
        style: AppTextStyles.body3.copyWith(color: mutedColor),
        children: [
          TextSpan(text: '$label: '),
          TextSpan(text: value, style: AppTextStyles.body3),
        ],
      ),
    ),
  );

  String _timelineAnswerLabel(FollowUpQuestionModel question, dynamic value) {
    final label = _categoricalAnswerLabel(question, value).trim();
    if (label.isEmpty) return '';
    if (question.unit?.trim().isNotEmpty == true &&
        (question.answerType == 'number' || question.answerType == 'scale')) {
      return '$label ${question.unit!.trim()}';
    }
    return label;
  }

  Widget _questionsSection() {
    final groups = _activeSymptoms
        .where((item) => item.questions.any((question) => question.isGlobal))
        .take(1)
        .toList();
    if (groups.isEmpty) return const SizedBox.shrink();
    var number = 0;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('คำถามติดตาม', style: AppTextStyles.h3),
        const SizedBox(height: 4),
        Text(
          'คำถามกลางจะถามเพียงครั้งเดียว ส่วนคำถามเฉพาะจะแยกตามอาการ',
          style: AppTextStyles.body2.copyWith(
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
        const SizedBox(height: 12),
        ...groups.expand((symptom) {
          final global = symptom.questions
              .where((item) => item.isGlobal)
              .toList();
          final widgets = <Widget>[];
          if (global.isNotEmpty) {
            widgets.addAll(
              global.map((question) {
                number++;
                return _questionField(_drafts[symptom.id]!, question, number);
              }),
            );
          }
          return widgets;
        }),
      ],
    );
  }

  Widget _linkedHealthRecordCard() => Card(
    margin: EdgeInsets.zero,
    clipBehavior: Clip.antiAlias,
    child: ListTile(
      contentPadding: const EdgeInsets.all(16),
      leading: CircleAvatar(
        backgroundColor: Theme.of(context).colorScheme.surfaceContainerLow,
        child: Icon(Icons.favorite_outline_rounded, color: AppColors.primary),
      ),
      title: Text('บันทึกสุขภาพประจำวัน', style: AppTextStyles.body1Bold),
      subtitle: Text(
        'บันทึกว่าวันนี้สบายดีหรือมีอาการ ข้อมูลจะแสดงร่วมกันในหน้าแนวโน้มสุขภาพ',
        style: AppTextStyles.body3.copyWith(
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
      ),
      trailing: const Icon(Icons.arrow_forward_rounded),
      onTap: () => Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => const DailyHealthRecordScreen()),
      ),
    ),
  );

  Widget _symptomCard(EpisodeSymptomModel symptom) {
    final draft = _drafts[symptom.id]!;
    final entries = [...symptom.entries]
      ..sort((a, b) => b.recordedAt.compareTo(a.recordedAt));
    final latestEntry = entries.isEmpty ? null : entries.first;
    final expanded = _expandedSymptomIds.contains(symptom.id);
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(18, 12, 18, 18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            InkWell(
              borderRadius: BorderRadius.circular(14),
              onTap: () => setState(() {
                expanded
                    ? _expandedSymptomIds.remove(symptom.id)
                    : _expandedSymptomIds.add(symptom.id);
              }),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            symptom.symptomName,
                            style: AppTextStyles.body1Bold,
                          ),
                          const SizedBox(height: 3),
                          Text(
                            latestEntry == null
                                ? 'ยังไม่มีบันทึกการติดตาม'
                                : 'ล่าสุด ${formatThaiDateTime(latestEntry.recordedAt.toLocal())}',
                            style: AppTextStyles.body3.copyWith(
                              color: Theme.of(
                                context,
                              ).colorScheme.onSurfaceVariant,
                            ),
                          ),
                        ],
                      ),
                    ),
                    Icon(
                      expanded
                          ? Icons.keyboard_arrow_up_rounded
                          : Icons.keyboard_arrow_down_rounded,
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                  ],
                ),
              ),
            ),
            if (expanded) ...[
              const SizedBox(height: 12),
              ...symptom.questions
                  .where((question) => !question.isGlobal)
                  .toList()
                  .asMap()
                  .entries
                  .map(
                    (item) => _questionField(draft, item.value, item.key + 1),
                  ),
              const SizedBox(height: 12),
              TextField(
                controller: draft.note,
                minLines: 2,
                maxLines: 4,
                maxLength: 500,
                decoration: const InputDecoration(
                  labelText: 'บันทึกเพิ่มเติม — ไม่บังคับ',
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _questionField(
    _SymptomDraft draft,
    FollowUpQuestionModel question,
    int questionNumber,
  ) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surfaceContainerLow,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 26,
                height: 26,
                alignment: Alignment.center,
                decoration: const BoxDecoration(
                  color: AppColors.primary,
                  shape: BoxShape.circle,
                ),
                child: Text(
                  '$questionNumber',
                  style: AppTextStyles.body3Bold.copyWith(
                    color: AppColors.white,
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text.rich(
                  TextSpan(
                    children: [
                      TextSpan(text: question.questionText),
                      if (question.isRequired)
                        const TextSpan(
                          text: ' *',
                          style: TextStyle(color: AppColors.danger),
                        ),
                    ],
                  ),
                  style: AppTextStyles.body2Bold,
                ),
              ),
            ],
          ),
          if (question.description?.trim().isNotEmpty == true) ...[
            const SizedBox(height: 3),
            Text(
              question.description!,
              style: AppTextStyles.body2.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
          ],
          const SizedBox(height: 8),
          if (question.answerType == 'boolean')
            Column(
              children: [
                _FollowUpChoiceTile(
                  label: question.options.isNotEmpty
                      ? question.options.first
                      : 'ใช่',
                  selected: draft.answers[question.id] == true,
                  onTap: () => setState(() {
                    draft.answers[question.id] = true;
                  }),
                ),
                _FollowUpChoiceTile(
                  label: question.options.length > 1
                      ? question.options[1]
                      : 'ไม่ใช่',
                  selected: draft.answers[question.id] == false,
                  onTap: () => setState(
                    () => draft.answers[question.id] = false,
                  ),
                ),
              ],
            )
          else if (question.answerType == 'single_choice')
            Column(
              children: question.options
                  .map(
                    (option) => _FollowUpChoiceTile(
                      label: option,
                      selected: draft.answers[question.id] == option,
                      onTap: () =>
                          setState(() => draft.answers[question.id] = option),
                    ),
                  )
                  .toList(),
            )
          else if (question.answerType == 'multiple_choice')
            Column(
              children: question.options.map((option) {
                final selected =
                    (draft.answers[question.id] as List<String>? ?? const [])
                        .contains(option);
                return _FollowUpChoiceTile(
                  label: option,
                  selected: selected,
                  multiple: true,
                  onTap: () => setState(() {
                    final values = List<String>.from(
                      draft.answers[question.id] as List<String>? ?? const [],
                    );
                    selected ? values.remove(option) : values.add(option);
                    draft.answers[question.id] = values;
                  }),
                );
              }).toList(),
            )
          else if (question.answerType == 'scale')
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  draft.answers[question.id]?.toString() ??
                      'ยังไม่ได้เลือกระดับ',
                  style: AppTextStyles.body2Bold,
                ),
                Slider(
                  value: (() {
                    final selected = question.options.indexOf(
                      draft.answers[question.id]?.toString() ?? '',
                    );
                    return (selected < 0 ? 0 : selected).toDouble();
                  })(),
                  min: 0,
                  max: (question.options.length - 1).toDouble(),
                  divisions: question.options.length - 1,
                  label:
                      draft.answers[question.id]?.toString() ??
                      question.options.first,
                  onChanged: (value) => setState(
                    () => draft.answers[question.id] =
                        question.options[value.round()],
                  ),
                ),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(question.options.first, style: AppTextStyles.body3),
                    Text(question.options.last, style: AppTextStyles.body3),
                  ],
                ),
              ],
            )
          else if (question.answerType == 'date' ||
              question.answerType == 'time')
            TextField(
              controller: draft.questionControllers[question.id],
              readOnly: true,
              decoration: InputDecoration(
                hintText: question.answerType == 'date'
                    ? 'เลือกวันที่'
                    : 'เลือกเวลา',
                suffixIcon: Icon(
                  question.answerType == 'date'
                      ? Icons.calendar_today_outlined
                      : Icons.schedule_outlined,
                ),
              ),
              onTap: () async {
                if (question.answerType == 'date') {
                  final value = await showDatePicker(
                    context: context,
                    calendarDelegate: const BuddhistCalendarDelegate(),
                    firstDate: DateTime(2000),
                    lastDate: DateTime.now(),
                    initialDate: DateTime.now(),
                  );
                  if (value != null) {
                    draft.questionControllers[question.id]!.text =
                        '${value.year.toString().padLeft(4, '0')}-${value.month.toString().padLeft(2, '0')}-${value.day.toString().padLeft(2, '0')}';
                  }
                } else {
                  final value = await showTimePicker(
                    context: context,
                    initialTime: TimeOfDay.now(),
                  );
                  if (value != null) {
                    draft.questionControllers[question.id]!.text =
                        '${value.hour.toString().padLeft(2, '0')}:${value.minute.toString().padLeft(2, '0')}';
                  }
                }
                if (mounted) setState(() {});
              },
            )
          else
            TextField(
              controller: draft.questionControllers[question.id],
              keyboardType: question.answerType == 'number'
                  ? const TextInputType.numberWithOptions(decimal: true)
                  : TextInputType.text,
              maxLines: question.answerType == 'text' ? 3 : 1,
              maxLength: question.answerType == 'text' ? 500 : null,
              decoration: InputDecoration(
                hintText: question.answerType == 'number'
                    ? 'กรอกตัวเลข${question.unit == null ? '' : ' (${question.unit})'}'
                    : 'กรอกคำตอบ',
              ),
            ),
        ],
      ),
    );
  }
}

class _FollowUpChoiceTile extends StatelessWidget {
  final String label;
  final bool selected;
  final bool multiple;
  final VoidCallback onTap;

  const _FollowUpChoiceTile({
    required this.label,
    required this.selected,
    required this.onTap,
    this.multiple = false,
  });

  @override
  Widget build(BuildContext context) {
    return Semantics(
      button: true,
      selected: selected,
      label: label,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 150),
          width: double.infinity,
          margin: const EdgeInsets.only(bottom: 8),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          decoration: BoxDecoration(
            color: selected
                ? AppColors.primaryLight
                : Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: selected
                  ? AppColors.primary
                  : Theme.of(context).colorScheme.outlineVariant,
              width: selected ? 1.5 : 1,
            ),
          ),
          child: Row(
            children: [
              Expanded(
                child: Text(
                  label,
                  style: AppTextStyles.body2Bold.copyWith(
                    color: selected
                        ? AppColors.primary
                        : Theme.of(context).colorScheme.onSurface,
                  ),
                ),
              ),
              AnimatedContainer(
                duration: const Duration(milliseconds: 150),
                width: 20,
                height: 20,
                decoration: BoxDecoration(
                  color: selected ? AppColors.primary : Colors.transparent,
                  shape: multiple ? BoxShape.rectangle : BoxShape.circle,
                  borderRadius: multiple ? BorderRadius.circular(5) : null,
                  border: Border.all(
                    color: selected
                        ? AppColors.primary
                        : Theme.of(context).colorScheme.outlineVariant,
                    width: 1.5,
                  ),
                ),
                child: selected
                    ? const Icon(Icons.check, size: 14, color: AppColors.white)
                    : null,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _TrackingStep extends StatelessWidget {
  final String number;
  final String label;

  const _TrackingStep({required this.number, required this.label});

  @override
  Widget build(BuildContext context) => Column(
    children: [
      Container(
        width: 30,
        height: 30,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface.withValues(alpha: 0.18),
          shape: BoxShape.circle,
          border: Border.all(color: AppColors.white.withValues(alpha: 0.4)),
        ),
        child: Text(
          number,
          style: AppTextStyles.body3Bold.copyWith(color: AppColors.white),
        ),
      ),
      const SizedBox(height: 5),
      Text(
        label,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: AppTextStyles.body3.copyWith(color: AppColors.white),
      ),
    ],
  );
}

class _SymptomDraft {
  final note = TextEditingController();
  final List<FollowUpQuestionModel> questions;
  final Map<int, dynamic> answers = {};
  final Map<int, TextEditingController> questionControllers = {};
  final dynamic initialEntryId;

  _SymptomDraft(this.questions, {FollowUpEntryModel? initialEntry})
    : initialEntryId = initialEntry?.id {
    if (initialEntry != null) {
      note.text = initialEntry.note ?? '';
      answers.addAll(initialEntry.answers);
    }
    for (final question in questions) {
      if (question.answerType == 'text' ||
          question.answerType == 'number' ||
          question.answerType == 'date' ||
          question.answerType == 'time') {
        final initialValue = initialEntry?.answers[question.id];
        questionControllers[question.id] = TextEditingController(
          text: initialValue?.toString() ?? '',
        );
      }
    }
  }

  bool get hasRequiredAnswers =>
      questions.where((item) => item.isRequired).every((question) {
        final controller = questionControllers[question.id];
        if (controller != null) return controller.text.trim().isNotEmpty;
        final answer = answers[question.id];
        return answer != null && (answer is! List || answer.isNotEmpty);
      });

  int? get severity {
    for (final question in questions) {
      if (question.answerType != 'scale' ||
          !question.questionText.contains('ความปวด')) {
        continue;
      }
      final value = answers[question.id]?.toString();
      return value == null ? null : int.tryParse(value);
    }
    return null;
  }

  List<Map<String, dynamic>> get serializedAnswers => questions
      .map((question) {
        final controller = questionControllers[question.id];
        dynamic value;
        if (controller != null) {
          final text = controller.text.trim();
          if (text.isEmpty) return null;
          value = question.answerType == 'number'
              ? double.tryParse(text)
              : text;
        } else {
          value = answers[question.id];
        }
        if (value == null || (value is List && value.isEmpty)) return null;
        return <String, dynamic>{
          'question_template_id': question.id,
          'value': value,
        };
      })
      .whereType<Map<String, dynamic>>()
      .toList();

  void dispose() {
    note.dispose();
    for (final controller in questionControllers.values) {
      controller.dispose();
    }
  }
}

class _SymptomPicker extends StatefulWidget {
  final List<SymptomModel> symptoms;
  const _SymptomPicker({required this.symptoms});

  @override
  State<_SymptomPicker> createState() => _SymptomPickerState();
}

class _SymptomPickerState extends State<_SymptomPicker> {
  String query = '';
  final Set<String> selectedIds = {};

  @override
  Widget build(BuildContext context) {
    final filtered = widget.symptoms
        .where(
          (item) =>
              item.symptomName.toLowerCase().contains(query.toLowerCase()),
        )
        .toList();
    final popular = widget.symptoms.take(16).toList();
    final popularIds = popular.map((item) => item.symptomId).toSet();
    final otherSymptoms = widget.symptoms
        .where((item) => !popularIds.contains(item.symptomId))
        .toList();
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        automaticallyImplyLeading: false,
        centerTitle: true,
        title: Text('เลือกอาการ', style: AppTextStyles.h3),
        actions: [
          IconButton(
            tooltip: 'ปิด',
            onPressed: () => Navigator.pop(context),
            icon: const Icon(Icons.close_rounded),
          ),
        ],
        bottom: PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(
            height: 1,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: SafeArea(
        child: Column(
          children: [
            const SizedBox(height: 14),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: TextField(
                onChanged: (value) => setState(() => query = value.trim()),
                decoration: const InputDecoration(
                  hintText: 'ค้นหาอาการ',
                  prefixIcon: Icon(Icons.search_rounded),
                ),
              ),
            ),
            const SizedBox(height: 8),
            Expanded(
              child: filtered.isEmpty
                  ? const Center(child: Text('ไม่พบอาการในรายการ'))
                  : query.isNotEmpty
                  ? ListView(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      children: [
                        Text('ผลการค้นหา', style: AppTextStyles.body1Bold),
                        const SizedBox(height: 6),
                        ...filtered.map(_textSymptomTile),
                      ],
                    )
                  : CustomScrollView(
                      slivers: [
                        SliverPadding(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
                          sliver: SliverToBoxAdapter(
                            child: Text(
                              'อาการที่พบบ่อย',
                              style: AppTextStyles.body1Bold,
                            ),
                          ),
                        ),
                        SliverPadding(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          sliver: SliverGrid(
                            delegate: SliverChildBuilderDelegate(
                              (context, index) =>
                                  _popularSymptomCard(popular[index]),
                              childCount: popular.length,
                            ),
                            gridDelegate:
                                const SliverGridDelegateWithFixedCrossAxisCount(
                                  crossAxisCount: 4,
                                  mainAxisSpacing: 8,
                                  crossAxisSpacing: 8,
                                  childAspectRatio: 0.9,
                                ),
                          ),
                        ),
                        SliverPadding(
                          padding: const EdgeInsets.fromLTRB(16, 20, 16, 6),
                          sliver: SliverToBoxAdapter(
                            child: Text(
                              'อาการอื่นๆ',
                              style: AppTextStyles.body1Bold,
                            ),
                          ),
                        ),
                        SliverPadding(
                          padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                          sliver: SliverList(
                            delegate: SliverChildBuilderDelegate(
                              (context, index) =>
                                  _textSymptomTile(otherSymptoms[index]),
                              childCount: otherSymptoms.length,
                            ),
                          ),
                        ),
                      ],
                    ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
              child: Column(
                children: [
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton(
                      onPressed: selectedIds.isEmpty
                          ? null
                          : () => Navigator.pop(
                              context,
                              selectedIds
                                  .map((id) => _SymptomChoice(symptomId: id))
                                  .toList(),
                            ),
                      child: Text(
                        selectedIds.isEmpty
                            ? 'เลือกอาการ'
                            : 'เพิ่มอาการ ${selectedIds.length} รายการ',
                      ),
                    ),
                  ),
                  TextButton.icon(
                    icon: const Icon(Icons.edit_outlined),
                    label: const Text('ไม่พบในรายการ — เพิ่มชื่ออาการเอง'),
                    onPressed: _addCustomSymptom,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _toggle(SymptomModel symptom) => setState(() {
    selectedIds.contains(symptom.symptomId)
        ? selectedIds.remove(symptom.symptomId)
        : selectedIds.add(symptom.symptomId);
  });

  Widget _popularSymptomCard(SymptomModel symptom) {
    final selected = selectedIds.contains(symptom.symptomId);

    return InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: () => _toggle(symptom),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: selected
              ? AppColors.primaryLight
              : Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: selected
                ? AppColors.primary
                : Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
        child: Stack(
          children: [
            Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  _symptomIconBox(symptom: symptom, selected: selected),
                  const SizedBox(height: 7),
                  Text(
                    symptom.symptomName,
                    maxLines: 2,
                    textAlign: TextAlign.center,
                    overflow: TextOverflow.ellipsis,
                    style: AppTextStyles.body3Bold,
                  ),
                ],
              ),
            ),

            Positioned(
              right: 0,
              top: 0,
              child: _selectionIndicator(selected: selected, size: 20),
            ),
          ],
        ),
      ),
    );
  }

  Widget _textSymptomTile(SymptomModel symptom) {
    final selected = selectedIds.contains(symptom.symptomId);

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: () => _toggle(symptom),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            color: selected
                ? AppColors.primaryLight
                : Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(
              color: selected
                  ? AppColors.primary
                  : Theme.of(context).colorScheme.outlineVariant,
            ),
          ),
          child: Row(
            children: [
              _symptomIconBox(symptom: symptom, selected: selected),

              const SizedBox(width: 16),

              Expanded(
                child: Text(
                  symptom.symptomName,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: AppTextStyles.body1Bold,
                ),
              ),

              const SizedBox(width: 12),

              _selectionIndicator(selected: selected, size: 20),
            ],
          ),
        ),
      ),
    );
  }

  Widget _symptomIconBox({
    required SymptomModel symptom,
    required bool selected,
  }) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 160),
      width: 44,
      height: 44,
      decoration: BoxDecoration(
        color: selected
            ? AppColors.primary.withValues(alpha: 0.10)
            : Theme.of(context).colorScheme.surfaceContainerLow,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Center(
        child: SizedBox(
          width: 24,
          height: 24,
          child: Center(
            child: SymptomIcon(
              iconName: symptom.symptomImage ?? symptom.category?.icon,
              size: 24,
              color: selected
                  ? AppColors.primary
                  : Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
        ),
      ),
    );
  }

  Widget _selectionIndicator({required bool selected, required double size}) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 160),
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: selected ? AppColors.primary : Colors.transparent,
        border: Border.all(
          color: selected
              ? AppColors.primary
              : Theme.of(context).colorScheme.outlineVariant,
          width: 2,
        ),
      ),
      child: selected
          ? Center(
              child: Icon(
                Icons.check_rounded,
                size: size * 0.65,
                color: AppColors.white,
              ),
            )
          : null,
    );
  }

  Future<void> _addCustomSymptom() async {
    final controller = TextEditingController(text: query);
    final text = await showDialog<String>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('เพิ่มอาการที่ต้องการติดตาม'),
        content: TextField(
          controller: controller,
          maxLength: 200,
          autofocus: true,
          decoration: const InputDecoration(labelText: 'ชื่ออาการ'),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('ยกเลิก'),
          ),
          FilledButton(
            onPressed: () {
              final value = controller.text.trim();
              if (value.isNotEmpty) Navigator.pop(dialogContext, value);
            },
            child: const Text('เพิ่ม'),
          ),
        ],
      ),
    );
    controller.dispose();
    if (text != null && mounted) {
      Navigator.pop(context, [_SymptomChoice(customText: text)]);
    }
  }
}

class _SymptomChoice {
  final String? symptomId;
  final String? customText;

  const _SymptomChoice({this.symptomId, this.customText});
}

class _FollowUpTrendPoint {
  final DateTime date;
  final double value;

  const _FollowUpTrendPoint(this.date, this.value);
}

class _CategoricalTrendPoint {
  final DateTime date;
  final String value;

  const _CategoricalTrendPoint(this.date, this.value);
}

class _CategoricalTrendSeries {
  final String title;
  final List<_CategoricalTrendPoint> points;

  const _CategoricalTrendSeries(this.title, this.points);
}

class _MatchedResponseAlert {
  final String level;
  final String? title;
  final String message;
  final bool requiresAcknowledgement;

  const _MatchedResponseAlert({
    required this.level,
    this.title,
    required this.message,
    required this.requiresAcknowledgement,
  });
}

class _FollowUpTrendSeries {
  final String title;
  final List<_FollowUpTrendPoint> points;
  final String unit;
  final DateTime rangeStart;
  final DateTime rangeEnd;

  _FollowUpTrendSeries(
    this.title,
    this.points,
    this.unit, {
    DateTime? rangeStart,
    DateTime? rangeEnd,
  }) : rangeStart = rangeStart ?? points.first.date,
       rangeEnd = rangeEnd ?? points.last.date;
}

class _FollowUpLineChartPainter extends CustomPainter {
  final List<double> values;
  final List<DateTime> dates;
  final DateTime rangeStart;
  final DateTime rangeEnd;
  final Color lineColor;
  final Color gridColor;

  const _FollowUpLineChartPainter({
    required this.values,
    required this.dates,
    required this.rangeStart,
    required this.rangeEnd,
    required this.lineColor,
    required this.gridColor,
  });

  @override
  void paint(Canvas canvas, Size size) {
    if (values.isEmpty) return;
    final gridPaint = Paint()
      ..color = gridColor
      ..strokeWidth = 1;
    for (var index = 0; index < 4; index++) {
      final y = size.height * index / 3;
      canvas.drawLine(Offset(0, y), Offset(size.width, y), gridPaint);
    }

    var minimum = values.reduce((left, right) => left < right ? left : right);
    var maximum = values.reduce((left, right) => left > right ? left : right);
    if (minimum == maximum) {
      minimum -= 1;
      maximum += 1;
    }
    const inset = 8.0;
    final path = Path();
    final points = <Offset>[];
    final rangeMilliseconds = rangeEnd.difference(rangeStart).inMilliseconds;
    for (var index = 0; index < values.length; index++) {
      final x = rangeMilliseconds == 0
          ? size.width / 2
          : inset +
                (size.width - inset * 2) *
                    dates[index].difference(rangeStart).inMilliseconds /
                    rangeMilliseconds;
      final normalized = (values[index] - minimum) / (maximum - minimum);
      final y = inset + (size.height - inset * 2) * (1 - normalized);
      points.add(Offset(x, y));
      index == 0 ? path.moveTo(x, y) : path.lineTo(x, y);
    }
    canvas.drawPath(
      path,
      Paint()
        ..color = lineColor
        ..strokeWidth = 2.5
        ..style = PaintingStyle.stroke
        ..strokeCap = StrokeCap.round
        ..strokeJoin = StrokeJoin.round,
    );
    final pointPaint = Paint()..color = lineColor;
    final centerPaint = Paint()..color = Colors.white;
    for (final point in points) {
      canvas.drawCircle(point, 4.5, pointPaint);
      canvas.drawCircle(point, 2, centerPaint);
    }
  }

  @override
  bool shouldRepaint(covariant _FollowUpLineChartPainter oldDelegate) =>
      oldDelegate.values != values ||
      oldDelegate.dates != dates ||
      oldDelegate.rangeStart != rangeStart ||
      oldDelegate.rangeEnd != rangeEnd ||
      oldDelegate.lineColor != lineColor ||
      oldDelegate.gridColor != gridColor;
}
