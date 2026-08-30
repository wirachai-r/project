import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../data/models/history_model.dart';
import '../../../shared/widgets/symptom_icon.dart';
import '../providers/history_provider.dart';
import 'history_detail_screen.dart';

enum _HistoryPeriod { all, week, month, year, custom }

class HistoryListScreen extends StatefulWidget {
  const HistoryListScreen({super.key});

  @override
  State<HistoryListScreen> createState() => _HistoryListScreenState();
}

class _HistoryListScreenState extends State<HistoryListScreen> {
  static const _itemsPerPage = 10;

  _HistoryPeriod _period = _HistoryPeriod.week;
  DateTimeRange? _customRange;
  int _listPage = 1;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<HistoryProvider>().loadAll(refresh: true);
    });
  }

  DateTime? _dateOf(HistoryItemModel item) =>
      DateTime.tryParse(item.createdAt)?.toLocal();

  DateTimeRange _activeRange() {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    switch (_period) {
      case _HistoryPeriod.all:
        return DateTimeRange(start: DateTime(now.year - 10), end: today);
      case _HistoryPeriod.week:
        return DateTimeRange(
          start: today.subtract(Duration(days: today.weekday - 1)),
          end: today,
        );
      case _HistoryPeriod.month:
        return DateTimeRange(start: DateTime(now.year, now.month), end: today);
      case _HistoryPeriod.year:
        return DateTimeRange(start: DateTime(now.year), end: today);
      case _HistoryPeriod.custom:
        return _customRange ?? DateTimeRange(start: today, end: today);
    }
  }

  List<HistoryItemModel> _filtered(List<HistoryItemModel> items) {
    if (_period == _HistoryPeriod.all) return items;

    final range = _activeRange();
    final start = DateTime(
      range.start.year,
      range.start.month,
      range.start.day,
    );
    final end = DateTime(
      range.end.year,
      range.end.month,
      range.end.day,
      23,
      59,
      59,
      999,
    );
    return items.where((item) {
      final date = _dateOf(item);
      return date != null && !date.isBefore(start) && !date.isAfter(end);
    }).toList();
  }

  Future<void> _pickRange() async {
    final now = DateTime.now();
    final range = await showDateRangePicker(
      context: context,
      locale: const Locale('th', 'TH'),
      firstDate: DateTime(now.year - 10),
      lastDate: now,
      initialDateRange: _customRange,
      helpText: 'เลือกช่วงวันที่',
      cancelText: 'ยกเลิก',
      confirmText: 'เลือก',
      saveText: 'บันทึก',
      fieldStartHintText: 'วัน/เดือน/ปี',
      fieldEndHintText: 'วัน/เดือน/ปี',
      fieldStartLabelText: 'วันที่เริ่มต้น',
      fieldEndLabelText: 'วันที่สิ้นสุด',
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(
          colorScheme: Theme.of(
            context,
          ).colorScheme.copyWith(primary: AppColors.primary),
          textTheme: Theme.of(context).textTheme.apply(fontFamily: 'Prompt'),
        ),
        child: child!,
      ),
    );
    if (range != null && mounted) {
      setState(() {
        _customRange = range;
        _period = _HistoryPeriod.custom;
        _listPage = 1;
      });
    }
  }

  Future<void> _refreshHistory() async {
    setState(() => _listPage = 1);
    await context.read<HistoryProvider>().loadAll(refresh: true);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Text('ประวัติการประเมิน', style: AppTextStyles.h4),
        centerTitle: true,
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(0.5),
          child: Divider(height: 0.5, color: AppColors.border),
        ),
      ),
      body: Consumer<HistoryProvider>(
        builder: (context, provider, _) {
          if (provider.isLoading && provider.items.isEmpty) {
            return const AppLoadingView();
          }
          if (provider.error != null && provider.items.isEmpty) {
            return _ErrorState(
              message: provider.error!,
              onRetry: () => provider.loadAll(refresh: true),
            );
          }

          final items = _filtered(provider.items);
          final totalPages = items.isEmpty
              ? 1
              : (items.length / _itemsPerPage).ceil();
          final currentPage = _listPage.clamp(1, totalPages).toInt();
          final pageStart = (currentPage - 1) * _itemsPerPage;
          final pageItems = items
              .skip(pageStart)
              .take(_itemsPerPage)
              .toList();
          return RefreshIndicator(
            color: AppColors.primary,
            onRefresh: _refreshHistory,
            child: CustomScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                SliverToBoxAdapter(
                  child: _PeriodSelector(
                    selected: _period,
                    customRange: _customRange,
                    onSelected: (value) {
                      if (value == _HistoryPeriod.custom) {
                        _pickRange();
                      } else {
                        setState(() {
                          _period = value;
                          _listPage = 1;
                        });
                      }
                    },
                  ),
                ),
                SliverToBoxAdapter(
                  child: _HistoryAnalysis(
                    items: items,
                    period: _period,
                    range: _activeRange(),
                  ),
                ),
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(18, 8, 18, 10),
                    child: Row(
                      children: [
                        Text('รายการประเมิน', style: AppTextStyles.body1Bold),
                        const Spacer(),
                        Text(
                          '${items.length} รายการ',
                          style: AppTextStyles.body2.copyWith(
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                if (items.isEmpty)
                  SliverFillRemaining(
                    hasScrollBody: false,
                    child: _EmptyHistory(
                      hasAnyHistory: provider.items.isNotEmpty,
                    ),
                  )
                else
                  SliverPadding(
                    padding: EdgeInsets.fromLTRB(
                      16,
                      0,
                      16,
                      totalPages > 1 ? 8 : 28,
                    ),
                    sliver: SliverList.builder(
                      itemCount: pageItems.length,
                      itemBuilder: (context, index) =>
                          _HistoryCard(item: pageItems[index]),
                    ),
                  ),
                if (items.isNotEmpty && totalPages > 1)
                  SliverToBoxAdapter(
                    child: _HistoryPagination(
                      currentPage: currentPage,
                      totalPages: totalPages,
                      onPageChanged: (page) {
                        setState(() => _listPage = page);
                      },
                    ),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _ChartPoint {
  final String label;
  final int count;

  const _ChartPoint(this.label, this.count);
}

class _HistoryAnalysis extends StatelessWidget {
  final List<HistoryItemModel> items;
  final _HistoryPeriod period;
  final DateTimeRange range;

  const _HistoryAnalysis({
    required this.items,
    required this.period,
    required this.range,
  });

  static const _weekdays = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'];
  static const _months = [
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
  ];

  List<DateTime> get _dates => items
      .map((item) => DateTime.tryParse(item.createdAt)?.toLocal())
      .whereType<DateTime>()
      .toList();

  List<_ChartPoint> _points() {
    final dates = _dates;
    if (period == _HistoryPeriod.all) {
      if (dates.isEmpty) return const [];
      final firstYear = dates
          .map((date) => date.year)
          .reduce((value, year) => year < value ? year : value);
      final lastYear = dates
          .map((date) => date.year)
          .reduce((value, year) => year > value ? year : value);

      if (firstYear == lastYear) {
        return List.generate(12, (index) {
          final count = dates.where((date) => date.month == index + 1).length;
          return _ChartPoint(_months[index], count);
        });
      }

      return List.generate(lastYear - firstYear + 1, (index) {
        final year = firstYear + index;
        final count = dates.where((date) => date.year == year).length;
        return _ChartPoint('${year + 543}', count);
      });
    }
    if (period == _HistoryPeriod.week) {
      return List.generate(7, (index) {
        final day = range.start.add(Duration(days: index));
        final count = dates.where((date) => _sameDay(date, day)).length;
        return _ChartPoint(_weekdays[day.weekday - 1], count);
      });
    }
    if (period == _HistoryPeriod.month) {
      final lastDay = DateTime(range.start.year, range.start.month + 1, 0).day;
      final weekCount = (lastDay / 7).ceil();
      return List.generate(weekCount, (index) {
        final first = index * 7 + 1;
        final last = (first + 6).clamp(1, lastDay);
        final count = dates
            .where((date) => date.day >= first && date.day <= last)
            .length;
        return _ChartPoint('$first-$last', count);
      });
    }
    if (period == _HistoryPeriod.year) {
      return List.generate(12, (index) {
        final count = dates.where((date) => date.month == index + 1).length;
        return _ChartPoint(_months[index], count);
      });
    }

    final totalDays = range.duration.inDays + 1;
    final groupSize = (totalDays / 7).ceil();
    final groupCount = (totalDays / groupSize).ceil();
    return List.generate(groupCount, (index) {
      final start = range.start.add(Duration(days: index * groupSize));
      final rawEnd = start.add(Duration(days: groupSize - 1));
      final end = rawEnd.isAfter(range.end) ? range.end : rawEnd;
      final count = dates.where((date) {
        final value = DateTime(date.year, date.month, date.day);
        return !value.isBefore(start) && !value.isAfter(end);
      }).length;
      final label = groupSize == 1
          ? '${start.day}'
          : '${start.day} ${thaiAbbreviatedMonths[start.month - 1]}-'
                '${end.day} ${thaiAbbreviatedMonths[end.month - 1]}';
      return _ChartPoint(label, count);
    });
  }

  bool _sameDay(DateTime a, DateTime b) =>
      a.year == b.year && a.month == b.month && a.day == b.day;

  String _subtitle() {
    if (period == _HistoryPeriod.all) {
      return 'ข้อมูลประวัติทั้งหมด';
    }
    if (period == _HistoryPeriod.year) {
      return 'ปี ${range.start.year + 543}';
    }
    if (_sameDay(range.start, range.end)) {
      return 'วันที่ ${_thaiDate(range.start)}';
    }
    return 'วันที่ ${_thaiDate(range.start)} – ${_thaiDate(range.end)}';
  }

  @override
  Widget build(BuildContext context) {
    final points = _points();
    final maxCount = points.fold<int>(
      1,
      (value, point) => point.count > value ? point.count : value,
    );
    final busiest = points.fold<_ChartPoint?>(null, (value, point) {
      if (point.count == 0) return value;
      return value == null || point.count > value.count ? point : value;
    });
    final average = points.isEmpty ? 0 : items.length / points.length;

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
      child: Container(
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: AppColors.white,
          borderRadius: BorderRadius.circular(22),
          border: Border.all(color: AppColors.border),
          boxShadow: [
            BoxShadow(
              color: AppColors.black.withValues(alpha: 0.04),
              blurRadius: 16,
              offset: const Offset(0, 6),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('ภาพรวมการประเมิน', style: AppTextStyles.body1Bold),
            const SizedBox(height: 3),
            Text(
              _subtitle(),
              style: AppTextStyles.body2.copyWith(
                color: AppColors.textSecondary,
                fontWeight: FontWeight.w500,
              ),
            ),
            const SizedBox(height: 18),
            Row(
              children: [
                _Metric(
                  label: 'ทั้งหมด',
                  value: '${items.length}',
                  suffix: 'ครั้ง',
                ),
                const _MetricDivider(),
                _Metric(
                  label: 'เฉลี่ย',
                  value: average.toStringAsFixed(1),
                  suffix: 'ครั้ง/ช่วง',
                ),
                const _MetricDivider(),
                _Metric(
                  label: 'สูงสุด',
                  value: busiest?.label ?? '-',
                  suffix: busiest == null ? '' : '${busiest.count} ครั้ง',
                ),
              ],
            ),
            const SizedBox(height: 22),
            SizedBox(
              height: 145,
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: points.map((point) {
                  final height = point.count == 0
                      ? 5.0
                      : 16 + (62 * point.count / maxCount);
                  return Expanded(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 2),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          Text(
                            '${point.count}',
                            style: AppTextStyles.body3Bold.copyWith(
                              color: AppColors.primary,
                            ),
                          ),
                          const SizedBox(height: 4),
                          AnimatedContainer(
                            duration: const Duration(milliseconds: 250),
                            height: height,
                            constraints: const BoxConstraints(maxWidth: 24),
                            decoration: BoxDecoration(
                              gradient: const LinearGradient(
                                begin: Alignment.bottomCenter,
                                end: Alignment.topCenter,
                                colors: [
                                  AppColors.primary,
                                  AppColors.primaryMid,
                                ],
                              ),
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                          const SizedBox(height: 7),
                          FittedBox(
                            fit: BoxFit.scaleDown,
                            child: Text(
                              point.label,
                              maxLines: 1,
                              style: AppTextStyles.body3.copyWith(
                                color: AppColors.textSecondary,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                }).toList(),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Metric extends StatelessWidget {
  final String label;
  final String value;
  final String suffix;

  const _Metric({
    required this.label,
    required this.value,
    required this.suffix,
  });

  @override
  Widget build(BuildContext context) => Expanded(
    child: Column(
      children: [
        Text(
          label,
          style: AppTextStyles.body3.copyWith(color: AppColors.textSecondary),
        ),
        const SizedBox(height: 3),
        Text(
          value,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: AppTextStyles.body1Bold.copyWith(color: AppColors.primary),
        ),
        Text(
          suffix,
          maxLines: 1,
          style: AppTextStyles.body3.copyWith(color: AppColors.textSecondary),
        ),
      ],
    ),
  );
}

class _MetricDivider extends StatelessWidget {
  const _MetricDivider();

  @override
  Widget build(BuildContext context) => const SizedBox(
    height: 44,
    child: VerticalDivider(width: 12, color: AppColors.border),
  );
}

class _PeriodSelector extends StatelessWidget {
  final _HistoryPeriod selected;
  final DateTimeRange? customRange;
  final ValueChanged<_HistoryPeriod> onSelected;

  const _PeriodSelector({
    required this.selected,
    required this.customRange,
    required this.onSelected,
  });

  @override
  Widget build(BuildContext context) {
    final labels = <_HistoryPeriod, String>{
      _HistoryPeriod.all: 'ทั้งหมด',
      _HistoryPeriod.week: 'รายสัปดาห์',
      _HistoryPeriod.month: 'รายเดือน',
      _HistoryPeriod.year: 'รายปี',
      _HistoryPeriod.custom: customRange == null
          ? 'เลือกวันที่'
          : '${_shortDate(customRange!.start)} – ${_shortDate(customRange!.end)}',
    };
    return SizedBox(
      height: 62,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.fromLTRB(16, 12, 8, 6),
        children: labels.entries.map((entry) {
          final active = selected == entry.key;
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: ChoiceChip(
              selected: active,
              onSelected: (_) => onSelected(entry.key),
              showCheckmark: false,
              avatar: entry.key == _HistoryPeriod.custom
                  ? Icon(
                      Icons.calendar_month_outlined,
                      size: 17,
                      color: active ? AppColors.white : AppColors.textSecondary,
                    )
                  : null,
              label: Text(entry.value),
              labelStyle: AppTextStyles.body2.copyWith(
                color: active ? AppColors.white : AppColors.textSecondary,
                fontWeight: active ? FontWeight.w600 : FontWeight.w400,
              ),
              backgroundColor: AppColors.white,
              selectedColor: AppColors.primary,
              side: BorderSide(
                color: active ? AppColors.primary : AppColors.border,
              ),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(24),
              ),
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 9),
            ),
          );
        }).toList(),
      ),
    );
  }
}

class _HistoryPagination extends StatelessWidget {
  final int currentPage;
  final int totalPages;
  final ValueChanged<int> onPageChanged;

  const _HistoryPagination({
    required this.currentPage,
    required this.totalPages,
    required this.onPageChanged,
  });

  @override
  Widget build(BuildContext context) {
    final firstPage = (currentPage - 2).clamp(
      1,
      (totalPages - 4).clamp(1, totalPages),
    ).toInt();
    final lastPage = (firstPage + 4).clamp(1, totalPages).toInt();

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          IconButton(
            tooltip: 'หน้าก่อนหน้า',
            onPressed: currentPage > 1
                ? () => onPageChanged(currentPage - 1)
                : null,
            icon: const Icon(Icons.chevron_left_rounded),
          ),
          for (var page = firstPage; page <= lastPage; page++)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 2),
              child: page == currentPage
                  ? FilledButton(
                      onPressed: null,
                      style: FilledButton.styleFrom(
                        disabledBackgroundColor: AppColors.primary,
                        disabledForegroundColor: AppColors.white,
                        minimumSize: const Size(40, 40),
                        padding: EdgeInsets.zero,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: Text('$page'),
                    )
                  : TextButton(
                      onPressed: () => onPageChanged(page),
                      style: TextButton.styleFrom(
                        foregroundColor: AppColors.textSecondary,
                        minimumSize: const Size(40, 40),
                        padding: EdgeInsets.zero,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: Text('$page'),
                    ),
            ),
          IconButton(
            tooltip: 'หน้าถัดไป',
            onPressed: currentPage < totalPages
                ? () => onPageChanged(currentPage + 1)
                : null,
            icon: const Icon(Icons.chevron_right_rounded),
          ),
        ],
      ),
    );
  }
}

class _EmptyHistory extends StatelessWidget {
  final bool hasAnyHistory;

  const _EmptyHistory({required this.hasAnyHistory});

  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.fromLTRB(40, 16, 40, 80),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 80,
            height: 80,
            decoration: const BoxDecoration(
              color: AppColors.primaryLight,
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.history_toggle_off_rounded,
              size: 38,
              color: AppColors.primary,
            ),
          ),
          const SizedBox(height: 16),
          Text(
            hasAnyHistory
                ? 'ไม่พบประวัติในช่วงนี้'
                : 'ยังไม่มีประวัติการประเมิน',
            style: AppTextStyles.body1Bold,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 6),
          Text(
            hasAnyHistory
                ? 'ลองเลือกช่วงเวลาอื่นเพื่อดูข้อมูล'
                : 'เมื่อบันทึกผลการประเมิน รายการจะแสดงที่นี่',
            style: AppTextStyles.body2.copyWith(color: AppColors.textSecondary),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    ),
  );
}

class _ErrorState extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const _ErrorState({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) => Center(
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(message, style: AppTextStyles.body1),
        const SizedBox(height: 12),
        OutlinedButton.icon(
          onPressed: onRetry,
          icon: const Icon(Icons.refresh_rounded),
          label: const Text('ลองอีกครั้ง'),
        ),
      ],
    ),
  );
}

class _HistoryCard extends StatelessWidget {
  final HistoryItemModel item;

  const _HistoryCard({required this.item});

  String _formatDate(String value) {
    final date = DateTime.tryParse(value)?.toLocal();
    if (date == null) return '-';
    return '${_thaiDate(date)} · ${DateFormat('HH:mm').format(date)} น.';
  }

  @override
  Widget build(BuildContext context) {
    final result = item.topResult;
    final requiresMedicalCare = result != null &&
        const ['R', 'P', 'Y'].contains(result.urgencyLevel);
    final statusColor = requiresMedicalCare
        ? AppColors.danger
        : AppColors.primary;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border),
        boxShadow: [
          BoxShadow(
            color: AppColors.black.withValues(alpha: 0.025),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(18),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => HistoryDetailScreen(assessmentId: item.id),
            ),
          ),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                Container(
                  width: 42,
                  height: 42,
                  alignment: Alignment.center,
                  decoration: const BoxDecoration(
                    color: AppColors.surfacePrimary,
                    borderRadius: BorderRadius.all(Radius.circular(12)),
                  ),
                  child: SymptomIcon(
                    iconName: item.symptomIcon,
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
                        item.symptomName,
                        style: AppTextStyles.body1Bold,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      if (result != null) ...[
                        const SizedBox(height: 2),
                        Text(
                          AppColors.urgencyLabel(result.urgencyLevel),
                          style: AppTextStyles.body3Bold.copyWith(
                            color: statusColor,
                          ),
                        ),
                      ],
                      const SizedBox(height: 5),
                      Row(
                        children: [
                          const Icon(
                            Icons.schedule_rounded,
                            size: 14,
                            color: AppColors.textHint,
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              _formatDate(item.createdAt),
                              style: AppTextStyles.body3.copyWith(
                                color: AppColors.textSecondary,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const Icon(
                  Icons.chevron_right_rounded,
                  color: AppColors.textHint,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

String _thaiDate(DateTime date) => formatThaiDate(date);

String _shortDate(DateTime date) =>
    '${date.day} ${thaiAbbreviatedMonths[date.month - 1]} '
    '${(date.year + 543).toString().substring(2)}';
