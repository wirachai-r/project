import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../data/models/history_model.dart';
import '../providers/history_provider.dart';
import 'history_detail_screen.dart';

enum _HistoryPeriod { all, today, month, year, custom }

class HistoryListScreen extends StatefulWidget {
  const HistoryListScreen({super.key});

  @override
  State<HistoryListScreen> createState() => _HistoryListScreenState();
}

class _HistoryListScreenState extends State<HistoryListScreen> {
  final _scrollCtrl = ScrollController();
  _HistoryPeriod _period = _HistoryPeriod.all;
  DateTimeRange? _customRange;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<HistoryProvider>().load(refresh: true);
    });
    _scrollCtrl.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollCtrl.position.pixels >=
        _scrollCtrl.position.maxScrollExtent - 200) {
      context.read<HistoryProvider>().load();
    }
  }

  DateTime? _dateOf(HistoryItemModel item) =>
      DateTime.tryParse(item.createdAt)?.toLocal();

  bool _sameDay(DateTime a, DateTime b) =>
      a.year == b.year && a.month == b.month && a.day == b.day;

  List<HistoryItemModel> _filtered(List<HistoryItemModel> items) {
    final now = DateTime.now();
    return items.where((item) {
      final date = _dateOf(item);
      if (date == null || _period == _HistoryPeriod.all) return true;
      if (_period == _HistoryPeriod.today) return _sameDay(date, now);
      if (_period == _HistoryPeriod.month) {
        return date.year == now.year && date.month == now.month;
      }
      if (_period == _HistoryPeriod.year) return date.year == now.year;
      final range = _customRange;
      if (range == null) return true;
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
      );
      return !date.isBefore(start) && !date.isAfter(end);
    }).toList();
  }

  Future<void> _pickRange() async {
    final now = DateTime.now();
    final range = await showDateRangePicker(
      context: context,
      firstDate: DateTime(now.year - 10),
      lastDate: now,
      initialDateRange: _customRange,
      helpText: 'เลือกช่วงวันที่',
      cancelText: 'ยกเลิก',
      confirmText: 'เลือก',
      saveText: 'บันทึก',
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
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF6F6FB),
      appBar: AppBar(
        backgroundColor: AppColors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Text('ประวัติการประเมิน', style: AppTextStyles.h4),
        centerTitle: true,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(0.5),
          child: Divider(height: 0.5, thickness: 0.5, color: AppColors.border),
        ),
      ),
      body: Consumer<HistoryProvider>(
        builder: (context, provider, _) {
          if (provider.isLoading && provider.items.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }
          if (provider.error != null && provider.items.isEmpty) {
            return Center(
              child: Text(provider.error!, style: AppTextStyles.body1),
            );
          }

          final items = _filtered(provider.items);
          return RefreshIndicator(
            color: AppColors.primary,
            backgroundColor: AppColors.white,
            elevation: 0,
            onRefresh: () => provider.load(refresh: true),
            child: CustomScrollView(
              controller: _scrollCtrl,
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                SliverToBoxAdapter(child: _HistoryChart(items: provider.items)),
                SliverToBoxAdapter(
                  child: _PeriodSelector(
                    selected: _period,
                    customRange: _customRange,
                    onSelected: (value) {
                      if (value == _HistoryPeriod.custom) {
                        _pickRange();
                      } else {
                        setState(() => _period = value);
                      }
                    },
                  ),
                ),
                if (items.isEmpty)
                  SliverFillRemaining(
                    hasScrollBody: false,
                    child: _EmptyHistory(filtered: provider.items.isNotEmpty),
                  )
                else
                  SliverPadding(
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                    sliver: SliverList(
                      delegate: SliverChildBuilderDelegate(
                        (context, i) => i == items.length
                            ? const Padding(
                                padding: EdgeInsets.all(16),
                                child: Center(
                                  child: CircularProgressIndicator(),
                                ),
                              )
                            : _HistoryCard(item: items[i]),
                        childCount:
                            items.length + (provider.isLoadingMore ? 1 : 0),
                      ),
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

class _HistoryChart extends StatelessWidget {
  final List<HistoryItemModel> items;

  const _HistoryChart({required this.items});

  static const _weekdayLabels = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'];

  @override
  Widget build(BuildContext context) {
    final today = DateTime.now();
    final days = List.generate(7, (index) {
      final value = today.subtract(Duration(days: 6 - index));
      return DateTime(value.year, value.month, value.day);
    });
    final counts = days.map((day) {
      return items.where((item) {
        final date = DateTime.tryParse(item.createdAt)?.toLocal();
        return date != null &&
            date.year == day.year &&
            date.month == day.month &&
            date.day == day.day;
      }).length;
    }).toList();
    final maxCount = counts.fold<int>(
      1,
      (max, count) => count > max ? count : max,
    );

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 14),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.fromLTRB(18, 16, 18, 14),
        decoration: BoxDecoration(
          color: AppColors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: AppColors.border),
          boxShadow: [
            BoxShadow(
              color: AppColors.black.withValues(alpha: 0.04),
              blurRadius: 14,
              offset: const Offset(0, 6),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('แนวโน้มการประเมิน', style: AppTextStyles.body1Bold),
            const SizedBox(height: 2),
            Text(
              '7 วันล่าสุด',
              style: AppTextStyles.body3.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              height: 124,
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: List.generate(days.length, (index) {
                  final count = counts[index];
                  final isToday = index == days.length - 1;
                  final barHeight = count == 0
                      ? 5.0
                      : 12 + (58 * count / maxCount);
                  return Expanded(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        Text(
                          '$count',
                          style: AppTextStyles.body3Bold.copyWith(
                            color: isToday
                                ? AppColors.primary
                                : AppColors.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 4),
                        AnimatedContainer(
                          duration: const Duration(milliseconds: 250),
                          width: 18,
                          height: barHeight,
                          decoration: BoxDecoration(
                            color: isToday
                                ? AppColors.primary
                                : AppColors.primaryLight,
                            borderRadius: BorderRadius.circular(9),
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          _weekdayLabels[days[index].weekday - 1],
                          style:
                              (isToday
                                      ? AppTextStyles.body3Bold
                                      : AppTextStyles.body3)
                                  .copyWith(
                                    color: isToday
                                        ? AppColors.primary
                                        : AppColors.textSecondary,
                                  ),
                        ),
                      ],
                    ),
                  );
                }),
              ),
            ),
          ],
        ),
      ),
    );
  }
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
      _HistoryPeriod.today: 'วันนี้',
      _HistoryPeriod.month: 'เดือนนี้',
      _HistoryPeriod.year: 'ปีนี้',
      _HistoryPeriod.custom: customRange == null ? 'เลือกวันที่' : 'ช่วงวันที่',
    };
    return SizedBox(
      height: 46,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        children: labels.entries.map((entry) {
          final active = selected == entry.key;
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: InkWell(
              onTap: () => onSelected(entry.key),
              borderRadius: BorderRadius.circular(30),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 180),
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 10,
                ),
                decoration: BoxDecoration(
                  color: active ? AppColors.primary : AppColors.white,
                  borderRadius: BorderRadius.circular(30),
                  border: Border.all(
                    color: active ? AppColors.primary : AppColors.border,
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    if (entry.key == _HistoryPeriod.custom) ...[
                      Icon(
                        Icons.calendar_month_outlined,
                        size: 16,
                        color: active
                            ? AppColors.white
                            : AppColors.textSecondary,
                      ),
                      const SizedBox(width: 6),
                    ],
                    Text(
                      entry.value,
                      style: AppTextStyles.body2.copyWith(
                        color: active
                            ? AppColors.white
                            : AppColors.textSecondary,
                        fontWeight: active ? FontWeight.w600 : FontWeight.w400,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        }).toList(),
      ),
    );
  }
}

class _EmptyHistory extends StatelessWidget {
  final bool filtered;
  const _EmptyHistory({required this.filtered});

  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.symmetric(horizontal: 40),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 84,
            height: 84,
            decoration: BoxDecoration(
              color: AppColors.primaryLight,
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.history_toggle_off_rounded,
              size: 40,
              color: AppColors.primary,
            ),
          ),
          const SizedBox(height: 16),
          Text(
            filtered
                ? 'ไม่พบประวัติในช่วงเวลานี้'
                : 'ยังไม่มีประวัติการประเมิน',
            style: AppTextStyles.body1Bold,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 6),
          Text(
            filtered
                ? 'ลองเปลี่ยนช่วงเวลาที่ต้องการค้นหาดูนะ'
                : 'เริ่มประเมินอาการเพื่อดูประวัติที่นี่',
            style: AppTextStyles.body2.copyWith(color: AppColors.textSecondary),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    ),
  );
}

class _HistoryCard extends StatelessWidget {
  final HistoryItemModel item;
  const _HistoryCard({required this.item});

  String _formatDate(String value) {
    final date = DateTime.tryParse(value)?.toLocal();
    if (date == null) return '-';
    final time = DateFormat('HH:mm').format(date);
    return '${date.day.toString().padLeft(2, '0')}/'
        '${date.month.toString().padLeft(2, '0')}/${date.year + 543} · $time น.';
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: AppColors.black.withValues(alpha: 0.04),
            blurRadius: 14,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(20),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => HistoryDetailScreen(assessmentId: item.id),
            ),
          ),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              item.symptomName,
                              style: AppTextStyles.body1Bold,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 10,
                              vertical: 5,
                            ),
                            decoration: BoxDecoration(
                              color:
                                  (item.isCompleted
                                          ? AppColors.success
                                          : AppColors.warning)
                                      .withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              item.isCompleted ? 'เสร็จสิ้น' : 'ดำเนินการ',
                              style: AppTextStyles.body3Bold.copyWith(
                                color: item.isCompleted
                                    ? AppColors.success
                                    : AppColors.warning,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          const Icon(
                            Icons.access_time_rounded,
                            size: 14,
                            color: AppColors.textSecondary,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            _formatDate(item.createdAt),
                            style: AppTextStyles.body3.copyWith(
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 4),
                const Icon(
                  Icons.arrow_forward_ios_rounded,
                  size: 14,
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
