import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
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
import '../../../shared/widgets/symptom_icon.dart';
import 'follow_up_screen.dart';
import 'health_episode_list_screen.dart';

class DailyHealthRecordScreen extends StatefulWidget {
  const DailyHealthRecordScreen({super.key});

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
  final _mainScrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _selectedDate = _dateOnly(DateTime.now());
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

  Future<void> _save(
    String status, {
    String? note,
    List<String> symptomIds = const [],
    dynamic recordId,
  }) async {
    if (_selectedDate.isAfter(_dateOnly(DateTime.now()))) return;
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
          );
      if (!mounted) return;
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
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('บันทึกไม่สำเร็จ กรุณาลองอีกครั้ง')),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _recordUnwell() async {
    final existing = _editingRecord;
    final editingUnwell = existing?.status == 'unwell';
    final symptomRepository = context.read<SymptomRepository>();
    final symptomIds = await Navigator.push<List<String>>(
      context,
      MaterialPageRoute(
        builder: (_) => _SymptomSelectionScreen(
          repository: symptomRepository,
          initialSelectedIds: editingUnwell
              ? existing!.symptoms.map((symptom) => symptom.symptomId).toSet()
              : {},
        ),
      ),
    );
    if (symptomIds != null) {
      await _save(
        'unwell',
        note: editingUnwell ? existing?.note : null,
        symptomIds: symptomIds,
        recordId: existing?.id,
      );
    }
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
          assessmentId: episode.sourceAssessmentId,
          symptomName: name,
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

  @override
  Widget build(BuildContext context) {
    final records = _records[_key(_selectedDate)] ?? const [];
    final record = records.isEmpty ? null : records.first;
    final trackingRecords = _trackingRecordsOn(_selectedDate);
    final isToday = _key(_selectedDate) == _key(DateTime.now());
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text('บันทึกสุขภาพ', style: AppTextStyles.h4),
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, thickness: 1, color: AppColors.border),
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
                        child: ListView(
                          controller: _mainScrollController,
                          physics: const AlwaysScrollableScrollPhysics(),
                          keyboardDismissBehavior:
                              ScrollViewKeyboardDismissBehavior.onDrag,
                          padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
                          children: [
                            _weekStrip(),
                            const SizedBox(height: 16),
                            Container(
                              padding: const EdgeInsets.all(20),
                              decoration: BoxDecoration(
                                gradient: const LinearGradient(
                                  colors: [
                                    AppColors.primary,
                                    AppColors.primaryMid,
                                  ],
                                  begin: Alignment.topLeft,
                                  end: Alignment.bottomRight,
                                ),
                                borderRadius: BorderRadius.circular(24),
                              ),
                              child: Row(
                                children: [
                                  Container(
                                    width: 52,
                                    height: 52,
                                    decoration: BoxDecoration(
                                      color: AppColors.white.withValues(
                                        alpha: 0.16,
                                      ),
                                      borderRadius: BorderRadius.circular(16),
                                    ),
                                    child: const Icon(
                                      Icons.favorite_rounded,
                                      color: AppColors.white,
                                      size: 28,
                                    ),
                                  ),
                                  const SizedBox(width: 14),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          isToday
                                              ? 'สุขภาพของคุณวันนี้'
                                              : 'บันทึกวันที่ ${formatThaiDate(_selectedDate)}',
                                          style: AppTextStyles.body1Bold
                                              .copyWith(color: AppColors.white),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          record == null
                                              ? 'ใช้เวลาไม่ถึงหนึ่งนาทีในการบันทึก'
                                              : 'บันทึกข้อมูลวันนี้เรียบร้อยแล้ว',
                                          style: AppTextStyles.body2.copyWith(
                                            color: AppColors.white.withValues(
                                              alpha: 0.82,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  if (record != null)
                                    const Icon(
                                      Icons.check_circle_rounded,
                                      color: AppColors.white,
                                    ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 24),
                            if (record == null || _changingStatus) ...[
                              Text(
                                'วันนี้รู้สึกอย่างไร?',
                                style: AppTextStyles.h3,
                              ),
                              const SizedBox(height: 6),
                              Text(
                                'เลือกสถานะที่ใกล้เคียงกับความรู้สึกของคุณมากที่สุด',
                                style: AppTextStyles.body2.copyWith(
                                  color: AppColors.textSecondary,
                                ),
                              ),
                              const SizedBox(height: 16),
                              LayoutBuilder(
                                builder: (context, constraints) {
                                  const spacing = 10.0;
                                  const columns = 3;
                                  final cardWidth =
                                      (constraints.maxWidth -
                                          (spacing * (columns - 1))) /
                                      columns;
                                  return Wrap(
                                    spacing: spacing,
                                    runSpacing: spacing,
                                    children: [
                                      SizedBox(
                                        width: cardWidth,
                                        child: _statusChoice(
                                          label: 'สบายดี',
                                          description: 'ไม่มีอาการผิดปกติ',
                                          icon: Icons
                                              .sentiment_satisfied_alt_rounded,
                                          color: AppColors.success,
                                          selected: false,
                                          onTap: () => _save(
                                            'well',
                                            recordId: _editingRecordId,
                                          ),
                                        ),
                                      ),
                                      SizedBox(
                                        width: cardWidth,
                                        child: _statusChoice(
                                          label: 'ปกติ',
                                          description: 'ไม่มีอะไรเปลี่ยน',
                                          icon: Icons.sentiment_neutral_rounded,
                                          color: AppColors.primary,
                                          selected: false,
                                          onTap: () => _save(
                                            'normal',
                                            recordId: _editingRecordId,
                                          ),
                                        ),
                                      ),
                                      SizedBox(
                                        width: cardWidth,
                                        child: _statusChoice(
                                          label: 'ไม่ค่อยสบาย',
                                          description: 'มีอาการที่สังเกตได้',
                                          icon: Icons
                                              .sentiment_dissatisfied_rounded,
                                          color: AppColors.danger,
                                          selected: false,
                                          onTap: _recordUnwell,
                                        ),
                                      ),
                                    ],
                                  );
                                },
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
                                      color: AppColors.surfacePrimary,
                                      borderRadius: BorderRadius.circular(999),
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
                              const SizedBox(height: 12),
                              Row(
                                children: [
                                  Expanded(
                                    child: OutlinedButton.icon(
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
                                      label: const Text('เพิ่มบันทึก'),
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: OutlinedButton.icon(
                                      onPressed: _openTrackingList,
                                      icon: const Icon(
                                        Icons.monitor_heart_outlined,
                                        size: 20,
                                      ),
                                      label: const Text('การติดตาม'),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              ...records.map(_recordSummary),
                              ...trackingRecords.map(
                                (episode) =>
                                    _trackingTodayCard(episode, _selectedDate),
                              ),
                            ],
                          ],
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
        ? AppColors.primary
        : AppColors.danger;

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Container(
                width: 58,
                height: 58,
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  isWell
                      ? Icons.sentiment_very_satisfied_rounded
                      : isNormal
                      ? Icons.sentiment_neutral_rounded
                      : Icons.sentiment_dissatisfied_rounded,
                  color: statusColor,
                  size: 34,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      isWell
                          ? 'สบายดี'
                          : isNormal
                          ? 'ปกติ'
                          : 'ไม่ค่อยสบาย',
                      style: AppTextStyles.h3.copyWith(color: statusColor),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      record.recordedAt == null
                          ? 'บันทึกสุขภาพ'
                          : 'บันทึกเวลา ${DateFormat('HH:mm').format(record.recordedAt!)} น.',
                      style: AppTextStyles.body3.copyWith(
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
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
          if (record.status == 'unwell' && record.symptoms.isNotEmpty) ...[
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
                  color: AppColors.surfaceElevated,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppColors.border),
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
          if (record.note?.isNotEmpty == true) ...[
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
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppColors.border),
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
          const SizedBox(height: 14),
          if (isWell || isNormal)
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
          else
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
    final items = <({String name, FollowUpEntryModel entry})>[];

    for (final symptom in episode.symptoms) {
      for (final entry in symptom.entries.where(
        (item) => _key(item.recordedAt.toLocal()) == _key(date),
      )) {
        items.add((name: symptom.symptomName, entry: entry));
      }
    }

    if (items.isEmpty) {
      return const SizedBox.shrink();
    }

    items.sort((a, b) => b.entry.recordedAt.compareTo(a.entry.recordedAt));

    final latest = items.first;

    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: () => _openTracking(episode),
      child: Container(
        width: double.infinity,
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.primary.withValues(alpha: 0.18)),
        ),
        child: Row(
          children: [
            Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(14),
              ),
              child: const Icon(
                Icons.monitor_heart_outlined,
                color: AppColors.primary,
                size: 25,
              ),
            ),

            const SizedBox(width: 12),

            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(latest.name, style: AppTextStyles.body1Bold),

                  const SizedBox(height: 3),

                  Text(
                    'ระดับอาการ ${latest.entry.severity}/10',
                    style: AppTextStyles.body3.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),

                  const SizedBox(height: 2),

                  Text(
                    'อัปเดตล่าสุด '
                    '${DateFormat('HH:mm').format(latest.entry.recordedAt.toLocal())} น.',
                    style: AppTextStyles.body3.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),

            const Icon(Icons.chevron_right_rounded, color: AppColors.textHint),
          ],
        ),
      ),
    );
  }

  ButtonStyle _summaryActionStyle() => OutlinedButton.styleFrom(
    minimumSize: const Size.fromHeight(52),
    padding: const EdgeInsets.symmetric(horizontal: 12),
    foregroundColor: AppColors.primary,
    side: const BorderSide(color: AppColors.primary),
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
    textStyle: AppTextStyles.body2Bold,
  );

  Widget _weekStrip() => Container(
    decoration: BoxDecoration(
      color: AppColors.surfaceElevated,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: AppColors.border),
    ),
    padding: const EdgeInsets.fromLTRB(6, 8, 6, 12),
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
                style: AppTextStyles.body2Bold,
              ),
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
                          color: AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 5),
                      Container(
                        width: 34,
                        height: 34,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: selected
                              ? AppColors.primary
                              : Colors.transparent,
                          shape: BoxShape.circle,
                        ),
                        child: Text(
                          '${date.day}',
                          style: AppTextStyles.body2.copyWith(
                            color: isFuture
                                ? AppColors.textHint
                                : selected
                                ? AppColors.white
                                : AppColors.textPrimary,
                            fontWeight: selected
                                ? FontWeight.w700
                                : FontWeight.w400,
                          ),
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
      borderRadius: BorderRadius.circular(22),
      onTap: _saving || selected ? null : onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        constraints: const BoxConstraints(minHeight: 188),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: selected
              ? color.withValues(alpha: 0.1)
              : AppColors.surfaceElevated,
          borderRadius: BorderRadius.circular(22),
          border: Border.all(
            color: selected ? color : AppColors.border,
            width: selected ? 2 : 1,
          ),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                color: color.withValues(alpha: selected ? 0.2 : 0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 38, color: color),
            ),
            const SizedBox(height: 12),
            Text(label, style: AppTextStyles.body1Bold),
            const SizedBox(height: 4),
            Text(
              description,
              textAlign: TextAlign.center,
              style: AppTextStyles.body3.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 10),
            Icon(
              selected
                  ? Icons.radio_button_checked_rounded
                  : Icons.radio_button_unchecked_rounded,
              color: selected ? color : AppColors.textHint,
              size: 22,
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
    backgroundColor: AppColors.background,
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
      bottom: const PreferredSize(
        preferredSize: Size.fromHeight(1),
        child: Divider(height: 1, color: AppColors.border),
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
                color: AppColors.textSecondary,
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

  const _SymptomSelectionScreen({
    required this.repository,
    required this.initialSelectedIds,
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
      final symptoms = await widget.repository.getSymptoms();
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
    Navigator.pop(context, orderedIds);
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _filteredSymptoms;
    final selected = _symptoms
        .where((symptom) => _selectedIds.contains(symptom.symptomId))
        .toList();

    return Scaffold(
      backgroundColor: AppColors.background,
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
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, color: AppColors.border),
        ),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'เลือกได้มากกว่า 1 อาการ',
                style: AppTextStyles.body2.copyWith(
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 14),
              TextField(
                controller: _searchController,
                onChanged: (value) => setState(() => _search = value),
                decoration: const InputDecoration(
                  hintText: 'ค้นหาอาการ',
                  prefixIcon: Icon(Icons.search_rounded),
                ),
              ),
              if (selected.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(
                  'อาการที่เลือก (${selected.length})',
                  style: AppTextStyles.body2Bold,
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: selected
                      .map(
                        (symptom) => InputChip(
                          label: Text(symptom.symptomName),
                          onDeleted: () => _toggle(symptom.symptomId),
                        ),
                      )
                      .toList(),
                ),
              ],
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
                    : LayoutBuilder(
                        builder: (context, constraints) {
                          final textScale =
                              MediaQuery.textScalerOf(context).scale(12) / 12;
                          final columns = textScale > 1.25
                              ? 2
                              : constraints.maxWidth < 360
                              ? 3
                              : 4;
                          return GridView.builder(
                            keyboardDismissBehavior:
                                ScrollViewKeyboardDismissBehavior.onDrag,
                            gridDelegate:
                                SliverGridDelegateWithFixedCrossAxisCount(
                                  crossAxisCount: columns,
                                  crossAxisSpacing: 8,
                                  mainAxisSpacing: 8,
                                  mainAxisExtent: textScale > 1.25 ? 112 : 96,
                                ),
                            itemCount: filtered.length,
                            itemBuilder: (context, index) {
                              final symptom = filtered[index];
                              final selected = _selectedIds.contains(
                                symptom.symptomId,
                              );
                              return Semantics(
                                button: true,
                                selected: selected,
                                label: symptom.symptomName,
                                child: InkWell(
                                  borderRadius: BorderRadius.circular(16),
                                  onTap: () => _toggle(symptom.symptomId),
                                  child: AnimatedContainer(
                                    duration: const Duration(milliseconds: 160),
                                    padding: const EdgeInsets.all(4),
                                    decoration: BoxDecoration(
                                      color: selected
                                          ? AppColors.primaryLight.withValues(
                                              alpha: 0.65,
                                            )
                                          : AppColors.surfaceElevated,
                                      borderRadius: BorderRadius.circular(16),
                                      border: Border.all(
                                        color: selected
                                            ? AppColors.primary
                                            : AppColors.border,
                                        width: selected ? 2 : 1,
                                      ),
                                    ),
                                    child: Stack(
                                      fit: StackFit.expand,
                                      children: [
                                        Column(
                                          mainAxisAlignment:
                                              MainAxisAlignment.center,
                                          children: [
                                            Container(
                                              width: 32,
                                              height: 32,
                                              decoration: BoxDecoration(
                                                color: selected
                                                    ? AppColors.primary
                                                          .withValues(
                                                            alpha: 0.14,
                                                          )
                                                    : AppColors.surface,
                                                shape: BoxShape.circle,
                                              ),
                                              alignment: Alignment.center,
                                              child: SymptomIcon(
                                                iconName: symptom.symptomImage,
                                                size: 20,
                                                color: selected
                                                    ? AppColors.primary
                                                    : AppColors.textSecondary,
                                              ),
                                            ),
                                            const SizedBox(height: 4),
                                            Text(
                                              symptom.symptomName,
                                              maxLines: 2,
                                              overflow: TextOverflow.ellipsis,
                                              textAlign: TextAlign.center,
                                              style: AppTextStyles.body3Bold,
                                            ),
                                          ],
                                        ),
                                        Positioned(
                                          top: 0,
                                          right: 0,
                                          child: Icon(
                                            selected
                                                ? Icons.check_circle_rounded
                                                : Icons
                                                      .radio_button_unchecked_rounded,
                                            color: selected
                                                ? AppColors.primary
                                                : AppColors.textHint,
                                            size: 16,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              );
                            },
                          );
                        },
                      ),
              ),
              const SizedBox(height: 12),
              FilledButton(
                onPressed: _loading || _error != null ? null : _submit,
                style: FilledButton.styleFrom(
                  backgroundColor: AppColors.primary,
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
      final records = await context
          .read<PersonalHealthRepository>()
          .dailyRecords(
            from: _key(DateTime(_selectedYear)),
            to: _key(endOfYear.isAfter(now) ? now : endOfYear),
          );
      if (!mounted || generation != _loadGeneration) return;
      final latestByDay = <String, DailyHealthRecordModel>{};
      for (final record in records) {
        latestByDay.putIfAbsent(_key(record.recordedOn), () => record);
      }
      setState(() => _records = latestByDay);
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

  void _selectToday() => Navigator.pop(context, _dateOnly(DateTime.now()));

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: AppColors.background,
    appBar: AppBar(
      title: Text('เลือกวันที่', style: AppTextStyles.h4),
      actions: [
        TextButton(onPressed: _selectToday, child: const Text('วันนี้')),
        const SizedBox(width: 8),
      ],
      bottom: const PreferredSize(
        preferredSize: Size.fromHeight(1),
        child: Divider(height: 1, color: AppColors.border),
      ),
    ),
    body: Stack(
      children: [
        Column(
          children: [
            _yearSelector(),
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
    final firstYear = _selectedYear < 2020 ? _selectedYear : 2020;
    final years = List.generate(
      currentYear - firstYear + 1,
      (index) => currentYear - index,
    );
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 16, 16, 12),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      decoration: BoxDecoration(
        color: AppColors.primaryLight.withValues(alpha: 0.55),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.28)),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<int>(
          value: _selectedYear,
          isExpanded: true,
          icon: const Icon(Icons.keyboard_arrow_down_rounded),
          iconEnabledColor: AppColors.primary,
          dropdownColor: AppColors.surfaceElevated,
          borderRadius: BorderRadius.circular(14),
          style: AppTextStyles.body1Bold.copyWith(color: AppColors.primary),
          onChanged: _loading ? null : _changeYear,
          items: years
              .map(
                (year) => DropdownMenuItem<int>(
                  value: year,
                  child: Text('ปี ${year + 543}'),
                ),
              )
              .toList(),
        ),
      ),
    );
  }

  Widget _monthCard(DateTime month) {
    final daysInMonth = DateTime(month.year, month.month + 1, 0).day;
    final leadingEmptyCells = month.weekday - 1;
    final cellCount = ((leadingEmptyCells + daysInMonth + 6) ~/ 7) * 7;

    return Container(
      padding: const EdgeInsets.fromLTRB(12, 18, 12, 14),
      decoration: BoxDecoration(
        color: AppColors.surfaceElevated,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.border),
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
                    color: AppColors.textSecondary,
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
                    ? AppColors.textHint
                    : selected
                    ? AppColors.primary
                    : AppColors.textPrimary,
                fontWeight: selected ? FontWeight.w700 : FontWeight.w400,
              ),
            ),
            SizedBox(
              height: 18,
              child: statusIcon == null
                  ? null
                  : Icon(
                      statusIcon,
                      size: 18,
                      color: record?.status == 'unwell'
                          ? AppColors.danger
                          : record?.status == 'normal'
                          ? AppColors.primary
                          : AppColors.success,
                    ),
            ),
          ],
        ),
      ),
    );
  }
}
