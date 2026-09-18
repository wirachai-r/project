import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/buddhist_calendar_delegate.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../data/repositories/personal_health_repository.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../shared/widgets/app_layout.dart';
import '../../../shared/widgets/symptom_icon.dart';

class HealthDashboardScreen extends StatefulWidget {
  const HealthDashboardScreen({super.key});

  @override
  State<HealthDashboardScreen> createState() => _HealthDashboardScreenState();
}

class _HealthDashboardScreenState extends State<HealthDashboardScreen> {
  Map<String, dynamic>? _data;
  String? _error;
  bool _refreshing = false;
  int _days = 7;
  DateTimeRange? _customRange;
  int _loadGeneration = 0;
  Map<String, dynamic>? _aiSummary;
  bool _analyzing = false;

  String _dateParam(DateTime value) =>
      '${value.year.toString().padLeft(4, '0')}-'
      '${value.month.toString().padLeft(2, '0')}-'
      '${value.day.toString().padLeft(2, '0')}';

  int get _activeDays =>
      _customRange == null ? _days : _customRange!.duration.inDays + 1;

  DateTimeRange _rangeForPeriod(int days, DateTime anchor) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final anchorDate = DateTime(anchor.year, anchor.month, anchor.day);
    final range = switch (days) {
      7 => DateTimeRange(
        start: anchorDate.subtract(Duration(days: anchorDate.weekday - 1)),
        end: anchorDate.add(Duration(days: 7 - anchorDate.weekday)),
      ),
      30 => DateTimeRange(
        start: DateTime(anchorDate.year, anchorDate.month),
        end: DateTime(anchorDate.year, anchorDate.month + 1, 0),
      ),
      365 => DateTimeRange(
        start: DateTime(anchorDate.year),
        end: DateTime(anchorDate.year, 12, 31),
      ),
      _ => DateTimeRange(start: anchorDate, end: anchorDate),
    };
    return DateTimeRange(
      start: range.start,
      end: days == 7
          ? range.end
          : (range.end.isAfter(today) ? today : range.end),
    );
  }

  DateTimeRange get _activeRange {
    if (_customRange != null) return _customRange!;
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    return DateTimeRange(
      start: today.subtract(Duration(days: _days - 1)),
      end: today,
    );
  }

  DateTimeRange get _queryRange {
    final range = _activeRange;
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    return DateTimeRange(
      start: range.start,
      end: range.end.isAfter(today) ? today : range.end,
    );
  }

  bool get _canMoveNext {
    final today = DateTime.now();
    return _activeRange.end.isBefore(
      DateTime(today.year, today.month, today.day),
    );
  }

  void _shiftPeriod(int amount) {
    final current = _activeRange;
    final shifted = switch (_days) {
      7 => DateTimeRange(
        start: current.start.add(Duration(days: 7 * amount)),
        end: current.end.add(Duration(days: 7 * amount)),
      ),
      30 => DateTimeRange(
        start: DateTime(current.start.year, current.start.month + amount),
        end: DateTime(current.start.year, current.start.month + amount + 1, 0),
      ),
      365 => DateTimeRange(
        start: DateTime(current.start.year + amount),
        end: DateTime(current.start.year + amount, 12, 31),
      ),
      _ => current,
    };
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    setState(() {
      _customRange = DateTimeRange(
        start: shifted.start,
        end: _days == 7
            ? shifted.end
            : (shifted.end.isAfter(today) ? today : shifted.end),
      );
    });
    _load();
  }

  Future<void> _pickNamedPeriod() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      calendarDelegate: const BuddhistCalendarDelegate(),
      locale: const Locale('th', 'TH'),
      firstDate: DateTime(now.year - 10),
      lastDate: now,
      initialDate: _activeRange.start,
      helpText: _days == 7
          ? 'เลือกสัปดาห์'
          : _days == 30
          ? 'เลือกเดือน'
          : 'เลือกปี',
      cancelText: 'ยกเลิก',
      confirmText: 'เลือก',
    );
    if (picked == null || !mounted) return;
    final range = _rangeForPeriod(_days, picked);
    setState(() => _customRange = range);
    _load();
  }

  @override
  void initState() {
    super.initState();
    _customRange = _rangeForPeriod(_days, DateTime.now());
    _load();
  }

  Future<void> _load() async {
    final generation = ++_loadGeneration;
    final repository = context.read<PersonalHealthRepository>();
    final queryRange = _queryRange;
    if (_data != null && mounted) setState(() => _refreshing = true);
    try {
      final result = await repository.dashboard(
        days: _days,
        from: _dateParam(queryRange.start),
        to: _dateParam(queryRange.end),
      );
      if (!mounted || generation != _loadGeneration) return;
      final from = _dateParam(queryRange.start);
      final to = _dateParam(queryRange.end);
      setState(() {
        _data = result;
        _aiSummary = repository.cachedAiTrendSummary(
          days: _days == 0 ? 30 : _days,
          from: from,
          to: to,
        );
        _error = null;
      });
    } catch (_) {
      if (!mounted || generation != _loadGeneration) return;
      setState(() => _error = 'ไม่สามารถโหลดข้อมูลสุขภาพได้ในขณะนี้');
    } finally {
      if (mounted && generation == _loadGeneration) {
        setState(() => _refreshing = false);
      }
    }
  }

  Future<void> _analyzeWithAi() async {
    if (_analyzing) return;
    final queryRange = _queryRange;
    setState(() => _analyzing = true);
    try {
      final result = await context
          .read<PersonalHealthRepository>()
          .aiTrendSummary(
            days: _days == 0 ? 30 : _days,
            from: _dateParam(queryRange.start),
            to: _dateParam(queryRange.end),
          );
      if (!mounted) return;
      setState(() => _aiSummary = result);
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('ยังไม่สามารถวิเคราะห์ข้อมูลด้วย AI ได้ในขณะนี้'),
        ),
      );
    } finally {
      if (mounted) setState(() => _analyzing = false);
    }
  }

  Future<void> _pickCustomRange() async {
    final now = DateTime.now();
    final selected = await showDateRangePicker(
      context: context,
      calendarDelegate: const BuddhistCalendarDelegate(),
      locale: const Locale('th', 'TH'),
      firstDate: DateTime(now.year - 1),
      lastDate: now,
      initialDateRange: _customRange,
      helpText: 'เลือกช่วงวันที่วิเคราะห์',
      cancelText: 'ยกเลิก',
      confirmText: 'เลือก',
      saveText: 'บันทึก',
      fieldStartLabelText: 'วันที่เริ่มต้น',
      fieldEndLabelText: 'วันที่สิ้นสุด',
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(
          colorScheme: Theme.of(
            context,
          ).colorScheme.copyWith(primary: AppColors.primary),
        ),
        child: child!,
      ),
    );
    if (selected == null || !mounted) return;
    setState(() {
      _customRange = selected;
      _days = 0;
    });
    _load();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: Text('แนวโน้มสุขภาพ', style: AppTextStyles.h4),
      bottom: PreferredSize(
        preferredSize: Size.fromHeight(1),
        child: Divider(
          height: 1,
          thickness: 1,
          color: Theme.of(context).colorScheme.outlineVariant,
        ),
      ),
    ),
    body: _buildBody(),
  );

  Widget _buildBody() {
    if (_data == null && _error == null) return const AppLoadingView();
    if (_data == null) {
      return AppMessageView.error(message: _error!, onAction: _load);
    }

    return Stack(
      children: [
        RefreshIndicator(onRefresh: _load, child: _content()),
        if (_refreshing)
          const Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: LinearProgressIndicator(minHeight: 2),
          ),
      ],
    );
  }

  Widget _content() {
    final summary = Map<String, dynamic>.from(_data!['summary'] ?? {});
    final symptoms = List<dynamic>.from(_data!['top_symptoms'] ?? []);
    final dailyStatuses = List<dynamic>.from(
      _data!['daily_status_trend'] ?? [],
    );
    final assessmentTrend = List<dynamic>.from(
      _data!['assessment_trend'] ?? [],
    );
    final followUpTrend = List<dynamic>.from(_data!['severity_trend'] ?? []);

    return AppContentWidth(
      maxWidth: 720,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
        children: [
          _HealthPeriodSelector(
            selectedDays: _days,
            customRange: _customRange,
            onSelected: (days) {
              if (days == 0) {
                _pickCustomRange();
              } else {
                setState(() {
                  _days = days;
                  _customRange = _rangeForPeriod(days, DateTime.now());
                });
                _load();
              }
            },
          ),
          if (_days != 0)
            _HealthPeriodNavigator(
              days: _days,
              range: _activeRange,
              canMoveNext: _canMoveNext,
              onPrevious: () => _shiftPeriod(-1),
              onNext: () => _shiftPeriod(1),
              onPick: _pickNamedPeriod,
            )
          else if (_customRange != null) ...[
            const SizedBox(height: 10),
            Text(
              'ช่วง ${_formatRangeDate(_customRange!.start)} – '
              '${_formatRangeDate(_customRange!.end)}',
              textAlign: TextAlign.center,
              style: AppTextStyles.body3.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
          ],
          const SizedBox(height: 24),
          Text('สรุปกิจกรรมในช่วงที่เลือก', style: AppTextStyles.body1Bold),
          const SizedBox(height: 4),
          Text(
            'ตัวเลขด้านล่างคือจำนวนรายการที่คุณบันทึกในแต่ละประเภท',
            style: AppTextStyles.body3.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: 12),
          IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Expanded(
                  child: _MetricCard(
                    width: double.infinity,
                    label: 'ประวัติการประเมิน',
                    value: summary['assessment_count'] ?? 0,
                    icon: Icons.fact_check_outlined,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _MetricCard(
                    width: double.infinity,
                    label: 'สุขภาพประจำวัน',
                    value: dailyStatuses.length,
                    icon: Icons.calendar_month_outlined,
                    accent: AppColors.success,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _MetricCard(
                    width: double.infinity,
                    label: 'การติดตาม',
                    value: summary['follow_up_count'] ?? 0,
                    icon: Icons.timeline_rounded,
                    accent: AppColors.warning,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          _HealthActivityChart(
            days: _days,
            range: _activeRange,
            assessments: assessmentTrend,
            followUps: followUpTrend,
            dailyRecords: dailyStatuses,
          ),
          const SizedBox(height: 28),
          const AppSectionHeader(title: 'บันทึกสุขภาพรายวัน'),
          const SizedBox(height: 4),
          Text(
            'สรุปวันที่คุณเช็กอินว่าสบายดี ปกติ หรือมีอาการในช่วงที่เลือก',
            style: AppTextStyles.body3.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: 12),
          _DailyStatusSummary(items: dailyStatuses, days: _activeDays),
          const SizedBox(height: 28),
          const AppSectionHeader(title: 'อาการที่พบบ่อย'),
          const SizedBox(height: 4),
          Text(
            'แสดงสูงสุด 5 อันดับ จากการประเมิน การติดตามอาการ และบันทึกสุขภาพ',
            style: AppTextStyles.body3.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: 12),
          if (symptoms.isEmpty)
            const _InlineEmpty(
              icon: Icons.monitor_heart_outlined,
              text:
                  'ยังไม่มีข้อมูลอาการจากการประเมิน การติดตาม หรือบันทึกสุขภาพ',
            )
          else
            ...symptoms.map((item) => _FrequentSymptomCard(item: item)),
          const SizedBox(height: 16),
          _AiAnalysisSection(
            data: _aiSummary,
            loading: _analyzing,
            onAnalyze: _analyzeWithAi,
          ),
        ],
      ),
    );
  }

  String _formatRangeDate(DateTime value) => formatThaiDate(value);
}

class _MetricCard extends StatelessWidget {
  final double width;
  final String label;
  final dynamic value;
  final IconData icon;
  final Color accent;

  const _MetricCard({
    required this.width,
    required this.label,
    required this.value,
    required this.icon,
    this.accent = AppColors.primary,
  });

  @override
  Widget build(BuildContext context) => SizedBox(
    width: width,
    child: Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: accent),
            const SizedBox(height: 12),
            Text('$value', style: AppTextStyles.h3),
            const SizedBox(height: 2),
            Text(
              label,
              style: AppTextStyles.body3.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
          ],
        ),
      ),
    ),
  );
}

class _HealthPeriodSelector extends StatelessWidget {
  final int selectedDays;
  final DateTimeRange? customRange;
  final ValueChanged<int> onSelected;

  const _HealthPeriodSelector({
    required this.selectedDays,
    required this.customRange,
    required this.onSelected,
  });

  @override
  Widget build(BuildContext context) {
    final labels = <int, String>{
      7: 'รายสัปดาห์',
      30: 'รายเดือน',
      365: 'รายปี',
      0: selectedDays == 0 && customRange != null
          ? 'ช่วงที่เลือก'
          : 'เลือกวันที่',
    };
    return Semantics(
      container: true,
      label: 'เลือกช่วงเวลาสำหรับดูแนวโน้มสุขภาพ',
      child: SizedBox(
        height: 58,
        child: ListView(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(vertical: 4),
          children: labels.entries.map((entry) {
            final active = selectedDays == entry.key;
            final isCustom = entry.key == 0;
            return Padding(
              padding: const EdgeInsets.only(right: 8),
              child: ChoiceChip(
                selected: active,
                showCheckmark: false,
                avatar: isCustom
                    ? Icon(
                        active
                            ? Icons.event_available_rounded
                            : Icons.calendar_month_outlined,
                        size: 18,
                        color: active
                            ? AppColors.white
                            : Theme.of(context).colorScheme.onSurfaceVariant,
                      )
                    : null,
                label: Text(entry.value),
                labelStyle: AppTextStyles.body2.copyWith(
                  color: active
                      ? AppColors.white
                      : Theme.of(context).colorScheme.onSurfaceVariant,
                  fontWeight: active ? FontWeight.w700 : FontWeight.w400,
                ),
                backgroundColor: Theme.of(context).colorScheme.surface,
                selectedColor: AppColors.primary,
                side: BorderSide(
                  color: active
                      ? AppColors.primary
                      : Theme.of(context).colorScheme.outlineVariant,
                ),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 10,
                ),
                onSelected: (_) => onSelected(entry.key),
              ),
            );
          }).toList(),
        ),
      ),
    );
  }
}

class _HealthPeriodNavigator extends StatelessWidget {
  final int days;
  final DateTimeRange range;
  final bool canMoveNext;
  final VoidCallback onPrevious;
  final VoidCallback onNext;
  final VoidCallback onPick;

  const _HealthPeriodNavigator({
    required this.days,
    required this.range,
    required this.canMoveNext,
    required this.onPrevious,
    required this.onNext,
    required this.onPick,
  });

  String get _label {
    if (days == 365) return 'ปี ${range.start.year + 543}';
    if (days == 30) {
      const months = [
        'มกราคม',
        'กุมภาพันธ์',
        'มีนาคม',
        'เมษายน',
        'พฤษภาคม',
        'มิถุนายน',
        'กรกฎาคม',
        'สิงหาคม',
        'กันยายน',
        'ตุลาคม',
        'พฤศจิกายน',
        'ธันวาคม',
      ];
      return '${months[range.start.month - 1]} ${range.start.year + 543}';
    }
    return '${formatShortThaiDate(range.start)} – '
        '${formatShortThaiDate(range.end)}';
  }

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(top: 6),
    child: Material(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        height: 56,
        decoration: BoxDecoration(
          border: Border.all(
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(
          children: [
            IconButton(
              tooltip: 'ช่วงก่อนหน้า',
              onPressed: onPrevious,
              icon: const Icon(Icons.chevron_left_rounded),
            ),
            Expanded(
              child: InkWell(
                onTap: onPick,
                borderRadius: BorderRadius.circular(12),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Flexible(
                      child: Text(
                        _label,
                        overflow: TextOverflow.ellipsis,
                        style: AppTextStyles.body1Bold,
                      ),
                    ),
                    const SizedBox(width: 10),
                    const Icon(Icons.calendar_month_outlined),
                  ],
                ),
              ),
            ),
            IconButton(
              tooltip: 'ช่วงถัดไป',
              onPressed: canMoveNext ? onNext : null,
              icon: const Icon(Icons.chevron_right_rounded),
            ),
          ],
        ),
      ),
    ),
  );
}

class _FrequentSymptomCard extends StatelessWidget {
  final dynamic item;

  const _FrequentSymptomCard({required this.item});

  @override
  Widget build(BuildContext context) {
    final name = item['symptom_name']?.toString().trim();
    final count = item['count'] ?? 0;
    final sources = <({Color color, String label})>[
      if ((item['assessment_count'] as num? ?? 0) > 0)
        (
          color: AppColors.primary,
          label: 'ประเมิน ${item['assessment_count']}',
        ),
      if ((item['follow_up_count'] as num? ?? 0) > 0)
        (color: AppColors.warning, label: 'ติดตาม ${item['follow_up_count']}'),
      if ((item['daily_record_count'] as num? ?? 0) > 0)
        (
          color: AppColors.success,
          label: 'บันทึกสุขภาพ ${item['daily_record_count']}',
        ),
    ];

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surfaceContainerLow,
              borderRadius: BorderRadius.all(Radius.circular(12)),
            ),
            child: SymptomIcon(
              iconName: item['symptom_image']?.toString(),
              color: AppColors.primary,
              size: 21,
            ),
          ),
          const SizedBox(width: 13),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name?.isNotEmpty == true ? name! : 'ไม่ระบุอาการ',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: AppTextStyles.body1Bold,
                ),
                const SizedBox(height: 3),
                if (sources.isEmpty)
                  Text(
                    'พบทั้งหมด $count ครั้ง',
                    style: AppTextStyles.body3.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                  )
                else
                  Wrap(
                    spacing: 10,
                    runSpacing: 4,
                    children: sources
                        .map(
                          (source) => _StatusLegend(
                            color: source.color,
                            label: source.label,
                          ),
                        )
                        .toList(),
                  ),
              ],
            ),
          ),
          Container(
            constraints: const BoxConstraints(minWidth: 38),
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surfaceContainerLow,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              '$count',
              textAlign: TextAlign.center,
              style: AppTextStyles.body2Bold.copyWith(color: AppColors.primary),
            ),
          ),
        ],
      ),
    );
  }
}

class _HealthActivityChart extends StatelessWidget {
  final int days;
  final DateTimeRange range;
  final List<dynamic> assessments;
  final List<dynamic> followUps;
  final List<dynamic> dailyRecords;

  const _HealthActivityChart({
    required this.days,
    required this.range,
    required this.assessments,
    required this.followUps,
    required this.dailyRecords,
  });

  DateTime? _date(dynamic value) =>
      DateTime.tryParse(value?.toString() ?? '')?.toLocal();

  @override
  Widget build(BuildContext context) {
    final totalDays = range.duration.inDays + 1;
    final bucketSize = totalDays <= 14 ? 1 : (totalDays / 7).ceil();
    final labels = switch (days) {
      7 => const ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'],
      30 => List.generate(
        (DateTime(range.start.year, range.start.month + 1, 0).day / 7).ceil(),
        (index) {
          final first = index * 7 + 1;
          final last = (first + 6).clamp(
            1,
            DateTime(range.start.year, range.start.month + 1, 0).day,
          );
          return '$first-$last';
        },
      ),
      365 => const [
        'ม.ค.',
        'ก.พ.',
        'มี.ค.',
        'เม.ย.',
        'พ.ค.',
        'มิ.ย.',
        'ก.ค.',
        'ส.ค.',
        'ก.ย.',
        'ต.ค.',
        'พ.ย.',
        'ธ.ค.',
      ],
      _ => List.generate((totalDays / bucketSize).ceil(), (index) {
        final start = range.start.add(Duration(days: index * bucketSize));
        final rawEnd = start.add(Duration(days: bucketSize - 1));
        final end = rawEnd.isAfter(range.end) ? range.end : rawEnd;
        return bucketSize == 1
            ? '${start.day}/${start.month}'
            : '${start.day}-${end.day}/${end.month}';
      }),
    };
    final bucketCount = labels.length;
    final values = List.generate(bucketCount, (_) => <int>[0, 0, 0]);

    void add(List<dynamic> source, String field, int series) {
      for (final item in source) {
        final date = _date(item[field]);
        if (date == null) continue;
        final day = DateTime(date.year, date.month, date.day);
        final index = switch (days) {
          7 => day.difference(range.start).inDays,
          30 => (day.day - 1) ~/ 7,
          365 => day.month - 1,
          _ => day.difference(range.start).inDays ~/ bucketSize,
        };
        if (index >= 0 && index < values.length) values[index][series]++;
      }
    }

    add(assessments, 'completed_at', 0);
    add(dailyRecords, 'recorded_on', 1);
    add(followUps, 'recorded_at', 2);
    final totals = List.generate(
      3,
      (series) => values.fold<int>(0, (sum, bucket) => sum + bucket[series]),
    );
    final maxValue = values
        .expand((item) => item)
        .fold<int>(1, (max, value) => value > max ? value : max);
    final xAxisUnit = days == 7
        ? 'วัน'
        : days == 365
        ? 'เดือน'
        : 'ช่วงวันที่';

    return Container(
      padding: const EdgeInsets.all(18),
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
              const Icon(Icons.bar_chart_rounded, color: AppColors.primary),
              const SizedBox(width: 10),
              Expanded(
                child: Text('กิจกรรมสุขภาพ', style: AppTextStyles.body1Bold),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            days == 365
                ? 'ปี ${range.start.year + 543}'
                : 'วันที่ ${formatThaiDate(range.start)} – '
                      '${formatThaiDate(range.end)}',
            style: AppTextStyles.body3.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            'จำนวน (ครั้ง)',
            style: AppTextStyles.body3.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: 4),
          Semantics(
            label:
                'กราฟกิจกรรมสุขภาพ แสดงการประเมิน สุขภาพประจำวัน '
                'และการติดตามอาการ',
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                SizedBox(
                  width: 28,
                  height: 160,
                  child: Padding(
                    padding: const EdgeInsets.only(top: 34, bottom: 28),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text('$maxValue', style: AppTextStyles.body3),
                        const Spacer(),
                        Text(
                          '${(maxValue / 2).ceil()}',
                          style: AppTextStyles.body3,
                        ),
                        const Spacer(),
                        Text('0', style: AppTextStyles.body3),
                      ],
                    ),
                  ),
                ),
                const SizedBox(width: 6),
                Expanded(
                  child: SizedBox(
                    height: 160,
                    child: CustomPaint(
                      painter: _HealthActivityChartPainter(
                        values: values,
                        labels: labels,
                        gridColor: Theme.of(context).colorScheme.outlineVariant,
                        labelColor: Theme.of(
                          context,
                        ).colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          Center(
            child: Text(
              'ช่วงเวลา ($xAxisUnit)',
              style: AppTextStyles.body3.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
          ),
          const SizedBox(height: 14),
          Center(
            child: FittedBox(
              fit: BoxFit.scaleDown,
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  _StatusLegend(
                    color: AppColors.primary,
                    label: 'ประเมิน ${totals[0]} ครั้ง',
                  ),
                  const SizedBox(width: 14),
                  _StatusLegend(
                    color: AppColors.success,
                    label: 'สุขภาพรายวัน ${totals[1]} ครั้ง',
                  ),
                  const SizedBox(width: 14),
                  _StatusLegend(
                    color: AppColors.warning,
                    label: 'ติดตาม ${totals[2]} ครั้ง',
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _HealthActivityChartPainter extends CustomPainter {
  final List<List<int>> values;
  final List<String> labels;
  final Color gridColor;
  final Color labelColor;

  const _HealthActivityChartPainter({
    required this.values,
    required this.labels,
    required this.gridColor,
    required this.labelColor,
  });

  @override
  void paint(Canvas canvas, Size size) {
    // Reserve enough headroom for three staggered value-label lanes. Labels
    // at the same height otherwise overlap when adjacent series have similar
    // values, especially in the 12-month view.
    const top = 42.0;
    const bottom = 28.0;
    final chartHeight = size.height - top - bottom;
    final maxValue = values
        .expand((item) => item)
        .fold<int>(1, (max, value) => value > max ? value : max);
    final gridPaint = Paint()
      ..color = gridColor
      ..strokeWidth = 1;
    for (var i = 0; i <= 3; i++) {
      final y = top + chartHeight * i / 3;
      canvas.drawLine(Offset(0, y), Offset(size.width, y), gridPaint);
    }

    final groupWidth = size.width / values.length;
    final barWidth = (groupWidth * .62 / 3).clamp(2.0, 12.0).toDouble();
    const colors = [AppColors.primary, AppColors.success, AppColors.warning];
    for (var group = 0; group < values.length; group++) {
      final center = groupWidth * (group + .5);
      for (var series = 0; series < 3; series++) {
        final height = chartHeight * values[group][series] / maxValue;
        final left = center + (series - 1) * barWidth - barWidth / 2;
        canvas.drawRRect(
          RRect.fromRectAndRadius(
            Rect.fromLTWH(
              left,
              top + chartHeight - height,
              barWidth - 1,
              height,
            ),
            const Radius.circular(2),
          ),
          Paint()..color = colors[series],
        );
        if (values[group][series] > 0) {
          final valuePainter = TextPainter(
            text: TextSpan(
              text: '${values[group][series]}',
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w700,
                color: colors[series],
              ),
            ),
            textDirection: TextDirection.ltr,
          )..layout();
          final labelTop =
              top +
              chartHeight -
              height -
              valuePainter.height -
              3 -
              (series * 11);
          valuePainter.paint(
            canvas,
            Offset(
              left + (barWidth - valuePainter.width) / 2,
              labelTop
                  .clamp(1.0, size.height - valuePainter.height)
                  .toDouble(),
            ),
          );
        }
      }
      if (labels.isNotEmpty) {
        final painter = TextPainter(
          text: TextSpan(
            text: labels[group],
            style: TextStyle(fontSize: 12, color: labelColor),
          ),
          textDirection: TextDirection.ltr,
        )..layout();
        painter.paint(
          canvas,
          Offset(center - painter.width / 2, size.height - 18),
        );
      }
    }
  }

  @override
  bool shouldRepaint(covariant _HealthActivityChartPainter oldDelegate) =>
      oldDelegate.values != values ||
      oldDelegate.labels != labels ||
      oldDelegate.gridColor != gridColor ||
      oldDelegate.labelColor != labelColor;
}

class _AiTrendSummaryCard extends StatelessWidget {
  final Map<String, dynamic> data;

  const _AiTrendSummaryCard({required this.data});

  @override
  Widget build(BuildContext context) {
    final observations = List<dynamic>.from(data['observations'] ?? const []);
    final selfCare = List<dynamic>.from(data['self_care'] ?? const []);
    final warningSigns = List<dynamic>.from(data['warning_signs'] ?? const []);
    final usedFallback = data['source'] == 'backend_fallback';
    final cached = data['cached'] == true;
    return Container(
      padding: const EdgeInsets.all(18),
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
              const Icon(Icons.auto_awesome_outlined, color: AppColors.primary),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('สรุปแนวโน้มสุขภาพ', style: AppTextStyles.body1Bold),
                    Text(
                      usedFallback
                          ? 'สรุปจากข้อมูลในระบบ เนื่องจาก AI ไม่พร้อมใช้งาน'
                          : cached
                          ? 'AI สรุปไว้จากข้อมูลชุดนี้'
                          : 'AI ช่วยเรียบเรียงจากข้อมูลในช่วงที่เลือก',
                      style: AppTextStyles.body3.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.primaryLight.withValues(alpha: 0.42),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Text(
              data['summary']?.toString() ?? '',
              style: AppTextStyles.body2.copyWith(height: 1.55),
            ),
          ),
          if (observations.isNotEmpty) ...[
            const SizedBox(height: 14),
            _TrendInsightSection(
              icon: Icons.insights_rounded,
              title: 'สิ่งที่พบจากข้อมูล',
              color: AppColors.primary,
              items: observations,
            ),
          ],
          if (selfCare.isNotEmpty) ...[
            const SizedBox(height: 14),
            _TrendInsightSection(
              icon: Icons.health_and_safety_outlined,
              title: 'แนวทางดูแลตัวเอง',
              color: AppColors.success,
              items: selfCare,
            ),
          ],
          if (warningSigns.isNotEmpty) ...[
            const SizedBox(height: 14),
            _TrendInsightSection(
              icon: Icons.warning_amber_rounded,
              title: 'สัญญาณที่ควรพบแพทย์',
              color: AppColors.danger,
              items: warningSigns,
            ),
          ],
          const SizedBox(height: 14),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surfaceContainerLow,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(
                  Icons.info_outline_rounded,
                  size: 18,
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    data['disclaimer']?.toString() ?? '',
                    style: AppTextStyles.body3.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                      height: 1.45,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _TrendInsightSection extends StatelessWidget {
  final IconData icon;
  final String title;
  final Color color;
  final List<dynamic> items;

  const _TrendInsightSection({
    required this.icon,
    required this.title,
    required this.color,
    required this.items,
  });

  @override
  Widget build(BuildContext context) => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(
      color: color.withValues(alpha: 0.06),
      borderRadius: BorderRadius.circular(14),
      border: Border.all(color: color.withValues(alpha: 0.18)),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, color: color, size: 20),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                title,
                style: AppTextStyles.body2Bold.copyWith(color: color),
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        ...items.asMap().entries.map(
          (entry) => Padding(
            padding: EdgeInsets.only(top: entry.key == 0 ? 0 : 8),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 22,
                  height: 22,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.12),
                    shape: BoxShape.circle,
                  ),
                  child: Text(
                    '${entry.key + 1}',
                    style: AppTextStyles.body3Bold.copyWith(color: color),
                  ),
                ),
                const SizedBox(width: 9),
                Expanded(
                  child: Text(
                    entry.value.toString(),
                    style: AppTextStyles.body2.copyWith(height: 1.5),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    ),
  );
}

class _AiAnalysisSection extends StatelessWidget {
  final Map<String, dynamic>? data;
  final bool loading;
  final VoidCallback onAnalyze;

  const _AiAnalysisSection({
    required this.data,
    required this.loading,
    required this.onAnalyze,
  });

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Container(
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: AppColors.primaryLight.withValues(alpha: 0.45),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.primary.withValues(alpha: 0.25)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(
                  Icons.auto_awesome_rounded,
                  color: AppColors.primary,
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'วิเคราะห์ข้อมูลสุขภาพด้วย AI',
                    style: AppTextStyles.body1Bold,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              'ใช้ข้อมูลการประเมิน การติดตาม และบันทึกสุขภาพในช่วงที่เลือก เพื่อช่วยสรุปแนวโน้มและคำแนะนำที่มีอยู่ในระบบ',
              style: AppTextStyles.body2.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
                height: 1.45,
              ),
            ),
            const SizedBox(height: 14),
            FilledButton.icon(
              onPressed: loading ? null : onAnalyze,
              icon: loading
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: AppColors.white,
                      ),
                    )
                  : const Icon(Icons.auto_awesome_outlined),
              label: Text(
                loading
                    ? 'กำลังวิเคราะห์...'
                    : data == null
                    ? 'วิเคราะห์ข้อมูล'
                    : 'วิเคราะห์ใหม่',
              ),
            ),
          ],
        ),
      ),
      if (data != null) ...[
        const SizedBox(height: 12),
        _AiTrendSummaryCard(data: data!),
      ],
    ],
  );
}

class _InlineEmpty extends StatelessWidget {
  final IconData icon;
  final String text;

  const _InlineEmpty({required this.icon, required this.text});

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 12),
    child: Column(
      children: [
        Icon(
          icon,
          size: 36,
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
        const SizedBox(height: 10),
        Text(
          text,
          textAlign: TextAlign.center,
          style: AppTextStyles.body2.copyWith(
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
      ],
    ),
  );
}

class _DailyStatusSummary extends StatelessWidget {
  final List<dynamic> items;
  final int days;

  const _DailyStatusSummary({required this.items, required this.days});

  @override
  Widget build(BuildContext context) {
    final unwell = items.where((item) => item['status'] == 'unwell').length;
    final well = items.where((item) => item['status'] == 'well').length;
    final normal = items.where((item) => item['status'] == 'normal').length;
    final recordedDays = items
        .map((item) => item['recorded_on']?.toString())
        .whereType<String>()
        .toSet()
        .length;
    final coverage = days == 0
        ? 0.0
        : (recordedDays / days).clamp(0.0, 1.0).toDouble();
    if (items.isEmpty) {
      return const Card(
        child: Padding(
          padding: EdgeInsets.all(20),
          child: _InlineEmpty(
            icon: Icons.calendar_month_outlined,
            text: 'ยังไม่มีบันทึกสุขภาพในช่วงเวลานี้',
          ),
        ),
      );
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: AppColors.primaryLight,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(
                    Icons.calendar_month_rounded,
                    color: AppColors.primary,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'บันทึก $recordedDays วัน จาก $days วัน',
                        style: AppTextStyles.body1Bold,
                      ),
                      Text(
                        'บันทึกทั้งหมด ${items.length} ครั้ง',
                        style: AppTextStyles.body3.copyWith(
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ),
                ),
                Text(
                  '${(coverage * 100).round()}%',
                  style: AppTextStyles.h4.copyWith(color: AppColors.primary),
                ),
              ],
            ),
            const SizedBox(height: 14),
            LinearProgressIndicator(
              value: coverage,
              minHeight: 10,
              borderRadius: BorderRadius.circular(8),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _DailyStatusItem(
                    color: AppColors.success,
                    icon: Icons.sentiment_satisfied_alt_rounded,
                    label: 'สบายดี',
                    count: well,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _DailyStatusItem(
                    color: AppColors.primary,
                    icon: Icons.sentiment_neutral_rounded,
                    label: 'ปกติ',
                    count: normal,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _DailyStatusItem(
                    color: AppColors.danger,
                    icon: Icons.sentiment_dissatisfied_rounded,
                    label: 'มีอาการ',
                    count: unwell,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _DailyStatusItem extends StatelessWidget {
  final Color color;
  final IconData icon;
  final String label;
  final int count;

  const _DailyStatusItem({
    required this.color,
    required this.icon,
    required this.label,
    required this.count,
  });

  @override
  Widget build(BuildContext context) => Container(
    height: 42,
    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 8),
    decoration: BoxDecoration(
      color: color.withValues(alpha: 0.08),
      borderRadius: BorderRadius.circular(12),
    ),
    child: FittedBox(
      fit: BoxFit.scaleDown,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 17, color: color),
          const SizedBox(width: 5),
          Text('$label $count ครั้ง', style: AppTextStyles.body3Bold),
        ],
      ),
    ),
  );
}

class _StatusLegend extends StatelessWidget {
  final Color color;
  final String label;

  const _StatusLegend({required this.color, required this.label});

  @override
  Widget build(BuildContext context) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Container(
        width: 10,
        height: 10,
        decoration: BoxDecoration(color: color, shape: BoxShape.circle),
      ),
      const SizedBox(width: 6),
      Text(label, style: AppTextStyles.body3),
    ],
  );
}
