import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../data/models/health_episode_model.dart';
import '../../../data/models/symptom_model.dart';
import '../../../data/repositories/personal_health_repository.dart';
import '../../../data/repositories/symptom_repository.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../shared/widgets/symptom_icon.dart';
import 'daily_health_record_screen.dart';

class FollowUpScreen extends StatefulWidget {
  final dynamic assessmentId;
  final String symptomName;

  const FollowUpScreen({
    super.key,
    required this.assessmentId,
    required this.symptomName,
  });

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
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error =
            'ไม่สามารถเปิดการติดตามอาการได้ กรุณาเข้าสู่ระบบแล้วลองอีกครั้ง';
      });
    }
  }

  Future<dynamic> _chooseTrackingDestination(
    List<HealthEpisodeModel> activeEpisodes,
  ) async {
    if (activeEpisodes.isEmpty) return null;
    return showModalBottomSheet<dynamic>(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('ติดตามร่วมกับรายการเดิมหรือไม่?', style: AppTextStyles.h3),
              const SizedBox(height: 6),
              Text(
                'เลือกรายการเดิมเมื่อผลประเมินนี้เป็นเหตุการณ์สุขภาพเดียวกัน',
                style: AppTextStyles.body2.copyWith(
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 14),
              ...activeEpisodes.map(
                (episode) => ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const CircleAvatar(
                    child: Icon(Icons.monitor_heart_outlined),
                  ),
                  title: Text(
                    episode.symptoms.map((item) => item.symptomName).join(', '),
                  ),
                  subtitle: Text(
                    'เริ่ม ${formatThaiDateTime(episode.startedAt.toLocal())}',
                  ),
                  trailing: const Icon(Icons.arrow_forward_rounded),
                  onTap: () => Navigator.pop(sheetContext, episode.id),
                ),
              ),
              const SizedBox(height: 8),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () => Navigator.pop(sheetContext),
                  icon: const Icon(Icons.add_rounded),
                  label: const Text('เริ่มรายการติดตามใหม่'),
                ),
              ),
            ],
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
      isScrollControlled: true,
      builder: (sheetContext) => DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.62,
        minChildSize: 0.4,
        maxChildSize: 0.9,
        builder: (_, controller) => SafeArea(
          child: ListView(
            controller: controller,
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: AppColors.border,
                    borderRadius: BorderRadius.circular(99),
                  ),
                ),
              ),
              const SizedBox(height: 20),
              Text('เหตุผลที่สิ้นสุดการติดตาม', style: AppTextStyles.h3),
              const SizedBox(height: 10),
              ...reasons.entries.map(
                (item) => ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 4),
                  title: Text(item.value),
                  trailing: const Icon(Icons.chevron_right_rounded),
                  onTap: () => Navigator.pop(sheetContext, item.key),
                ),
              ),
            ],
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
        final today = DateTime.now();
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
    if (_expandedSymptomIds.isEmpty && episode.symptoms.isNotEmpty) {
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
    setState(() => _saving = true);
    try {
      final repository = context.read<PersonalHealthRepository>();
      for (final symptom in _episode!.symptoms.where(
        (item) => item.status == 'A',
      )) {
        final draft = _drafts[symptom.id]!;
        if (draft.initialEntryId != null) {
          await repository.updateEpisodeFollowUp(
            draft.initialEntryId,
            severity: draft.severity.round(),
            note: draft.note.text,
            answers: draft.serializedAnswers,
          );
        } else {
          await repository.addEpisodeFollowUp(
            symptom.id,
            severity: draft.severity.round(),
            note: draft.note.text,
            answers: draft.serializedAnswers,
          );
        }
      }
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _hasEntriesToday
                ? 'อัปเดตการติดตามวันนี้แล้ว'
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

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: AppColors.background,
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
      bottom: const PreferredSize(
        preferredSize: Size.fromHeight(1),
        child: Divider(height: 1, color: AppColors.border),
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
            child: AppButton(
              label: _saving
                  ? 'กำลังบันทึก...'
                  : _hasEntriesToday
                  ? 'อัปเดตการติดตามวันนี้'
                  : 'บันทึกการติดตามวันนี้',
              loading: _saving,
              onTap: _save,
            ),
          ),
  );

  Widget _body() => SafeArea(
    child: ListView(
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 28),
      children: [
        _trackingHeader(),
        const SizedBox(height: 18),
        Text('อาการหลัก', style: AppTextStyles.h3),
        const SizedBox(height: 12),
        ..._activeSymptoms.where((item) => item.isPrimary).map(_symptomCard),
        if (_activeSymptoms.any((item) => !item.isPrimary)) ...[
          const SizedBox(height: 4),
          Text('อาการร่วม', style: AppTextStyles.h3),
          const SizedBox(height: 12),
          ..._activeSymptoms.where((item) => !item.isPrimary).map(_symptomCard),
        ],
        OutlinedButton.icon(
          onPressed: _addSymptom,
          icon: const Icon(Icons.add_circle_outline_rounded),
          label: const Text('เพิ่มอาการร่วม'),
        ),
        const SizedBox(height: 20),
        _timelineSection(),
        const SizedBox(height: 20),
        _questionsSection(),
        const SizedBox(height: 20),
        _linkedHealthRecordCard(),
      ],
    ),
  );

  List<EpisodeSymptomModel> get _activeSymptoms =>
      _episode!.symptoms.where((item) => item.status == 'A').toList()
        ..sort((a, b) {
          if (a.isPrimary == b.isPrimary) return 0;
          return a.isPrimary ? -1 : 1;
        });

  bool get _hasEntriesToday =>
      _drafts.values.any((draft) => draft.initialEntryId != null);

  Widget _trackingHeader() => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(20),
    decoration: BoxDecoration(
      gradient: const LinearGradient(
        colors: [AppColors.primary, AppColors.primaryMid],
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
      ),
      borderRadius: BorderRadius.circular(24),
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
                color: AppColors.white.withValues(alpha: 0.16),
                borderRadius: BorderRadius.circular(15),
              ),
              child: const Icon(
                Icons.monitor_heart_outlined,
                color: AppColors.white,
              ),
            ),
            const SizedBox(width: 13),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    widget.symptomName,
                    style: AppTextStyles.h4.copyWith(color: AppColors.white),
                  ),
                  Text(
                    'ตอบจากอาการที่สังเกตได้ในตอนนี้',
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
        const SizedBox(height: 18),
        const Row(
          children: [
            Expanded(
              child: _TrackingStep(number: '1', label: 'ระดับอาการ'),
            ),
            SizedBox(width: 8),
            Expanded(
              child: _TrackingStep(number: '2', label: 'ข้อมูลร่วม'),
            ),
            SizedBox(width: 8),
            Expanded(
              child: _TrackingStep(number: '3', label: 'คำถามติดตาม'),
            ),
          ],
        ),
      ],
    ),
  );

  Widget _timelineSection() {
    final hasEntries = _activeSymptoms.any((item) => item.entries.isNotEmpty);
    final grouped =
        <
          String,
          List<({EpisodeSymptomModel symptom, FollowUpEntryModel entry})>
        >{};
    for (final symptom in _activeSymptoms) {
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
        color: AppColors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.border),
      ),
      clipBehavior: Clip.antiAlias,
      child: ExpansionTile(
        shape: const Border(),
        collapsedShape: const Border(),
        leading: const CircleAvatar(
          backgroundColor: AppColors.surfacePrimary,
          child: Icon(Icons.timeline_rounded, color: AppColors.primary),
        ),
        title: Text('ไทม์ไลน์การติดตาม', style: AppTextStyles.body1Bold),
        subtitle: Text(
          hasEntries ? 'กดเพื่อดูประวัติรายวัน' : 'ยังไม่มีบันทึกการติดตาม',
          style: AppTextStyles.body3.copyWith(color: AppColors.textSecondary),
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
                    color: AppColors.background,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.border),
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
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      ...items.map(
                        (item) => Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: Row(
                            children: [
                              Container(
                                width: 38,
                                height: 38,
                                decoration: BoxDecoration(
                                  color: AppColors.primaryLight,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: const Icon(
                                  Icons.monitor_heart_outlined,
                                  color: AppColors.primary,
                                  size: 21,
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
                                        color: AppColors.textSecondary,
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
                                  '${item.entry.severity}/10',
                                  style: AppTextStyles.body2Bold.copyWith(
                                    color: AppColors.primary,
                                  ),
                                ),
                              ),
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

  Widget _questionsSection() {
    final groups = _activeSymptoms
        .where((item) => item.questions.isNotEmpty)
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
          style: AppTextStyles.body2.copyWith(color: AppColors.textSecondary),
        ),
        const SizedBox(height: 12),
        ...groups.expand((symptom) {
          final specific = symptom.questions
              .where((item) => !item.isGlobal)
              .toList();
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
          if (specific.isNotEmpty) {
            widgets.add(
              Padding(
                padding: const EdgeInsets.fromLTRB(2, 8, 2, 10),
                child: Text(
                  'คำถามเฉพาอาการ: ${symptom.symptomName}',
                  style: AppTextStyles.body1Bold,
                ),
              ),
            );
            widgets.addAll(
              specific.map((question) {
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
      leading: const CircleAvatar(
        backgroundColor: AppColors.surfacePrimary,
        child: Icon(Icons.favorite_outline_rounded, color: AppColors.primary),
      ),
      title: Text('บันทึกสุขภาพประจำวัน', style: AppTextStyles.body1Bold),
      subtitle: Text(
        'บันทึกว่าวันนี้สบายดีหรือมีอาการ ข้อมูลจะแสดงร่วมกันในหน้าแนวโน้มสุขภาพ',
        style: AppTextStyles.body3.copyWith(color: AppColors.textSecondary),
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
        borderRadius: BorderRadius.circular(22),
        side: const BorderSide(color: AppColors.border),
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
                                : 'ล่าสุด ${formatThaiDateTime(latestEntry.recordedAt.toLocal())}  •  ${latestEntry.severity}/10',
                            style: AppTextStyles.body3.copyWith(
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (symptom.isPrimary)
                      Chip(
                        avatar: const Icon(Icons.push_pin_outlined, size: 16),
                        label: const Text('อาการหลัก'),
                        visualDensity: VisualDensity.compact,
                        backgroundColor: AppColors.surfacePrimary,
                      ),
                    Icon(
                      expanded
                          ? Icons.keyboard_arrow_up_rounded
                          : Icons.keyboard_arrow_down_rounded,
                      color: AppColors.textSecondary,
                    ),
                  ],
                ),
              ),
            ),
            if (!expanded) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  Text(
                    'ระดับที่จะบันทึก',
                    style: AppTextStyles.body3.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const Spacer(),
                  Text(
                    '${draft.severity.round()} / 10',
                    style: AppTextStyles.body2Bold.copyWith(
                      color: AppColors.primary,
                    ),
                  ),
                ],
              ),
            ],
            if (expanded) ...[
              if (latestEntry != null) ...[
                const SizedBox(height: 8),
                Text(
                  'ค่าครั้งก่อน ${latestEntry.severity}/10 ใช้เป็นข้อมูลเปรียบเทียบ',
                  style: AppTextStyles.body3.copyWith(
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
              const SizedBox(height: 18),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'ระดับความรุนแรง',
                      style: AppTextStyles.body1Bold,
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: AppColors.surfacePrimary,
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      '${draft.severity.round()} / 10',
                      style: AppTextStyles.body2Bold.copyWith(
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                ],
              ),
              Slider(
                value: draft.severity,
                min: 1,
                max: 10,
                divisions: 9,
                onChanged: (value) => setState(() => draft.severity = value),
              ),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('น้อย', style: AppTextStyles.body3),
                  Text('มาก', style: AppTextStyles.body3),
                ],
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
    final title = '${question.questionText}${question.isRequired ? ' *' : ''}';
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surfacePrimary,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
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
              Expanded(child: Text(title, style: AppTextStyles.body2Bold)),
            ],
          ),
          if (question.description?.trim().isNotEmpty == true) ...[
            const SizedBox(height: 3),
            Text(
              question.description!,
              style: AppTextStyles.body2.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
          ],
          const SizedBox(height: 8),
          if (question.answerType == 'boolean')
            SegmentedButton<bool>(
              segments: [
                ButtonSegment(
                  value: true,
                  label: Text(
                    question.options.isNotEmpty
                        ? question.options.first
                        : 'ใช่',
                  ),
                ),
                ButtonSegment(
                  value: false,
                  label: Text(
                    question.options.length > 1
                        ? question.options[1]
                        : 'ไม่ใช่',
                  ),
                ),
              ],
              emptySelectionAllowed: true,
              selected: draft.answers[question.id] is bool
                  ? {draft.answers[question.id] as bool}
                  : <bool>{},
              onSelectionChanged: (values) => setState(() {
                final value = values.isEmpty ? null : values.first;
                draft.answers[question.id] = value;
                if (value == true &&
                    question.questionText.contains('อาการอื่นเพิ่มขึ้น')) {
                  WidgetsBinding.instance.addPostFrameCallback(
                    (_) => _addSymptom(),
                  );
                }
              }),
            )
          else if (question.answerType == 'single_choice')
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: question.options
                  .map(
                    (option) => ChoiceChip(
                      label: Text(option),
                      selected: draft.answers[question.id] == option,
                      onSelected: (_) =>
                          setState(() => draft.answers[question.id] = option),
                    ),
                  )
                  .toList(),
            )
          else if (question.answerType == 'multiple_choice')
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: question.options.map((option) {
                final selected =
                    (draft.answers[question.id] as List<String>? ?? const [])
                        .contains(option);
                return FilterChip(
                  label: Text(option),
                  selected: selected,
                  onSelected: (checked) => setState(() {
                    final values = List<String>.from(
                      draft.answers[question.id] as List<String>? ?? const [],
                    );
                    checked ? values.add(option) : values.remove(option);
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
          color: AppColors.white.withValues(alpha: 0.18),
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
  double severity = 5;
  final note = TextEditingController();
  final List<FollowUpQuestionModel> questions;
  final Map<int, dynamic> answers = {};
  final Map<int, TextEditingController> questionControllers = {};
  final dynamic initialEntryId;

  _SymptomDraft(this.questions, {FollowUpEntryModel? initialEntry})
    : initialEntryId = initialEntry?.id {
    if (initialEntry != null) {
      severity = initialEntry.severity.toDouble();
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
        final controllerValue = questionControllers[question.id]?.text.trim();
        final answer = answers[question.id];
        return (answer != null && (answer is! List || answer.isNotEmpty)) ||
            controllerValue?.isNotEmpty == true;
      });

  List<Map<String, dynamic>> get serializedAnswers => questions
      .map((question) {
        dynamic value = answers[question.id];
        final text = questionControllers[question.id]?.text.trim();
        if (text?.isNotEmpty == true) {
          value = question.answerType == 'number'
              ? double.tryParse(text!)
              : text;
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
      backgroundColor: AppColors.background,
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
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, color: AppColors.border),
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
                              'อาการยอดนิยม',
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
          color: selected ? AppColors.primaryLight : AppColors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: selected ? AppColors.primary : AppColors.border,
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
            color: selected ? AppColors.primaryLight : AppColors.white,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(
              color: selected ? AppColors.primary : AppColors.border,
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
            : AppColors.surfacePrimary,
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
              color: selected ? AppColors.primary : AppColors.textSecondary,
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
          color: selected ? AppColors.primary : AppColors.border,
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
