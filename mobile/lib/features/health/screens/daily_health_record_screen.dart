import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../shared/widgets/app_layout.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/fuzzy_search.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../data/models/daily_health_record_model.dart';
import '../../../data/models/health_episode_model.dart';
import '../../../data/models/symptom_model.dart';
import '../../../data/repositories/personal_health_repository.dart';
import '../../../data/repositories/symptom_repository.dart';
import '../../../data/services/local_notification_service.dart';
import '../../../shared/widgets/symptom_icon.dart';
import 'follow_up_screen.dart';
import 'health_episode_list_screen.dart';

class DailyHealthRecordScreen extends StatefulWidget {
  final DateTime? initialDate;

  const DailyHealthRecordScreen({super.key, this.initialDate});

  @override
  State<DailyHealthRecordScreen> createState() =>
      _DailyHealthRecordScreenState();
}

class _DailyHealthRecordScreenState extends State<DailyHealthRecordScreen> {
  static const _weekdays = ['จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส', 'อา'];
  late DateTime _selectedDate;
  Map<String, List<DailyHealthRecordModel>> _records = {};
  List<HealthEpisodeModel> _healthEpisodes = [];
  bool _loading = true;
  bool _saving = false;
  bool _hasLoaded = false;
  int _loadGeneration = 0;
  bool _changingStatus = false;
  dynamic _editingRecordId;
  final Set<String> _expandedTrackingIds = {};
  final Set<String> _expandedRecordIds = {};
  final _mainScrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _selectedDate = _dateOnly(widget.initialDate ?? DateTime.now());
    _load();
  }

  @override
  void dispose() {
    _mainScrollController.dispose();
    super.dispose();
  }

  DateTime _dateOnly(DateTime date) =>
      DateTime(date.year, date.month, date.day);
  String _key(DateTime date) => DateFormat('yyyy-MM-dd').format(date);
  DateTime get _weekStart =>
      _selectedDate.subtract(Duration(days: _selectedDate.weekday - 1));
  bool get _isCurrentWeek {
    final today = _dateOnly(DateTime.now());
    final currentWeekStart = today.subtract(Duration(days: today.weekday - 1));
    return _key(_weekStart) == _key(currentWeekStart);
  }
  bool get _isToday => _key(_selectedDate) == _key(DateTime.now());

  Future<void> _load() async {
    final generation = ++_loadGeneration;
    setState(() => _loading = true);
    final from = _weekStart;
    final to = from.add(const Duration(days: 6));
    try {
      final repository = context.read<PersonalHealthRepository>();
      final recordsFuture = repository.dailyRecords(
        from: _key(from),
        to: _key(to),
      );
      final episodesFuture = repository.healthEpisodes(
        from: _key(from),
        to: _key(to),
      );
      final records = await recordsFuture;
      final episodes = await episodesFuture;
      if (!mounted || generation != _loadGeneration) return;
      final grouped = <String, List<DailyHealthRecordModel>>{};
      for (final item in records) {
        grouped.putIfAbsent(_key(item.recordedOn), () => []).add(item);
      }
      setState(() {
        _records = grouped;
        _healthEpisodes = episodes;
      });
    } catch (_) {
      if (!mounted || generation != _loadGeneration) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('ไม่สามารถโหลดบันทึกสุขภาพได้')),
      );
    } finally {
      if (mounted && generation == _loadGeneration) {
        setState(() {
          _loading = false;
          _hasLoaded = true;
        });
      }
    }
  }

  Future<void> _openCalendar() async {
    final selected = await Navigator.push<DateTime>(
      context,
      MaterialPageRoute(
        builder: (_) => DailyHealthCalendarScreen(selectedDate: _selectedDate),
      ),
    );
    if (selected == null || !mounted) return;
    setState(() {
      _selectedDate = _dateOnly(selected);
      _changingStatus = false;
    });
    await _load();
  }

  Future<void> _changeWeek(int offset) async {
    if (offset > 0 && _isCurrentWeek) return;
    final nextDate = _selectedDate.add(Duration(days: offset * 7));
    final today = _dateOnly(DateTime.now());
    setState(() {
      _selectedDate = nextDate.isAfter(today) ? today : nextDate;
      _changingStatus = false;
    });
    await _load();
  }

  Future<void> _goToToday() async {
    if (_loading || _saving || _isToday) return;

    final previousWeekStart = _key(_weekStart);
    setState(() {
      _selectedDate = _dateOnly(DateTime.now());
      _changingStatus = false;
    });

    if (_key(_weekStart) != previousWeekStart) await _load();
  }

  Future<void> _changeDay(int offset) async {
    if (_loading || _saving) return;

    final nextDate = _selectedDate.add(Duration(days: offset));
    if (nextDate.isAfter(_dateOnly(DateTime.now()))) return;

    final currentWeek = _key(_weekStart);
    setState(() {
      _selectedDate = nextDate;
      _changingStatus = false;
    });
    if (_key(_weekStart) != currentWeek) await _load();
  }

  Future<void> _handleHorizontalSwipe(DragEndDetails details) async {
    final velocity = details.primaryVelocity ?? 0;
    if (velocity.abs() < 200) return;
    await _changeDay(velocity < 0 ? 1 : -1);
  }

  Future<DailyHealthRecordModel?> _save(
    String status, {
    String? note,
    List<String> symptomIds = const [],
    List<dynamic> healthEpisodeIds = const [],
    dynamic recordId,
  }) async {
    if (_selectedDate.isAfter(_dateOnly(DateTime.now()))) return null;
    setState(() => _saving = true);
    try {
      final record = await context
          .read<PersonalHealthRepository>()
          .saveDailyRecord(
            recordId: recordId,
            recordedOn: _key(_selectedDate),
            status: status,
            note: note,
            symptomIds: symptomIds,
            healthEpisodeIds: healthEpisodeIds,
          );
      if (!mounted) return null;
      setState(() {
        final dayRecords = _records.putIfAbsent(_key(_selectedDate), () => []);
        final existingIndex = dayRecords.indexWhere(
          (item) => item.id.toString() == record.id.toString(),
        );
        if (existingIndex >= 0) {
          dayRecords[existingIndex] = record;
        } else {
          dayRecords.insert(0, record);
        }
        _changingStatus = false;
        _editingRecordId = null;
      });
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('บันทึกสุขภาพวันนี้แล้ว')));
      return record;
    } catch (_) {
      if (!mounted) return null;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('บันทึกไม่สำเร็จ กรุณาลองอีกครั้ง')),
      );
      return null;
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _recordUnwell() async {
    final existing = _editingRecord;
    final editingUnwell = existing?.status == 'unwell';
    final symptomRepository = context.read<SymptomRepository>();
    final selection = await Navigator.push<_UnwellSelection>(
      context,
      MaterialPageRoute(
        builder: (_) => _SymptomSelectionScreen(
          repository: symptomRepository,
          activeEpisodes: _healthEpisodes
              .where((episode) => episode.status == 'A')
              .toList(),
          initialSelectedIds: editingUnwell
              ? existing!.symptoms.map((symptom) => symptom.symptomId).toSet()
              : {},
        ),
      ),
    );
    if (selection != null) {
      final record = await _save(
        'unwell',
        note: editingUnwell ? existing?.note : null,
        symptomIds: selection.symptomIds,
        healthEpisodeIds: selection.healthEpisodeIds,
        recordId: existing?.id,
      );
      if (mounted && !editingUnwell && record != null) {
        await _offerSymptomTracking(selection, record);
      }
    }
  }

  Future<void> _offerSymptomTracking(
    _UnwellSelection selection,
    DailyHealthRecordModel record,
  ) async {
    final linkedEpisodes = _healthEpisodes
        .where(
          (episode) => selection.healthEpisodeIds.any(
            (id) => id.toString() == episode.id.toString(),
          ),
        )
        .toList();
    final trackedSymptomIds = linkedEpisodes
        .expand((episode) => episode.symptoms)
        .map((symptom) => symptom.symptomId)
        .whereType<String>()
        .toSet();
    final newSymptomIds = selection.symptomIds
        .where((id) => !trackedSymptomIds.contains(id))
        .toList();
    final continueTracking = linkedEpisodes.isNotEmpty && newSymptomIds.isEmpty;
    final accepted = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        icon: const Icon(
          Icons.monitor_heart_outlined,
          color: AppColors.primary,
          size: 34,
        ),
        title: Text(
          continueTracking
              ? 'บันทึกติดตามอาการต่อไหม?'
              : 'ต้องการติดตามอาการไหม?',
        ),
        content: Text(
          continueTracking
              ? 'อาการนี้เชื่อมกับรายการที่คุณกำลังติดตามแล้ว ต้องการบันทึกการเปลี่ยนแปลงของอาการต่อเลยหรือไม่?'
              : 'เริ่มติดตามอาการจากบันทึกสุขภาพวันนี้ได้ทันที โดยไม่ต้องทำแบบประเมินก่อน',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('ไว้ภายหลัง'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(continueTracking ? 'ติดตามต่อ' : 'เริ่มติดตาม'),
          ),
        ],
      ),
    );
    if (accepted != true || !mounted) return;
    if (continueTracking) {
      await _openTracking(linkedEpisodes.first);
    } else {
      final destination = await _chooseTrackingDestination();
      if (destination == null || !mounted) return;
      try {
        final episode = await context
            .read<PersonalHealthRepository>()
            .startHealthEpisodeFromDailyRecord(
              record.id,
              symptomIds: newSymptomIds,
              healthEpisodeId: destination.healthEpisodeId,
            );
        if (!mounted) return;
        if (destination.healthEpisodeId == null) {
          await LocalNotificationService.instance.showActivity(
            title: 'เริ่มติดตามอาการแล้ว',
            body: 'แตะเพื่อดูรายละเอียดการติดตามอาการ',
            payload: 'health_episode:${episode.id}',
          );
        }
        await Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => FollowUpScreen(
              episodeId: episode.id,
              symptomName: episode.symptoms.first.symptomName,
              offerReminder: true,
              recordDate: _selectedDate,
            ),
          ),
        );
        if (mounted) await _load();
      } catch (_) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('เริ่มติดตามอาการไม่สำเร็จ กรุณาลองอีกครั้ง')),
        );
      }
    }
  }

  Future<_TrackingDestination?> _chooseTrackingDestination() async {
    final activeEpisodes = _healthEpisodes
        .where((episode) => episode.status == 'A')
        .toList();
    if (activeEpisodes.isEmpty) {
      return const _TrackingDestination();
    }

    return showModalBottomSheet<_TrackingDestination>(
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
                  'เลือกรายการเดิมเมื่ออาการที่บันทึกเป็นเหตุการณ์สุขภาพเดียวกัน',
                  style: AppTextStyles.body2.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                ),
                const SizedBox(height: 14),
                ConstrainedBox(
                  constraints: const BoxConstraints(maxHeight: 360),
                  child: ListView(
                    shrinkWrap: true,
                    children: [
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
                              onTap: () => Navigator.pop(
                                sheetContext,
                                _TrackingDestination(
                                  healthEpisodeId: episode.id,
                                ),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 8),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: () => Navigator.pop(
                      sheetContext,
                      const _TrackingDestination(),
                    ),
                    icon: const Icon(Icons.add_rounded),
                    label: const Text('แยกเป็นรายการติดตามใหม่'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _editUnwellNote() async {
    final existing = _editingRecord;
    if (existing?.status != 'unwell') return;
    final note = await _showNoteEditor(
      title: 'รายละเอียดเพิ่มเติม',
      hintText: 'เช่น เริ่มมีอาการช่วงเช้า หรือสิ่งที่สังเกตได้',
      initialText: existing?.note ?? '',
      color: AppColors.primary,
    );
    if (note != null) {
      await _save(
        'unwell',
        note: note,
        symptomIds: existing!.symptoms
            .map((symptom) => symptom.symptomId)
            .toList(),
        healthEpisodeIds: existing.healthEpisodes
            .map((episode) => episode.id)
            .toList(),
        recordId: existing.id,
      );
    }
  }

  Future<String?> _showNoteEditor({
    required String title,
    required String hintText,
    required String initialText,
    required Color color,
  }) => Navigator.push<String>(
    context,
    MaterialPageRoute(
      fullscreenDialog: true,
      builder: (_) => _NoteEditorScreen(
        title: title,
        hintText: hintText,
        initialText: initialText,
        color: color,
      ),
    ),
  );

  Future<void> _recordWell() async {
    final existing = _editingRecord;
    final note = await _showNoteEditor(
      title: 'วันนี้สบายดี',
      hintText: 'เช่น วันนี้พักผ่อนเพียงพอ รู้สึกสดชื่น',
      initialText: existing?.status == 'well' ? existing?.note ?? '' : '',
      color: AppColors.primary,
    );
    if (note != null) {
      await _save('well', note: note, recordId: existing?.id);
    }
  }

  Future<void> _recordNormal() async {
    final existing = _editingRecord;
    final note = await _showNoteEditor(
      title: 'วันนี้รู้สึกปกติ',
      hintText: 'เพิ่มรายละเอียดที่ต้องการจดจำ (ไม่บังคับ)',
      initialText: existing?.status == 'normal' ? existing?.note ?? '' : '',
      color: AppColors.primary,
    );
    if (note != null) {
      await _save('normal', note: note, recordId: existing?.id);
    }
  }

  DailyHealthRecordModel? get _editingRecord {
    if (_editingRecordId == null) return null;
    for (final record in _records[_key(_selectedDate)] ?? const []) {
      if (record.id.toString() == _editingRecordId.toString()) return record;
    }
    return null;
  }

  List<HealthEpisodeModel> _trackingRecordsOn(DateTime date) => _healthEpisodes
      .where(
        (episode) => episode.symptoms.any(
          (symptom) => symptom.entries.any(
            (entry) => _key(entry.recordedAt.toLocal()) == _key(date),
          ),
        ),
      )
      .toList();

  List<HealthEpisodeModel> _trackingEpisodesFor(DateTime date) {
    final episodes = [..._trackingRecordsOn(date)];
    if (_key(date) != _key(DateTime.now())) return episodes;

    for (final episode in _healthEpisodes.where((item) => item.status == 'A')) {
      if (!episodes.any((item) => item.id.toString() == episode.id.toString())) {
        episodes.add(episode);
      }
    }
    return episodes;
  }

  List<HealthEpisodeModel> _pendingTrackingOn(DateTime date) => _healthEpisodes
      .where((episode) => episode.status == 'A')
      .where(
        (episode) => !_dateOnly(episode.startedAt.toLocal()).isAfter(
          _dateOnly(date),
        ),
      )
      .where(
        (episode) => episode.symptoms.any((symptom) {
          final firstObserved = symptom.firstObservedAt?.toLocal();
          return symptom.status == 'A' &&
              (symptom.isPrimary ||
                  firstObserved == null ||
                  !_dateOnly(firstObserved).isAfter(_dateOnly(date)));
        }),
      )
      .where(
        (episode) => !episode.symptoms.any(
          (symptom) => symptom.entries.any(
            (entry) => _key(entry.recordedAt.toLocal()) == _key(date),
          ),
        ),
      )
      .toList();

  Future<void> _openTracking(HealthEpisodeModel episode) async {
    final primary = episode.symptoms.where((item) => item.isPrimary);
    final name = primary.isNotEmpty
        ? primary.first.symptomName
        : episode.symptoms.isNotEmpty
        ? episode.symptoms.first.symptomName
        : 'อาการที่ติดตาม';
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => FollowUpScreen(
          episodeId: episode.id,
          symptomName: name,
          recordDate: _selectedDate,
        ),
      ),
    );
    if (mounted) await _load();
  }

  Future<void> _openTrackingList() async {
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => const HealthEpisodeListScreen()),
    );
    if (mounted) await _load();
  }

  Future<void> _choosePendingTracking(
    List<HealthEpisodeModel> episodes,
  ) async {
    if (episodes.length == 1) {
      await _openTracking(episodes.first);
      return;
    }
    final selected = await showModalBottomSheet<HealthEpisodeModel>(
      context: context,
      showDragHandle: true,
      builder: (sheetContext) => SafeArea(
        child: ListView(
          shrinkWrap: true,
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
          children: [
            Text('เลือกอาการที่ต้องการบันทึก', style: AppTextStyles.h3),
            const SizedBox(height: 10),
            ...episodes.map(
              (episode) => ListTile(
                leading: SymptomIcon(
                  iconName: _episodePrimarySymptom(episode)?.symptomIcon,
                  size: 26,
                  color: AppColors.primary,
                ),
                title: Text(
                  episode.symptoms.map((item) => item.symptomName).join(', '),
                ),
                trailing: const Icon(Icons.chevron_right_rounded),
                onTap: () => Navigator.pop(sheetContext, episode),
              ),
            ),
          ],
        ),
      ),
    );
    if (selected != null && mounted) await _openTracking(selected);
  }

  EpisodeSymptomModel? _episodePrimarySymptom(HealthEpisodeModel episode) {
    if (episode.symptoms.isEmpty) return null;
    for (final symptom in episode.symptoms) {
      if (symptom.isPrimary) return symptom;
    }
    return episode.symptoms.first;
  }

  @override
  Widget build(BuildContext context) {
    final records = _records[_key(_selectedDate)] ?? const [];
    final record = records.isEmpty ? null : records.first;
    final isToday = _key(_selectedDate) == _key(DateTime.now());
    final trackingRecords = _trackingEpisodesFor(_selectedDate);
    final pendingTracking = _pendingTrackingOn(_selectedDate);
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text('บันทึกสุขภาพ', style: AppTextStyles.h4),
        actions: [
          IconButton(
            onPressed: _openTrackingList,
            tooltip: 'ดูการติดตามทั้งหมด',
            icon: const Icon(Icons.monitor_heart_outlined),
          ),
          const SizedBox(width: 8),
        ],
        bottom: PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(
            height: 1,
            thickness: 1,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: SafeArea(
        child: !_hasLoaded
            ? const AppLoadingView(label: 'กำลังโหลดบันทึกสุขภาพ...')
            : Stack(
                children: [
                  Scrollbar(
                    controller: _mainScrollController,
                    child: RefreshIndicator(
                      onRefresh: _load,
                      child: GestureDetector(
                        behavior: HitTestBehavior.translucent,
                        onHorizontalDragEnd: _handleHorizontalSwipe,
                        child: AppContentWidth(
                          child: ListView(
                            controller: _mainScrollController,
                            physics: const AlwaysScrollableScrollPhysics(),
                            keyboardDismissBehavior:
                                ScrollViewKeyboardDismissBehavior.onDrag,
                            padding: const EdgeInsets.fromLTRB(16, 12, 16, 36),
                            children: [
                              _weekStrip(),
                              const SizedBox(height: 14),
                              _overviewCard(
                                isToday: isToday,
                                hasRecord: record != null,
                                recordCount: records.length,
                              ),
                              if (pendingTracking.isNotEmpty) ...[
                                const SizedBox(height: 12),
                                _trackingPromptCard(pendingTracking),
                              ],
                              const SizedBox(height: 20),
                              if (record == null || _changingStatus) ...[
                                Text(
                                  'วันนี้รู้สึกอย่างไร?',
                                  style: AppTextStyles.h3,
                                ),
                                const SizedBox(height: 6),
                                Text(
                                  'เลือกสถานะที่ใกล้เคียงกับความรู้สึกของคุณมากที่สุด',
                                  style: AppTextStyles.body2.copyWith(
                                    color: Theme.of(
                                      context,
                                    ).colorScheme.onSurfaceVariant,
                                  ),
                                ),
                                const SizedBox(height: 14),
                                Column(
                                  children: [
                                    _statusChoice(
                                      label: 'สบายดี',
                                      description: 'ไม่มีอาการผิดปกติ',
                                      icon:
                                          Icons.sentiment_satisfied_alt_rounded,
                                      color: AppColors.success,
                                      selected: false,
                                      onTap: () => _save(
                                        'well',
                                        recordId: _editingRecordId,
                                      ),
                                    ),
                                    const SizedBox(height: 10),
                                    _statusChoice(
                                      label: 'ปกติ',
                                      description: 'ไม่มีอะไรเปลี่ยน',
                                      icon: Icons.sentiment_neutral_rounded,
                                      color: Theme.of(context).colorScheme.primary,
                                      selected: false,
                                      onTap: () => _save(
                                        'normal',
                                        recordId: _editingRecordId,
                                      ),
                                    ),
                                    const SizedBox(height: 10),
                                    _statusChoice(
                                      label: 'ไม่ค่อยสบาย',
                                      description: 'มีอาการที่สังเกตได้',
                                      icon:
                                          Icons.sentiment_dissatisfied_rounded,
                                      color: AppColors.danger,
                                      selected: false,
                                      onTap: _recordUnwell,
                                    ),
                                  ],
                                ),
                                if (record != null) ...[
                                  const SizedBox(height: 12),
                                  TextButton(
                                    onPressed: () => setState(() {
                                      _changingStatus = false;
                                      _editingRecordId = null;
                                    }),
                                    child: const Text('ยกเลิก'),
                                  ),
                                ],
                              ] else ...[
                                Row(
                                  crossAxisAlignment: CrossAxisAlignment.center,
                                  children: [
                                    Expanded(
                                      child: Text(
                                        'รายการบันทึกของวันนี้',
                                        style: AppTextStyles.h3,
                                      ),
                                    ),
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 11,
                                        vertical: 6,
                                      ),
                                      decoration: BoxDecoration(
                                        color: Theme.of(
                                          context,
                                        ).colorScheme.surfaceContainerLow,
                                        borderRadius: BorderRadius.circular(
                                          999,
                                        ),
                                      ),
                                      child: Text(
                                        '${records.length + trackingRecords.length} รายการ',
                                        style: AppTextStyles.body3Bold.copyWith(
                                          color: AppColors.primary,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 14),
                                SizedBox(
                                  width: double.infinity,
                                  child: FilledButton.icon(
                                    onPressed: _saving
                                        ? null
                                        : () => setState(() {
                                            _editingRecordId = null;
                                            _changingStatus = true;
                                          }),
                                    icon: const Icon(
                                      Icons.note_add_outlined,
                                      size: 20,
                                    ),
                                    label: const Text('เพิ่มบันทึกสุขภาพ'),
                                  ),
                                ),
                                const SizedBox(height: 20),
                                Row(
                                  children: [
                                    Expanded(
                                      child: Text(
                                        'การติดตามอาการ',
                                        style: AppTextStyles.body1Bold,
                                      ),
                                    ),
                                    TextButton(
                                      onPressed: _openTrackingList,
                                      child: const Text('ดูทั้งหมด'),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                if (trackingRecords.isEmpty)
                                  Container(
                                    width: double.infinity,
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 16,
                                      vertical: 18,
                                    ),
                                    decoration: BoxDecoration(
                                      color: Theme.of(context)
                                          .colorScheme
                                          .surfaceContainerLow,
                                      borderRadius: BorderRadius.circular(16),
                                    ),
                                    child: Text(
                                      'วันนี้ยังไม่มีบันทึกการติดตามอาการ',
                                      textAlign: TextAlign.center,
                                      style: AppTextStyles.body2.copyWith(
                                        color: Theme.of(context)
                                            .colorScheme
                                            .onSurfaceVariant,
                                      ),
                                    ),
                                  ),
                                ...trackingRecords.map(
                                  (episode) => _trackingTodayCard(
                                    episode,
                                    _selectedDate,
                                  ),
                                ),
                                if (records.isNotEmpty) ...[
                                  const SizedBox(height: 8),
                                  Text(
                                    'บันทึกสุขภาพ',
                                    style: AppTextStyles.body1Bold,
                                  ),
                                  const SizedBox(height: 10),
                                ] else
                                  const SizedBox(height: 12),
                                ...records.map(_recordSummary),
                              ],
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                  if (_loading || _saving)
                    const Positioned(
                      top: 0,
                      left: 0,
                      right: 0,
                      child: LinearProgressIndicator(minHeight: 2),
                    ),
                ],
              ),
      ),
    );
  }

  Widget _recordSummary(DailyHealthRecordModel record) {
    final isWell = record.status == 'well';
    final isNormal = record.status == 'normal';
    final statusColor = isWell
        ? AppColors.success
        : isNormal
        ? Theme.of(context).colorScheme.primary
        : AppColors.danger;
    final recordKey = record.id.toString();
    final expanded = _expandedRecordIds.contains(recordKey);

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 12),
      constraints: const BoxConstraints(minHeight: 82),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Theme.of(context).colorScheme.outlineVariant,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Container(
                width: 52,
                height: 52,
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  isWell
                      ? Icons.sentiment_satisfied_alt_rounded
                      : isNormal
                      ? Icons.sentiment_neutral_rounded
                      : Icons.sentiment_dissatisfied_rounded,
                  color: statusColor,
                  size: 30,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: InkWell(
                  borderRadius: BorderRadius.circular(10),
                  onTap: () => setState(() {
                    expanded
                        ? _expandedRecordIds.remove(recordKey)
                        : _expandedRecordIds.add(recordKey);
                  }),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 6),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isWell
                              ? 'สบายดี'
                              : isNormal
                              ? 'ปกติ'
                              : 'ไม่ค่อยสบาย',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: AppTextStyles.h4.copyWith(color: statusColor),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          record.recordedAt == null
                              ? 'บันทึกสุขภาพ'
                              : 'บันทึกเวลา ${DateFormat('HH:mm').format(record.recordedAt!)} น.',
                          style: AppTextStyles.body3.copyWith(
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurfaceVariant,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              IconButton(
                tooltip: expanded ? 'ย่อรายละเอียด' : 'เปิดรายละเอียด',
                onPressed: () => setState(() {
                  expanded
                      ? _expandedRecordIds.remove(recordKey)
                      : _expandedRecordIds.add(recordKey);
                }),
                icon: AnimatedRotation(
                  turns: expanded ? 0.5 : 0,
                  duration: const Duration(milliseconds: 180),
                  child: Icon(
                    Icons.keyboard_arrow_down_rounded,
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                ),
              ),
              IconButton.filledTonal(
                tooltip: 'เปลี่ยนสถานะ',
                onPressed: _saving
                    ? null
                    : () => setState(() {
                        _editingRecordId = record.id;
                        _changingStatus = true;
                      }),
                icon: const Icon(Icons.edit_rounded, size: 20),
              ),
            ],
          ),
          if (expanded &&
              record.status == 'unwell' &&
              record.symptoms.isNotEmpty) ...[
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerLeft,
              child: Text(
                'อาการที่บันทึก (${record.symptoms.length})',
                style: AppTextStyles.body2Bold,
              ),
            ),
            const SizedBox(height: 8),
            ...record.symptoms.map(
              (symptom) => Container(
                margin: const EdgeInsets.only(bottom: 8),
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 10,
                ),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.surface,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(
                    color: Theme.of(context).colorScheme.outlineVariant,
                  ),
                ),
                child: Row(
                  children: [
                    SymptomIcon(
                      iconName: symptom.symptomImage,
                      size: 28,
                      color: statusColor,
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        symptom.symptomName,
                        style: AppTextStyles.body1Bold,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
          if (expanded && record.note?.isNotEmpty == true) ...[
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerLeft,
              child: Text(
                'รายละเอียดเพิ่มเติม',
                style: AppTextStyles.body2Bold,
              ),
            ),
            const SizedBox(height: 6),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surfaceContainer,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(
                  color: Theme.of(context).colorScheme.outlineVariant,
                ),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(
                    Icons.notes_rounded,
                    color: AppColors.primary,
                    size: 20,
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      record.note!,
                      style: AppTextStyles.body2.copyWith(height: 1.5),
                    ),
                  ),
                ],
              ),
            ),
          ],
          if (expanded) const SizedBox(height: 14),
          if (expanded && (isWell || isNormal))
            OutlinedButton.icon(
              onPressed: _saving
                  ? null
                  : () {
                      _editingRecordId = record.id;
                      isWell ? _recordWell() : _recordNormal();
                    },
              style: _summaryActionStyle(),
              icon: const Icon(Icons.notes_rounded, size: 20),
              label: const Text('รายละเอียดเพิ่มเติม'),
            )
          else if (expanded)
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _saving
                        ? null
                        : () {
                            _editingRecordId = record.id;
                            _recordUnwell();
                          },
                    style: _summaryActionStyle(),
                    icon: const Icon(Icons.edit_outlined, size: 20),
                    label: const Text('แก้ไขอาการ'),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _saving
                        ? null
                        : () {
                            _editingRecordId = record.id;
                            _editUnwellNote();
                          },
                    style: _summaryActionStyle(),
                    icon: const Icon(Icons.notes_rounded, size: 20),
                    label: const Text('รายละเอียดเพิ่มเติม'),
                  ),
                ),
              ],
            ),
        ],
      ),
    );
  }

  Widget _trackingTodayCard(HealthEpisodeModel episode, DateTime date) {
    final items = <({EpisodeSymptomModel symptom, FollowUpEntryModel entry})>[];

    for (final symptom in episode.symptoms) {
      for (final entry in symptom.entries.where(
        (item) => _key(item.recordedAt.toLocal()) == _key(date),
      )) {
        items.add((symptom: symptom, entry: entry));
      }
    }

    if (items.isEmpty) return _pendingTrackingCard(episode);

    items.sort((a, b) => b.entry.recordedAt.compareTo(a.entry.recordedAt));

    final latest = items.first;
    final episodeKey = episode.id.toString();
    final expanded = _expandedTrackingIds.contains(episodeKey);

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 12),
      constraints: const BoxConstraints(minHeight: 82),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Theme.of(context).colorScheme.outlineVariant,
        ),
      ),
      child: Column(
        children: [
          InkWell(
            borderRadius: BorderRadius.circular(14),
            onTap: () => setState(() {
              expanded
                  ? _expandedTrackingIds.remove(episodeKey)
                  : _expandedTrackingIds.add(episodeKey);
            }),
            child: Row(
              children: [
                Container(
                  width: 52,
                  height: 52,
                  decoration: BoxDecoration(
                    color: AppColors.primaryLight,
                    shape: BoxShape.circle,
                  ),
                  child: Center(
                    child: SizedBox.square(
                      dimension: 30,
                      child: SymptomIcon(
                        iconName: latest.symptom.symptomIcon,
                        color: AppColors.primary,
                        size: 30,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        latest.symptom.symptomName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: AppTextStyles.h4,
                      ),
                      const SizedBox(height: 3),
                      Text(
                        'ติดตามเวลา '
                        '${DateFormat('HH:mm').format(latest.entry.recordedAt.toLocal())} น.',
                        style: AppTextStyles.body3.copyWith(
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  tooltip: expanded ? 'ย่อรายละเอียด' : 'เปิดรายละเอียด',
                  onPressed: () => setState(() {
                    expanded
                        ? _expandedTrackingIds.remove(episodeKey)
                        : _expandedTrackingIds.add(episodeKey);
                  }),
                  icon: AnimatedRotation(
                    turns: expanded ? 0.5 : 0,
                    duration: const Duration(milliseconds: 180),
                    child: const Icon(Icons.keyboard_arrow_down_rounded),
                  ),
                ),
              ],
            ),
          ),
          AnimatedCrossFade(
            duration: const Duration(milliseconds: 180),
            crossFadeState: expanded
                ? CrossFadeState.showSecond
                : CrossFadeState.showFirst,
            firstChild: const SizedBox(width: double.infinity),
            secondChild: Padding(
              padding: const EdgeInsets.only(top: 16),
              child: Column(
                children: [
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.surfaceContainerLow,
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.show_chart_rounded,
                          color: AppColors.primary,
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            '${latest.entry.severity == null ? 'บันทึกการติดตามล่าสุดแล้ว' : 'ระดับอาการล่าสุด ${latest.entry.severity}/10'}'
                            '${items.length > 1 ? ' • ${items.length} รายการวันนี้' : ''}',
                            style: AppTextStyles.body2Bold,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  OutlinedButton.icon(
                    onPressed: () => _openTracking(episode),
                    style: _summaryActionStyle(),
                    icon: const Icon(Icons.edit_outlined, size: 20),
                    label: const Text('แก้ไขการติดตาม'),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _pendingTrackingCard(HealthEpisodeModel episode) {
    final primary = episode.symptoms.where((item) => item.isPrimary);
    final displayedSymptom = primary.isNotEmpty
        ? primary.first
        : episode.symptoms.isNotEmpty
        ? episode.symptoms.first
        : null;
    final name = displayedSymptom?.symptomName ?? 'อาการที่กำลังติดตาม';

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 12),
      constraints: const BoxConstraints(minHeight: 82),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Theme.of(context).colorScheme.outlineVariant,
        ),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: () => _openTracking(episode),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: Row(
              children: [
                Container(
                  width: 52,
                  height: 52,
                  decoration: const BoxDecoration(
                    color: AppColors.primaryLight,
                    shape: BoxShape.circle,
                  ),
                  child: Center(
                    child: SizedBox.square(
                      dimension: 30,
                      child: SymptomIcon(
                        iconName: displayedSymptom?.symptomIcon,
                        color: AppColors.primary,
                        size: 30,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        name,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: AppTextStyles.h4,
                      ),
                      const SizedBox(height: 3),
                      Text(
                        'ยังไม่ได้บันทึกการติดตามวันนี้',
                        style: AppTextStyles.body3.copyWith(
                          color: Theme.of(
                            context,
                          ).colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ),
                ),
                const Icon(Icons.chevron_right_rounded),
              ],
            ),
          ),
        ),
      ),
    );
  }

  ButtonStyle _summaryActionStyle() => OutlinedButton.styleFrom(
    minimumSize: const Size.fromHeight(52),
    padding: const EdgeInsets.symmetric(horizontal: 12),
    foregroundColor: Theme.of(context).colorScheme.primary,
    side: BorderSide(color: Theme.of(context).colorScheme.primary),
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
    textStyle: AppTextStyles.body2Bold,
  );

  Widget _overviewCard({
    required bool isToday,
    required bool hasRecord,
    required int recordCount,
  }) {
    final colorScheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: isDark
              ? const [Color(0xFF4B36B8), Color(0xFF243F91)]
              : const [AppColors.primaryMid, AppColors.primaryDark],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: colorScheme.primary.withValues(
              alpha: isDark ? 0.10 : 0.16,
            ),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Row(
      children: [
        Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            color: Theme.of(
              context,
            ).colorScheme.surface.withValues(alpha: 0.16),
            borderRadius: BorderRadius.circular(14),
          ),
          child: Icon(
            hasRecord ? Icons.check_rounded : Icons.favorite_outline_rounded,
            color: AppColors.white,
            size: 27,
          ),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                isToday ? 'สุขภาพของคุณวันนี้' : formatThaiDate(_selectedDate),
                style: AppTextStyles.body1Bold.copyWith(color: AppColors.white),
              ),
              const SizedBox(height: 3),
              Text(
                hasRecord
                    ? 'บันทึกแล้ว $recordCount รายการ'
                    : 'ยังไม่มีบันทึก แตะเลือกความรู้สึกด้านล่าง',
                style: AppTextStyles.body3.copyWith(
                  color: AppColors.white.withValues(alpha: 0.82),
                ),
              ),
            ],
          ),
        ),
      ],
      ),
    );
  }

  Widget _trackingPromptCard(List<HealthEpisodeModel> episodes) {
    final count = episodes.length;
    final isToday = _key(_selectedDate) == _key(DateTime.now());
    final periodLabel = isToday ? 'วันนี้' : 'ย้อนหลัง';
    final colorScheme = Theme.of(context).colorScheme;
    return Semantics(
      button: true,
      label: 'ติดตามอาการ$periodLabel $count รายการ',
      child: Material(
        color: colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: () => _choosePendingTracking(episodes),
          child: Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: colorScheme.outlineVariant),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: AppColors.primaryLight,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(
                    Icons.monitor_heart_outlined,
                    color: AppColors.primary,
                    size: 22,
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'ยังไม่ได้ติดตามอาการ$periodLabel $count รายการ',
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 5),
                      Text(
                        'บันทึกอาการประจำวันเพื่อให้ข้อมูลการติดตามต่อเนื่อง',
                        maxLines: 4,
                        overflow: TextOverflow.ellipsis,
                        style: AppTextStyles.body2.copyWith(
                          color: colorScheme.onSurfaceVariant,
                        ),
                      ),
                      const SizedBox(height: 10),
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            'ติดตามอาการ',
                            style: AppTextStyles.body2Bold.copyWith(
                              color: AppColors.primary,
                            ),
                          ),
                          const SizedBox(width: 4),
                          const Icon(
                            Icons.arrow_forward_rounded,
                            size: 18,
                            color: AppColors.primary,
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _weekStrip() => Container(
    decoration: BoxDecoration(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(24),
      boxShadow: [
        BoxShadow(
          color: Theme.of(
            context,
          ).colorScheme.onSurface.withValues(alpha: 0.05),
          blurRadius: 18,
          offset: const Offset(0, 6),
        ),
      ],
    ),
    padding: const EdgeInsets.fromLTRB(8, 8, 8, 12),
    child: Column(
      children: [
        Row(
          children: [
            IconButton(
              tooltip: 'สัปดาห์ก่อนหน้า',
              onPressed: _loading ? null : () => _changeWeek(-1),
              icon: const Icon(Icons.chevron_left_rounded),
            ),
            Expanded(
              child: Text(
                '${formatThaiDate(_weekStart)} – '
                '${formatThaiDate(_weekStart.add(const Duration(days: 6)))}',
                textAlign: TextAlign.center,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: AppTextStyles.body2Bold,
              ),
            ),
            if (!_isToday)
              TextButton(
                onPressed: _loading || _saving ? null : _goToToday,
                style: TextButton.styleFrom(
                  padding: const EdgeInsets.symmetric(horizontal: 8),
                  minimumSize: const Size(0, 40),
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
                child: const Text('วันนี้'),
              ),
            IconButton(
              tooltip: 'เลือกวันที่จากปฏิทิน',
              onPressed: _loading ? null : _openCalendar,
              icon: const Icon(Icons.calendar_month_outlined),
            ),
            IconButton(
              tooltip: 'สัปดาห์ถัดไป',
              onPressed: _loading || _isCurrentWeek
                  ? null
                  : () => _changeWeek(1),
              icon: const Icon(Icons.chevron_right_rounded),
            ),
          ],
        ),
        Row(
          children: List.generate(7, (index) {
            final date = _weekStart.add(Duration(days: index));
            final isFuture = date.isAfter(_dateOnly(DateTime.now()));
            final selected = _key(date) == _key(_selectedDate);
            final dayRecords = _records[_key(date)] ?? const [];
            final record = dayRecords.isEmpty ? null : dayRecords.first;
            final statusIcon = record?.status == 'unwell'
                ? Icons.sentiment_dissatisfied_rounded
                : record?.status == 'well'
                ? Icons.sentiment_satisfied_alt_rounded
                : record?.status == 'normal'
                ? Icons.sentiment_neutral_rounded
                : null;
            final hasTracking = _trackingRecordsOn(date).isNotEmpty;
            return Expanded(
              child: Semantics(
                button: true,
                enabled: !isFuture,
                label: isFuture
                    ? '${date.day} ยังไม่สามารถเลือกวันในอนาคตได้'
                    : '${date.day} เลือกวันที่',
                child: AnimatedOpacity(
                  duration: const Duration(milliseconds: 160),
                  opacity: isFuture ? 0.35 : 1,
                  child: InkWell(
                    borderRadius: BorderRadius.circular(12),
                    onTap: isFuture
                        ? null
                        : () => setState(() {
                            _selectedDate = date;
                            _changingStatus = false;
                          }),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Column(
                        children: [
                      Text(
                        _weekdays[index],
                        style: AppTextStyles.body3Bold.copyWith(
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                      ),
                      const SizedBox(height: 5),
                      Container(
                        width: 34,
                        height: 34,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: selected
                              ? Theme.of(context).colorScheme.primary
                              : Colors.transparent,
                          shape: BoxShape.circle,
                        ),
                        child: Text(
                          '${date.day}',
                          style: AppTextStyles.body2.copyWith(
                            color: isFuture
                                ? Theme.of(context).colorScheme.onSurfaceVariant
                                : selected
                                ? Theme.of(context).colorScheme.onPrimary
                                : Theme.of(context).colorScheme.onSurface,
                            fontWeight: selected
                                ? FontWeight.w700
                                : FontWeight.w400,
                          ),
                        ),
                      ),
                          SizedBox(
                            height: 18,
                            child: isFuture
                                ? const Icon(Icons.lock_outline_rounded, size: 14)
                                : Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      if (statusIcon != null)
                                        Icon(
                                          statusIcon,
                                          size: 18,
                                          color: record?.status == 'unwell'
                                              ? AppColors.danger
                                              : record?.status == 'normal'
                                              ? Theme.of(
                                                  context,
                                                ).colorScheme.primary
                                              : AppColors.success,
                                        ),
                                      if (statusIcon != null && hasTracking)
                                        const SizedBox(width: 2),
                                      if (hasTracking)
                                        Icon(
                                          Icons.monitor_heart_outlined,
                                          size: 16,
                                          color: Theme.of(
                                            context,
                                          ).colorScheme.primary,
                                        ),
                                    ],
                                  ),
                          ),
                        ],
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
  );

  Widget _statusChoice({
    required String label,
    required String description,
    required IconData icon,
    required Color color,
    required bool selected,
    required VoidCallback onTap,
  }) => Semantics(
    button: true,
    selected: selected,
    label: label,
    child: InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: _saving || selected ? null : onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        constraints: const BoxConstraints(minHeight: 82),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: selected
              ? color.withValues(alpha: 0.1)
              : Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: selected
                ? color
                : Theme.of(context).colorScheme.outlineVariant,
            width: selected ? 2 : 1,
          ),
        ),
        child: Row(
          children: [
            Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                color: color.withValues(alpha: selected ? 0.2 : 0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 30, color: color),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label, style: AppTextStyles.body1Bold),
                  const SizedBox(height: 2),
                  Text(
                    description,
                    style: AppTextStyles.body3.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            ),
            Icon(
              selected
                  ? Icons.check_circle_rounded
                  : Icons.chevron_right_rounded,
              color: selected
                  ? color
                  : Theme.of(context).colorScheme.onSurfaceVariant,
              size: 24,
            ),
          ],
        ),
      ),
    ),
  );
}

class _NoteEditorScreen extends StatefulWidget {
  final String title;
  final String hintText;
  final String initialText;
  final Color color;

  const _NoteEditorScreen({
    required this.title,
    required this.hintText,
    required this.initialText,
    required this.color,
  });

  @override
  State<_NoteEditorScreen> createState() => _NoteEditorScreenState();
}

class _NoteEditorScreenState extends State<_NoteEditorScreen> {
  late final TextEditingController _controller;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(text: widget.initialText);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: Theme.of(context).scaffoldBackgroundColor,
    appBar: AppBar(
      automaticallyImplyLeading: false,
      title: Text(widget.title, style: AppTextStyles.h4),
      centerTitle: true,
      actions: [
        IconButton(
          tooltip: 'ปิด',
          onPressed: () => Navigator.pop(context),
          icon: const Icon(Icons.close_rounded),
        ),
        const SizedBox(width: 8),
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
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'เขียนรายละเอียดเกี่ยวกับสุขภาพในวันนี้',
              style: AppTextStyles.body1Bold,
            ),
            const SizedBox(height: 6),
            Text(
              'บันทึกความรู้สึก อาการ ช่วงเวลาที่เกิด หรือสิ่งที่สังเกตได้ (ไม่บังคับ)',
              style: AppTextStyles.body2.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
            const SizedBox(height: 16),
            Expanded(
              child: TextField(
                controller: _controller,
                autofocus: true,
                expands: true,
                minLines: null,
                maxLines: null,
                textAlignVertical: TextAlignVertical.top,
                decoration: InputDecoration(
                  labelText: 'รายละเอียดที่ต้องการบันทึก',
                  hintText: widget.hintText,
                  alignLabelWithHint: true,
                ),
              ),
            ),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: () => Navigator.pop(context, _controller.text.trim()),
              style: FilledButton.styleFrom(
                backgroundColor: widget.color,
                minimumSize: const Size.fromHeight(52),
              ),
              child: const Text('บันทึก'),
            ),
          ],
        ),
      ),
    ),
  );
}

class _SymptomSelectionScreen extends StatefulWidget {
  final SymptomRepository repository;
  final Set<String> initialSelectedIds;
  final List<HealthEpisodeModel> activeEpisodes;

  const _SymptomSelectionScreen({
    required this.repository,
    required this.initialSelectedIds,
    required this.activeEpisodes,
  });

  @override
  State<_SymptomSelectionScreen> createState() =>
      _SymptomSelectionScreenState();
}

class _SymptomSelectionScreenState extends State<_SymptomSelectionScreen> {
  late final Set<String> _selectedIds;
  final _searchController = TextEditingController();
  List<SymptomModel> _symptoms = [];
  String _search = '';
  bool _loading = true;
  String? _error;
  bool _showSelectionError = false;

  @override
  void initState() {
    super.initState();
    _selectedIds = {...widget.initialSelectedIds};
    _loadSymptoms();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadSymptoms() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final symptoms = await widget.repository.getSymptoms(
        status: '1',
        sort: 'popular',
      );
      if (!mounted) return;
      setState(() => _symptoms = symptoms);
    } catch (_) {
      if (mounted) setState(() => _error = 'ไม่สามารถโหลดรายการอาการได้');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  List<SymptomModel> get _filteredSymptoms {
    final query = _search.trim();
    if (query.isEmpty) return _symptoms;
    return _symptoms.where((symptom) {
      final searchable = [
        symptom.symptomId,
        symptom.symptomName,
        symptom.symptomNameEn ?? '',
      ].join(' ');
      return fuzzyContains(searchable, query);
    }).toList();
  }

  void _toggle(String id) {
    setState(() {
      _selectedIds.contains(id)
          ? _selectedIds.remove(id)
          : _selectedIds.add(id);
      if (_selectedIds.isNotEmpty) _showSelectionError = false;
    });
  }

  void _submit() {
    if (_selectedIds.isEmpty) {
      setState(() => _showSelectionError = true);
      return;
    }
    final orderedIds = _symptoms
        .where((symptom) => _selectedIds.contains(symptom.symptomId))
        .map((symptom) => symptom.symptomId)
        .toList();
    final selectedEpisodeIds = widget.activeEpisodes
        .where(
          (episode) => episode.symptoms.any(
            (symptom) =>
                symptom.symptomId != null &&
                _selectedIds.contains(symptom.symptomId),
          ),
        )
        .map((episode) => episode.id)
        .toList();
    Navigator.pop(
      context,
      _UnwellSelection(
        symptomIds: orderedIds,
        healthEpisodeIds: selectedEpisodeIds,
      ),
    );
  }

  Widget _symptomCard(SymptomModel symptom) {
    final selected = _selectedIds.contains(symptom.symptomId);
    return Semantics(
      button: true,
      selected: selected,
      label: symptom.symptomName,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => _toggle(symptom.symptomId),
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
                    _symptomIconBox(symptom, selected),
                    const SizedBox(height: 7),
                    Text(
                      symptom.symptomName,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                      style: AppTextStyles.body3Bold,
                    ),
                  ],
                ),
              ),
              Positioned(
                right: 0,
                top: 0,
                child: _selectionIndicator(selected),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _symptomListTile(SymptomModel symptom) {
    final selected = _selectedIds.contains(symptom.symptomId);
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => _toggle(symptom.symptomId),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
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
          child: Row(
            children: [
              _symptomIconBox(symptom, selected),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  symptom.symptomName,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: AppTextStyles.body2Bold,
                ),
              ),
              const SizedBox(width: 12),
              _selectionIndicator(selected),
            ],
          ),
        ),
      ),
    );
  }

  Widget _symptomIconBox(SymptomModel symptom, bool selected) =>
      AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        width: 44,
        height: 44,
        decoration: BoxDecoration(
          color: selected
              ? AppColors.primary.withValues(alpha: 0.10)
              : Theme.of(context).colorScheme.surfaceContainerLow,
          borderRadius: BorderRadius.circular(14),
        ),
        alignment: Alignment.center,
        child: SymptomIcon(
          iconName: symptom.symptomImage ?? symptom.category?.icon,
          size: 24,
          color: selected
              ? AppColors.primary
              : Theme.of(context).colorScheme.onSurfaceVariant,
        ),
      );

  Widget _selectionIndicator(bool selected) => AnimatedContainer(
    duration: const Duration(milliseconds: 160),
    width: 20,
    height: 20,
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
        ? const Icon(Icons.check_rounded, size: 13, color: AppColors.white)
        : null,
  );

  @override
  Widget build(BuildContext context) {
    final filtered = _filteredSymptoms;
    final popular = _symptoms.take(16).toList();
    final popularIds = popular.map((symptom) => symptom.symptomId).toSet();
    final otherSymptoms = _symptoms
        .where((symptom) => !popularIds.contains(symptom.symptomId))
        .toList();

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        automaticallyImplyLeading: false,
        title: Text('เลือกอาการ', style: AppTextStyles.h4),
        centerTitle: true,
        actions: [
          IconButton(
            tooltip: 'ปิด',
            onPressed: () => Navigator.pop(context),
            icon: const Icon(Icons.close_rounded),
          ),
          const SizedBox(width: 8),
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
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextField(
                controller: _searchController,
                onChanged: (value) => setState(() => _search = value),
                decoration: const InputDecoration(
                  hintText: 'ค้นหาอาการ',
                  prefixIcon: Icon(Icons.search_rounded),
                ),
              ),
              if (_showSelectionError) ...[
                const SizedBox(height: 8),
                Text(
                  'กรุณาเลือกอย่างน้อย 1 อาการ',
                  style: AppTextStyles.body3.copyWith(color: AppColors.primary),
                ),
              ],
              const SizedBox(height: 10),
              Expanded(
                child: _loading
                    ? const AppLoadingView(label: 'กำลังโหลดข้อมูล...')
                    : _error != null
                    ? AppMessageView.error(
                        message: _error!,
                        onAction: _loadSymptoms,
                      )
                    : filtered.isEmpty
                    ? const AppMessageView.empty(
                        title: 'ไม่พบอาการที่ค้นหา',
                        message: 'ลองตรวจคำสะกดหรือใช้คำค้นหาที่สั้นลง',
                      )
                    : _search.trim().isNotEmpty
                    ? ListView.builder(
                        keyboardDismissBehavior:
                            ScrollViewKeyboardDismissBehavior.onDrag,
                        itemCount: filtered.length,
                        itemBuilder: (context, index) =>
                            _symptomListTile(filtered[index]),
                      )
                    : CustomScrollView(
                        slivers: [
                          SliverToBoxAdapter(
                            child: Padding(
                              padding: const EdgeInsets.only(bottom: 12),
                              child: Text(
                                'อาการที่พบบ่อย',
                                style: AppTextStyles.body1Bold,
                              ),
                            ),
                          ),
                          SliverGrid(
                            delegate: SliverChildBuilderDelegate(
                              (context, index) => _symptomCard(popular[index]),
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
                          if (otherSymptoms.isNotEmpty) ...[
                            SliverToBoxAdapter(
                              child: Padding(
                                padding: const EdgeInsets.fromLTRB(0, 20, 0, 8),
                                child: Text(
                                  'อาการอื่นๆ',
                                  style: AppTextStyles.body1Bold,
                                ),
                              ),
                            ),
                            SliverList.builder(
                              itemCount: otherSymptoms.length,
                              itemBuilder: (context, index) =>
                                  _symptomListTile(otherSymptoms[index]),
                            ),
                          ],
                        ],
                      ),
              ),
              const SizedBox(height: 12),
              FilledButton(
                onPressed: _loading || _error != null ? null : _submit,
                style: FilledButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  minimumSize: const Size.fromHeight(52),
                ),
                child: Text(
                  _selectedIds.isEmpty
                      ? 'เลือกอาการ'
                      : 'เลือกอาการ ${_selectedIds.length} รายการ',
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _UnwellSelection {
  final List<String> symptomIds;
  final List<dynamic> healthEpisodeIds;

  const _UnwellSelection({
    required this.symptomIds,
    required this.healthEpisodeIds,
  });
}

class _TrackingDestination {
  final dynamic healthEpisodeId;

  const _TrackingDestination({this.healthEpisodeId});
}

class DailyHealthCalendarScreen extends StatefulWidget {
  final DateTime selectedDate;

  const DailyHealthCalendarScreen({super.key, required this.selectedDate});

  @override
  State<DailyHealthCalendarScreen> createState() =>
      _DailyHealthCalendarScreenState();
}

class _DailyHealthCalendarScreenState extends State<DailyHealthCalendarScreen> {
  static const _weekdays = ['จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส', 'อา'];
  static const _months = thaiAbbreviatedMonths;

  List<DateTime> _visibleMonths = [];
  late DateTime _selectedDate;
  late int _selectedYear;
  final ScrollController _scrollController = ScrollController();
  Map<String, DailyHealthRecordModel> _records = {};
  List<HealthEpisodeModel> _healthEpisodes = const [];
  bool _loading = true;
  int _loadGeneration = 0;

  DateTime _dateOnly(DateTime date) =>
      DateTime(date.year, date.month, date.day);
  String _key(DateTime date) => DateFormat('yyyy-MM-dd').format(date);

  @override
  void initState() {
    super.initState();
    _selectedDate = _dateOnly(widget.selectedDate);
    _selectedYear = _selectedDate.year;
    _setVisibleMonths();
    _load();
    _scrollToLatestMonth();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    final generation = ++_loadGeneration;
    setState(() => _loading = true);
    try {
      final now = _dateOnly(DateTime.now());
      final endOfYear = DateTime(_selectedYear, 12, 31);
      final repository = context.read<PersonalHealthRepository>();
      final from = _key(DateTime(_selectedYear));
      final to = _key(endOfYear.isAfter(now) ? now : endOfYear);
      final results = await Future.wait([
        repository.dailyRecords(from: from, to: to),
        repository.healthEpisodes(from: from, to: to),
      ]);
      final records = results[0] as List<DailyHealthRecordModel>;
      final episodes = results[1] as List<HealthEpisodeModel>;
      if (!mounted || generation != _loadGeneration) return;
      final latestByDay = <String, DailyHealthRecordModel>{};
      for (final record in records) {
        latestByDay.putIfAbsent(_key(record.recordedOn), () => record);
      }
      setState(() {
        _records = latestByDay;
        _healthEpisodes = episodes;
      });
    } catch (_) {
      if (!mounted || generation != _loadGeneration) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('ไม่สามารถโหลดปฏิทินสุขภาพได้')),
      );
    } finally {
      if (mounted && generation == _loadGeneration) {
        setState(() => _loading = false);
      }
    }
  }

  void _select(DateTime date) {
    setState(() => _selectedDate = date);
    Navigator.pop(context, date);
  }

  void _setVisibleMonths() {
    final now = DateTime.now();
    final monthCount = _selectedYear == now.year ? now.month : 12;
    _visibleMonths = List.generate(
      monthCount,
      (index) => DateTime(_selectedYear, index + 1),
    );
  }

  void _scrollToLatestMonth() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.jumpTo(_scrollController.position.maxScrollExtent);
      }
    });
  }

  Future<void> _changeYear(int? year) async {
    if (year == null || year == _selectedYear) return;
    setState(() {
      _selectedYear = year;
      _setVisibleMonths();
      _records = {};
    });
    await _load();
    _scrollToLatestMonth();
  }

  int get _firstSelectableYear =>
      widget.selectedDate.year < 2020 ? widget.selectedDate.year : 2020;

  Future<void> _shiftYear(int offset) async {
    final nextYear = _selectedYear + offset;
    final currentYear = DateTime.now().year;
    if (nextYear < _firstSelectableYear || nextYear > currentYear) return;
    await _changeYear(nextYear);
  }

  Future<void> _pickYear() async {
    final currentYear = DateTime.now().year;
    final years = List.generate(
      currentYear - _firstSelectableYear + 1,
      (index) => currentYear - index,
    );
    final year = await showDialog<int>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('เลือกปี'),
        content: SizedBox(
          width: double.maxFinite,
          height: 280,
          child: GridView.count(
            crossAxisCount: 3,
            mainAxisSpacing: 8,
            crossAxisSpacing: 8,
            childAspectRatio: 1.7,
            children: years.map((year) {
              final selected = year == _selectedYear;
              return selected
                  ? FilledButton(
                      onPressed: () => Navigator.pop(dialogContext, year),
                      child: Text('${year + 543}'),
                    )
                  : TextButton(
                      onPressed: () => Navigator.pop(dialogContext, year),
                      child: Text('${year + 543}'),
                    );
            }).toList(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('ยกเลิก'),
          ),
        ],
      ),
    );
    await _changeYear(year);
  }

  void _selectToday() => Navigator.pop(context, _dateOnly(DateTime.now()));

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: Theme.of(context).scaffoldBackgroundColor,
    appBar: AppBar(
      title: Text('เลือกวันที่', style: AppTextStyles.h4),
      actions: [
        TextButton(onPressed: _selectToday, child: const Text('วันนี้')),
        const SizedBox(width: 8),
      ],
      bottom: PreferredSize(
        preferredSize: Size.fromHeight(1),
        child: Divider(
          height: 1,
          color: Theme.of(context).colorScheme.outlineVariant,
        ),
      ),
    ),
    body: Stack(
      children: [
        Column(
          children: [
            _yearSelector(),
            _calendarLegend(),
            Expanded(
              child: ListView.separated(
                controller: _scrollController,
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
                itemCount: _visibleMonths.length,
                separatorBuilder: (_, _) => const SizedBox(height: 16),
                itemBuilder: (context, index) =>
                    _monthCard(_visibleMonths[index]),
              ),
            ),
          ],
        ),
        if (_loading)
          const Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: LinearProgressIndicator(minHeight: 2),
          ),
      ],
    ),
  );

  Widget _yearSelector() {
    final currentYear = DateTime.now().year;
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
      child: Material(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          height: 56,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: Theme.of(context).colorScheme.outlineVariant,
            ),
          ),
          child: Row(
            children: [
              IconButton(
                tooltip: 'ปีก่อนหน้า',
                onPressed: !_loading && _selectedYear > _firstSelectableYear
                    ? () => _shiftYear(-1)
                    : null,
                icon: const Icon(Icons.chevron_left_rounded),
              ),
              Expanded(
                child: InkWell(
                  onTap: _loading ? null : _pickYear,
                  borderRadius: BorderRadius.circular(12),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'ปี ${_selectedYear + 543}',
                        style: AppTextStyles.body1Bold,
                      ),
                      const SizedBox(width: 10),
                      const Icon(Icons.calendar_month_outlined),
                    ],
                  ),
                ),
              ),
              IconButton(
                tooltip: 'ปีถัดไป',
                onPressed: !_loading && _selectedYear < currentYear
                    ? () => _shiftYear(1)
                    : null,
                icon: const Icon(Icons.chevron_right_rounded),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _calendarLegend() => Padding(
    padding: const EdgeInsets.fromLTRB(20, 0, 20, 2),
    child: Wrap(
      spacing: 16,
      runSpacing: 6,
      children: [
        _legendItem(
          Icons.sentiment_satisfied_alt_rounded,
          AppColors.success,
          'บันทึกสุขภาพ',
        ),
        _legendItem(
          Icons.monitor_heart_outlined,
          AppColors.primary,
          'บันทึกติดตามอาการ',
        ),
      ],
    ),
  );

  Widget _legendItem(IconData icon, Color color, String label) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Icon(icon, size: 17, color: color),
      const SizedBox(width: 5),
      Text(
        label,
        style: AppTextStyles.body3.copyWith(
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
      ),
    ],
  );

  bool _hasTrackingOn(DateTime date) => _healthEpisodes.any(
    (episode) => episode.symptoms.any(
      (symptom) => symptom.entries.any(
        (entry) => _key(entry.recordedAt.toLocal()) == _key(date),
      ),
    ),
  );

  Widget _monthCard(DateTime month) {
    final daysInMonth = DateTime(month.year, month.month + 1, 0).day;
    final leadingEmptyCells = month.weekday - 1;
    final cellCount = ((leadingEmptyCells + daysInMonth + 6) ~/ 7) * 7;

    return Container(
      padding: const EdgeInsets.fromLTRB(12, 18, 12, 14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 6),
            child: Text(
              '${_months[month.month - 1]} ${month.year + 543}',
              style: AppTextStyles.body1Bold,
            ),
          ),
          const SizedBox(height: 14),
          Row(
            children: List.generate(
              7,
              (index) => Expanded(
                child: Text(
                  _weekdays[index],
                  textAlign: TextAlign.center,
                  style: AppTextStyles.body3Bold.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                ),
              ),
            ),
          ),
          const Divider(height: 18),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 7,
              mainAxisExtent: 58,
            ),
            itemCount: cellCount,
            itemBuilder: (context, index) {
              final day = index - leadingEmptyCells + 1;
              if (day < 1 || day > daysInMonth) {
                return const SizedBox.shrink();
              }
              return _dayCell(DateTime(month.year, month.month, day));
            },
          ),
        ],
      ),
    );
  }

  Widget _dayCell(DateTime date) {
    final isFuture = date.isAfter(_dateOnly(DateTime.now()));
    final selected = _key(date) == _key(_selectedDate);
    final record = _records[_key(date)];
    final statusIcon = record?.status == 'unwell'
        ? Icons.sentiment_dissatisfied_rounded
        : record?.status == 'well'
        ? Icons.sentiment_satisfied_alt_rounded
        : record?.status == 'normal'
        ? Icons.sentiment_neutral_rounded
        : null;
    final hasTracking = _hasTrackingOn(date);

    return InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: isFuture ? null : () => _select(date),
      child: Container(
        margin: const EdgeInsets.all(2),
        decoration: BoxDecoration(
          color: selected ? AppColors.primaryLight : Colors.transparent,
          borderRadius: BorderRadius.circular(12),
          border: selected ? Border.all(color: AppColors.primary) : null,
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              '${date.day}',
              style: AppTextStyles.body2.copyWith(
                color: isFuture
                    ? Theme.of(context).colorScheme.onSurfaceVariant
                    : selected
                    ? AppColors.primary
                    : Theme.of(context).colorScheme.onSurface,
                fontWeight: selected ? FontWeight.w700 : FontWeight.w400,
              ),
            ),
            SizedBox(
              height: 18,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  if (statusIcon != null)
                    Icon(
                      statusIcon,
                      size: 18,
                      color: record?.status == 'unwell'
                          ? AppColors.danger
                          : record?.status == 'normal'
                          ? AppColors.primary
                          : AppColors.success,
                    ),
                  if (statusIcon != null && hasTracking)
                    const SizedBox(width: 2),
                  if (hasTracking)
                    const Icon(
                      Icons.monitor_heart_outlined,
                      size: 16,
                      color: AppColors.primary,
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
