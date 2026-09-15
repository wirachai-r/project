import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/buddhist_calendar_delegate.dart';
import '../../../core/utils/responsive.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../data/models/health_episode_model.dart';
import '../../../data/repositories/personal_health_repository.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../shared/widgets/app_layout.dart';
import 'follow_up_screen.dart';

class HealthEpisodeListScreen extends StatefulWidget {
  const HealthEpisodeListScreen({super.key});

  @override
  State<HealthEpisodeListScreen> createState() =>
      _HealthEpisodeListScreenState();
}

class _HealthEpisodeListScreenState extends State<HealthEpisodeListScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;
  List<HealthEpisodeModel> _episodes = const [];
  String? _statusFilter;
  String _endedPeriod = 'week';
  DateTime _periodAnchor = DateTime.now();
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this)
      ..addListener(_handleTabChange);
    _load();
  }

  @override
  void dispose() {
    _tabController
      ..removeListener(_handleTabChange)
      ..dispose();
    super.dispose();
  }

  void _handleTabChange() {
    final status = [null, 'A', 'P', 'E'][_tabController.index];
    if (_statusFilter != status) setState(() => _statusFilter = status);
  }

  List<HealthEpisodeModel> get _filteredEpisodes {
    final items = _episodes.where((episode) {
      if (_statusFilter != null && episode.status != _statusFilter) {
        return false;
      }
      if (_statusFilter != 'E') return true;
      final endedAt = episode.endedAt;
      if (endedAt == null) return false;
      final date = _dateOnly(endedAt.toLocal());
      final range = _endedRange;
      return !date.isBefore(range.start) && !date.isAfter(range.end);
    }).toList();
    items.sort((a, b) => b.startedAt.compareTo(a.startedAt));
    return items;
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final episodes = await context
          .read<PersonalHealthRepository>()
          .healthEpisodes();
      if (!mounted) return;
      setState(() => _episodes = episodes);
    } catch (_) {
      if (mounted) setState(() => _error = 'ไม่สามารถโหลดรายการติดตามได้');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        centerTitle: true,
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        surfaceTintColor: Colors.transparent,
        title: Text('การติดตามอาการทั้งหมด', style: AppTextStyles.h4),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(61),
          child: Column(
            children: [
              Divider(
                height: 1,
                thickness: 1,
                color: Theme.of(context).colorScheme.outlineVariant,
              ),
              AppContentWidth(
                shrinkWrapHeight: true,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 6, 16, 6),
                  child: Container(
                    height: 48,
                    padding: const EdgeInsets.all(3),
                    decoration: BoxDecoration(
                      color: Color.alphaBlend(
                        Theme.of(
                          context,
                        ).colorScheme.onSurface.withValues(alpha: 0.08),
                        Theme.of(context).colorScheme.surface,
                      ),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: TabBar(
                      controller: _tabController,
                      labelColor: AppColors.primary,
                      unselectedLabelColor: Theme.of(
                        context,
                      ).colorScheme.onSurface,
                      labelStyle: AppTextStyles.body2Bold,
                      unselectedLabelStyle: AppTextStyles.body2,
                      indicatorSize: TabBarIndicatorSize.tab,
                      indicator: BoxDecoration(
                        color: AppColors.primaryLight,
                        borderRadius: BorderRadius.circular(13),
                      ),
                      dividerColor: Colors.transparent,
                      splashBorderRadius: BorderRadius.circular(13),
                      tabs: const [
                        Tab(text: 'ทั้งหมด'),
                        Tab(text: 'กำลังติดตาม'),
                        Tab(text: 'หยุดพัก'),
                        Tab(text: 'สิ้นสุดแล้ว'),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
      body: _loading
        ? const AppLoadingView(label: 'กำลังโหลดการติดตาม...')
        : _error != null
        ? AppMessageView.error(message: _error!, onAction: _load)
        : _episodes.isEmpty
        ? const AppMessageView.empty(
            title: 'ยังไม่มีการติดตามอาการ',
            message:
                'เริ่มติดตามได้จากผลประเมินที่บันทึกไว้ในหน้าประวัติ แล้วกลับมาดูรายการทั้งหมดที่หน้านี้',
          )
        : RefreshIndicator(
            onRefresh: _load,
            child: ResponsiveBuilder(
              builder: (context) => AppContentWidth(
                child: LayoutBuilder(
                  builder: (context, constraints) => ListView(
                    padding: EdgeInsets.fromLTRB(
                      Responsive.horizontalPadding,
                      12,
                      Responsive.horizontalPadding,
                      32,
                    ),
                    children: [
                      if (_statusFilter == 'E') ...[
                        _endedPeriodSelector(),
                        const SizedBox(height: 12),
                      ],
                      if (_filteredEpisodes.isEmpty)
                        SizedBox(
                          height: _emptyStateHeight(constraints.maxHeight),
                          child: const AppMessageView.empty(
                            title: 'ไม่พบรายการในช่วงนี้',
                            message: 'ลองเปลี่ยนสถานะหรือช่วงเวลาที่เลือก',
                          ),
                        )
                      else ...[
                        _section('กำลังติดตาม', 'A'),
                        _section('หยุดชั่วคราว', 'P'),
                        _section('สิ้นสุดแล้ว', 'E'),
                      ],
                    ],
                  ),
                ),
              ),
            ),
          ),
  );

  double _emptyStateHeight(double availableHeight) {
    final controlsHeight = _statusFilter == 'E' ? 130.0 : 0.0;
    final remainingHeight = availableHeight - controlsHeight;
    return remainingHeight < 160 ? 160 : remainingHeight;
  }

  DateTime _dateOnly(DateTime date) => DateTime(date.year, date.month, date.day);

  DateTimeRange get _endedRange {
    final anchor = _dateOnly(_periodAnchor);
    return switch (_endedPeriod) {
      'month' => DateTimeRange(
        start: DateTime(anchor.year, anchor.month),
        end: DateTime(anchor.year, anchor.month + 1, 0),
      ),
      'year' => DateTimeRange(
        start: DateTime(anchor.year),
        end: DateTime(anchor.year, 12, 31),
      ),
      _ => DateTimeRange(
        start: anchor.subtract(Duration(days: anchor.weekday - 1)),
        end: anchor.add(Duration(days: 7 - anchor.weekday)),
      ),
    };
  }

  bool get _canMoveToNextPeriod => _endedRange.end.isBefore(
    _dateOnly(DateTime.now()),
  );

  void _selectEndedPeriod(String period) => setState(() {
    _endedPeriod = period;
    _periodAnchor = DateTime.now();
  });

  void _moveEndedPeriod(int amount) => setState(() {
    _periodAnchor = switch (_endedPeriod) {
      'month' => DateTime(
        _periodAnchor.year,
        _periodAnchor.month + amount,
        1,
      ),
      'year' => DateTime(_periodAnchor.year + amount, 1, 1),
      _ => _periodAnchor.add(Duration(days: 7 * amount)),
    };
  });

  Future<void> _pickEndedPeriod() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      calendarDelegate: const BuddhistCalendarDelegate(),
      locale: const Locale('th', 'TH'),
      firstDate: DateTime(now.year - 10),
      lastDate: now,
      initialDate: _periodAnchor.isAfter(now) ? now : _periodAnchor,
      initialDatePickerMode: _endedPeriod == 'year'
          ? DatePickerMode.year
          : DatePickerMode.day,
      helpText: switch (_endedPeriod) {
        'month' => 'เลือกวันในเดือน',
        'year' => 'เลือกปี',
        _ => 'เลือกวันในสัปดาห์',
      },
      cancelText: 'ยกเลิก',
      confirmText: 'เลือก',
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
    if (picked != null && mounted) {
      setState(() => _periodAnchor = picked);
    }
  }

  Widget _endedPeriodSelector() => Column(
    children: [
      SizedBox(
        height: 50,
        child: ListView(
          scrollDirection: Axis.horizontal,
          children: [
            _periodChip('รายสัปดาห์', 'week'),
            _periodChip('รายเดือน', 'month'),
            _periodChip('รายปี', 'year'),
          ],
        ),
      ),
      const SizedBox(height: 10),
      Material(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          height: 58,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: Theme.of(context).colorScheme.outlineVariant,
            ),
          ),
          child: Row(
            children: [
              IconButton(
                tooltip: 'ช่วงก่อนหน้า',
                onPressed: () => _moveEndedPeriod(-1),
                icon: const Icon(Icons.chevron_left_rounded),
              ),
              Expanded(
                child: InkWell(
                  onTap: _pickEndedPeriod,
                  borderRadius: BorderRadius.circular(12),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Flexible(
                        child: Text(
                          _endedPeriodLabel,
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
                onPressed: _canMoveToNextPeriod
                    ? () => _moveEndedPeriod(1)
                    : null,
                icon: const Icon(Icons.chevron_right_rounded),
              ),
            ],
          ),
        ),
      ),
    ],
  );

  Widget _periodChip(String label, String period) {
    final selected = _endedPeriod == period;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        selected: selected,
        onSelected: (_) => _selectEndedPeriod(period),
        showCheckmark: false,
        label: Text(label),
        labelStyle: AppTextStyles.body2.copyWith(
          color: selected
              ? AppColors.white
              : Theme.of(context).colorScheme.onSurfaceVariant,
          fontWeight: selected ? FontWeight.w600 : FontWeight.w400,
        ),
        backgroundColor: Theme.of(context).colorScheme.surface,
        selectedColor: AppColors.primary,
        side: BorderSide(
          color: selected
              ? AppColors.primary
              : Theme.of(context).colorScheme.outlineVariant,
        ),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
      ),
    );
  }

  String get _endedPeriodLabel {
    final range = _endedRange;
    return switch (_endedPeriod) {
      'month' =>
        '${thaiAbbreviatedMonths[range.start.month - 1]} ${range.start.year + 543}',
      'year' => 'ปี ${range.start.year + 543}',
      _ => '${formatShortThaiDate(range.start)} – ${formatShortThaiDate(range.end)}',
    };
  }

  Widget _section(String title, String status) {
    final items = _filteredEpisodes
        .where((item) => item.status == status)
        .toList();
    if (items.isEmpty) return const SizedBox.shrink();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(2, 8, 2, 10),
          child: Text('$title (${items.length})', style: AppTextStyles.h3),
        ),
        ...items.map(_episodeCard),
        const SizedBox(height: 14),
      ],
    );
  }

  Widget _episodeCard(HealthEpisodeModel episode) {
    final primary = episode.symptoms.where((item) => item.isPrimary);
    final name = primary.isNotEmpty
        ? primary.first.symptomName
        : episode.symptoms.map((item) => item.symptomName).join(', ');
    final entries = episode.symptoms.expand((item) => item.entries).toList()
      ..sort((a, b) => b.recordedAt.compareTo(a.recordedAt));
    final startedAt = episode.startedAt.toLocal();
    final latest = entries.isEmpty ? null : entries.first.recordedAt.toLocal();
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 54,
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surfaceContainerLow,
            borderRadius: BorderRadius.circular(16),
          ),
          child: Column(
            children: [
              Text(
                startedAt.day.toString().padLeft(2, '0'),
                style: AppTextStyles.body1Bold.copyWith(
                  color: AppColors.primary,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                thaiAbbreviatedMonths[startedAt.month - 1],
                style: AppTextStyles.body3Bold.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Container(
            margin: const EdgeInsets.only(bottom: 10),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(
                color: Theme.of(context).colorScheme.outlineVariant,
              ),
            ),
            child: Material(
              color: Colors.transparent,
              child: InkWell(
                borderRadius: BorderRadius.circular(18),
                onTap: () async {
                        await Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => FollowUpScreen(
                              episodeId: episode.id,
                              symptomName: name,
                            ),
                          ),
                        );
                        if (mounted) _load();
                      },
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              name.isEmpty ? 'อาการที่ติดตาม' : name,
                              style: AppTextStyles.body1Bold,
                            ),
                          ),
                          const Icon(Icons.chevron_right_rounded),
                        ],
                      ),
                      const SizedBox(height: 14),
                      Row(
                        children: [
                          Icon(
                            Icons.schedule_rounded,
                            size: 14,
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurfaceVariant,
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              latest == null
                                  ? _trackingDayLabel(episode)
                                  : 'ล่าสุด ${formatThaiDateTime(latest)} · ${_trackingDayLabel(episode)}',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: AppTextStyles.body3.copyWith(
                                color: Theme.of(
                                  context,
                                ).colorScheme.onSurfaceVariant,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }

  String _trackingDayLabel(HealthEpisodeModel episode) {
    final end = episode.endedAt ?? DateTime.now();
    final startDate = DateTime(
      episode.startedAt.toLocal().year,
      episode.startedAt.toLocal().month,
      episode.startedAt.toLocal().day,
    );
    final endDate = DateTime(
      end.toLocal().year,
      end.toLocal().month,
      end.toLocal().day,
    );
    final days = endDate.difference(startDate).inDays + 1;
    return 'วันที่ ${days < 1 ? 1 : days}';
  }
}
