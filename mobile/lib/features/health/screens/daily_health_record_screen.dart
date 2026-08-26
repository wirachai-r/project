import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../data/models/daily_health_record_model.dart';
import '../../../data/repositories/personal_health_repository.dart';

class DailyHealthRecordScreen extends StatefulWidget {
  const DailyHealthRecordScreen({super.key});

  @override
  State<DailyHealthRecordScreen> createState() =>
      _DailyHealthRecordScreenState();
}

class _DailyHealthRecordScreenState extends State<DailyHealthRecordScreen> {
  static const _weekdays = ['จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส', 'อา'];
  late DateTime _selectedDate;
  Map<String, DailyHealthRecordModel> _records = {};
  bool _loading = true;
  bool _saving = false;
  bool _hasLoaded = false;
  int _loadGeneration = 0;

  @override
  void initState() {
    super.initState();
    _selectedDate = _dateOnly(DateTime.now());
    _load();
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
      final records = await context
          .read<PersonalHealthRepository>()
          .dailyRecords(from: _key(from), to: _key(to));
      if (!mounted || generation != _loadGeneration) return;
      setState(
        () => _records = {
          for (final item in records) _key(item.recordedOn): item,
        },
      );
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
    setState(() => _selectedDate = _dateOnly(selected));
    await _load();
  }

  Future<void> _changeWeek(int offset) async {
    if (offset > 0 && _isCurrentWeek) return;
    final nextDate = _selectedDate.add(Duration(days: offset * 7));
    final today = _dateOnly(DateTime.now());
    setState(() {
      _selectedDate = nextDate.isAfter(today) ? today : nextDate;
    });
    await _load();
  }

  Future<void> _changeDay(int offset) async {
    if (_loading || _saving) return;

    final nextDate = _selectedDate.add(Duration(days: offset));
    if (nextDate.isAfter(_dateOnly(DateTime.now()))) return;

    final currentWeek = _key(_weekStart);
    setState(() => _selectedDate = nextDate);
    if (_key(_weekStart) != currentWeek) await _load();
  }

  Future<void> _handleHorizontalSwipe(DragEndDetails details) async {
    final velocity = details.primaryVelocity ?? 0;
    if (velocity.abs() < 200) return;
    await _changeDay(velocity < 0 ? 1 : -1);
  }

  Future<void> _save(String status, {String? note}) async {
    if (_selectedDate.isAfter(_dateOnly(DateTime.now()))) return;
    final existing = _records[_key(_selectedDate)];
    final normalizedNote = note?.trim() ?? '';
    final existingNote = existing?.note?.trim() ?? '';
    if (existing?.status == status &&
        (status == 'well' || normalizedNote == existingNote)) {
      return;
    }
    setState(() => _saving = true);
    try {
      final record = await context
          .read<PersonalHealthRepository>()
          .saveDailyRecord(
            recordedOn: _key(_selectedDate),
            status: status,
            note: note,
          );
      if (!mounted) return;
      setState(() => _records[_key(_selectedDate)] = record);
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
    final controller = TextEditingController(
      text: _records[_key(_selectedDate)]?.note ?? '',
    );
    final note = await showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      builder: (context) => Padding(
        padding: EdgeInsets.fromLTRB(
          24,
          24,
          24,
          MediaQuery.viewInsetsOf(context).bottom + 24,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('วันนี้มีอาการอย่างไร', style: AppTextStyles.h4),
            const SizedBox(height: 6),
            Text(
              'ระบุอาการหรือสิ่งที่สังเกตได้ (ไม่บังคับ)',
              style: AppTextStyles.body2.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: controller,
              autofocus: true,
              minLines: 3,
              maxLines: 5,
              maxLength: 1000,
              decoration: const InputDecoration(
                hintText: 'เช่น ปวดศีรษะ รู้สึกอ่อนเพลีย',
              ),
            ),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: () => Navigator.pop(context, controller.text.trim()),
              style: FilledButton.styleFrom(
                backgroundColor: AppColors.primary,
                minimumSize: const Size.fromHeight(52),
              ),
              child: const Text('บันทึก'),
            ),
          ],
        ),
      ),
    );
    controller.dispose();
    if (note != null) await _save('unwell', note: note);
  }

  @override
  Widget build(BuildContext context) {
    final record = _records[_key(_selectedDate)];
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
                    child: RefreshIndicator(
                      onRefresh: _load,
                      child: GestureDetector(
                        behavior: HitTestBehavior.translucent,
                        onHorizontalDragEnd: _handleHorizontalSwipe,
                        child: ListView(
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
                          colors: [AppColors.primary, AppColors.primaryMid],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(24),
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.primary.withValues(alpha: 0.2),
                            blurRadius: 24,
                            offset: const Offset(0, 10),
                          ),
                        ],
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 52,
                            height: 52,
                            decoration: BoxDecoration(
                              color: AppColors.white.withValues(alpha: 0.16),
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
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  isToday
                                      ? 'สุขภาพของคุณวันนี้'
                                      : 'บันทึกวันที่ ${formatThaiDate(_selectedDate)}',
                                  style: AppTextStyles.body1Bold.copyWith(
                                    color: AppColors.white,
                                  ),
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
                    Text('วันนี้รู้สึกอย่างไร?', style: AppTextStyles.h3),
                    const SizedBox(height: 6),
                    Text(
                      'เลือกสถานะที่ใกล้เคียงกับความรู้สึกของคุณมากที่สุด',
                      style: AppTextStyles.body2.copyWith(
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: _statusChoice(
                            label: 'สบายดี',
                            description: 'ไม่มีอาการผิดปกติ',
                            icon: Icons.sentiment_satisfied_alt_rounded,
                            color: AppColors.success,
                            selected: record?.status == 'well',
                            onTap: () => _save('well'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _statusChoice(
                            label: 'มีอาการ',
                            description: 'รู้สึกไม่สบาย',
                            icon: Icons.sentiment_dissatisfied_rounded,
                            color: AppColors.danger,
                            selected: record?.status == 'unwell',
                            onTap: _recordUnwell,
                          ),
                        ),
                      ],
                    ),
                    if (record != null) ...[
                      const SizedBox(height: 20),
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: AppColors.surfaceElevated,
                          borderRadius: BorderRadius.circular(18),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Icon(
                              record.status == 'well'
                                  ? Icons.check_circle_outline_rounded
                                  : Icons.notes_rounded,
                              color: record.status == 'well'
                                  ? AppColors.success
                                  : AppColors.danger,
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'บันทึกล่าสุด',
                                    style: AppTextStyles.body2Bold,
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    record.note?.isNotEmpty == true
                                        ? record.note!
                                        : record.status == 'well'
                                        ? 'วันนี้คุณบันทึกว่าสบายดี'
                                        : 'วันนี้คุณบันทึกว่ามีอาการ',
                                    style: AppTextStyles.body2.copyWith(
                                      color: AppColors.textSecondary,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            if (record.status == 'unwell')
                              TextButton(
                                onPressed: _saving ? null : _recordUnwell,
                                child: const Text('แก้ไข'),
                              ),
                          ],
                        ),
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
            final record = _records[_key(date)];
            final statusIcon = record?.status == 'unwell'
                ? Icons.sentiment_dissatisfied_rounded
                : record?.status == 'well'
                ? Icons.sentiment_satisfied_alt_rounded
                : null;

            return Expanded(
              child: InkWell(
                borderRadius: BorderRadius.circular(12),
                onTap: isFuture
                    ? null
                    : () => setState(() => _selectedDate = date),
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
                        child: statusIcon == null
                            ? null
                            : Icon(
                                statusIcon,
                                size: 18,
                                color: record?.status == 'unwell'
                                    ? AppColors.danger
                                    : AppColors.success,
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
        constraints: const BoxConstraints(minHeight: 210),
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
              width: 76,
              height: 76,
              decoration: BoxDecoration(
                color: color.withValues(alpha: selected ? 0.2 : 0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 46, color: color),
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
      setState(() {
        _records = {
          for (final record in records) _key(record.recordedOn): record,
        };
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
                (year) =>
                    DropdownMenuItem<int>(value: year, child: Text('ปี $year')),
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
                          : AppColors.success,
                    ),
            ),
          ],
        ),
      ),
    );
  }
}
