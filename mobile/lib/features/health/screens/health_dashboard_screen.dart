import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
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
  int _days = 30;
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

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final generation = ++_loadGeneration;
    final repository = context.read<PersonalHealthRepository>();
    if (_data != null && mounted) setState(() => _refreshing = true);
    try {
      final result = await repository.dashboard(
        days: _days,
        from: _customRange == null ? null : _dateParam(_customRange!.start),
        to: _customRange == null ? null : _dateParam(_customRange!.end),
      );
      if (!mounted || generation != _loadGeneration) return;
      setState(() {
        _data = result;
        _aiSummary = null;
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
    setState(() => _analyzing = true);
    try {
      final result = await context
          .read<PersonalHealthRepository>()
          .aiTrendSummary(
            days: _days == 0 ? 30 : _days,
            from: _customRange == null ? null : _dateParam(_customRange!.start),
            to: _customRange == null ? null : _dateParam(_customRange!.end),
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
      bottom: const PreferredSize(
        preferredSize: Size.fromHeight(1),
        child: Divider(height: 1, thickness: 1, color: AppColors.border),
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
    final trend = List<dynamic>.from(_data!['severity_trend'] ?? []);
    final symptomTrends = List<dynamic>.from(_data!['symptom_trends'] ?? []);
    final dailyStatuses = List<dynamic>.from(
      _data!['daily_status_trend'] ?? [],
    );
    final analysis = Map<String, dynamic>.from(
      _data!['statistical_analysis'] ?? {},
    );

    return AppContentWidth(
      maxWidth: 720,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
        children: [
          _buildHero(),
          const SizedBox(height: 16),
          LayoutBuilder(
            builder: (context, constraints) {
              final textScale = MediaQuery.textScalerOf(context).scale(14) / 14;
              final singleColumn =
                  constraints.maxWidth < 350 || textScale > 1.25;
              final width = singleColumn
                  ? constraints.maxWidth
                  : (constraints.maxWidth - 12) / 2;
              return Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [
                  _MetricCard(
                    width: width,
                    label: 'การประเมิน',
                    value: summary['assessment_count'] ?? 0,
                    icon: Icons.fact_check_outlined,
                  ),
                  _MetricCard(
                    width: width,
                    label: 'ติดตามอาการ',
                    value: summary['follow_up_count'] ?? 0,
                    icon: Icons.timeline_rounded,
                  ),
                  _MetricCard(
                    width: singleColumn ? width : constraints.maxWidth,
                    label: 'ควรเฝ้าระวัง',
                    value: summary['urgent_count'] ?? 0,
                    icon: Icons.health_and_safety_outlined,
                    accent: AppColors.warning,
                  ),
                ],
              );
            },
          ),
          const SizedBox(height: 24),
          _HealthPeriodSelector(
            selectedDays: _days,
            customRange: _customRange,
            onSelected: (days) {
              if (days == 0) {
                _pickCustomRange();
              } else {
                setState(() {
                  _days = days;
                  _customRange = null;
                });
                _load();
              }
            },
          ),
          if (_customRange != null) ...[
            const SizedBox(height: 10),
            Text(
              'ช่วง ${_formatRangeDate(_customRange!.start)} – '
              '${_formatRangeDate(_customRange!.end)}',
              textAlign: TextAlign.center,
              style: AppTextStyles.body3.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
          ],
          const SizedBox(height: 20),
          _StatisticalAnalysisCard(analysis: analysis),
          const SizedBox(height: 16),
          _AiAnalysisSection(
            data: _aiSummary,
            loading: _analyzing,
            onAnalyze: _analyzeWithAi,
          ),
          const SizedBox(height: 28),
          const AppSectionHeader(title: 'อาการที่พบบ่อย'),
          const SizedBox(height: 12),
          if (symptoms.isEmpty)
            const _InlineEmpty(
              icon: Icons.monitor_heart_outlined,
              text: 'ยังไม่มีประวัติการประเมิน',
            )
          else
            ...symptoms.map((item) => _FrequentSymptomCard(item: item)),
          const SizedBox(height: 28),
          const AppSectionHeader(title: 'แนวโน้มความรุนแรงล่าสุด'),
          const SizedBox(height: 12),
          if (symptomTrends.isNotEmpty)
            ...symptomTrends.map((series) {
              final item = Map<String, dynamic>.from(series);
              final entries = List<dynamic>.from(item['entries'] ?? const []);
              return Card(
                margin: const EdgeInsets.only(bottom: 10),
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              item['symptom_name']?.toString() ??
                                  'ไม่ระบุอาการ',
                              style: AppTextStyles.body1Bold,
                            ),
                          ),
                          if (item['is_primary'] == true)
                            const Chip(
                              label: Text('อาการหลัก'),
                              visualDensity: VisualDensity.compact,
                            ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      _SeverityBars(trend: entries),
                    ],
                  ),
                ),
              );
            })
          else
            Card(
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: trend.isEmpty
                    ? const _InlineEmpty(
                        icon: Icons.show_chart_rounded,
                        text: 'แนวโน้มจะแสดงเมื่อคุณเริ่มบันทึกติดตามอาการ',
                      )
                    : _SeverityBars(trend: trend),
              ),
            ),
          const SizedBox(height: 28),
          const AppSectionHeader(title: 'วันที่บันทึกว่ามีอาการ'),
          const SizedBox(height: 12),
          _DailyStatusSummary(items: dailyStatuses, days: _activeDays),
        ],
      ),
    );
  }

  String _formatRangeDate(DateTime value) =>
      '${value.day}/${value.month}/${value.year + 543}';

  Widget _buildHero() => Container(
    padding: const EdgeInsets.all(20),
    decoration: BoxDecoration(
      color: AppColors.primary,
      borderRadius: BorderRadius.circular(16),
    ),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.16),
            borderRadius: BorderRadius.circular(14),
          ),
          child: const Icon(Icons.favorite_rounded, color: Colors.white),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'ภาพรวมสุขภาพของคุณ',
                style: AppTextStyles.h4.copyWith(color: Colors.white),
              ),
              const SizedBox(height: 4),
              Text(
                'สรุปจากข้อมูลที่คุณบันทึกไว้ในแอป',
                style: AppTextStyles.body2.copyWith(
                  color: Colors.white.withValues(alpha: 0.82),
                  height: 1.45,
                ),
              ),
            ],
          ),
        ),
      ],
    ),
  );
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
                color: AppColors.textSecondary,
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
      0: customRange == null ? 'เลือกวันที่' : 'ช่วงที่เลือก',
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
                            : AppColors.textSecondary,
                      )
                    : null,
                label: Text(entry.value),
                labelStyle: AppTextStyles.body2.copyWith(
                  color: active ? AppColors.white : AppColors.textSecondary,
                  fontWeight: active ? FontWeight.w700 : FontWeight.w400,
                ),
                backgroundColor: AppColors.white,
                selectedColor: AppColors.primary,
                side: BorderSide(
                  color: active ? AppColors.primary : AppColors.border,
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

class _FrequentSymptomCard extends StatelessWidget {
  final dynamic item;

  const _FrequentSymptomCard({required this.item});

  @override
  Widget build(BuildContext context) {
    final name = item['symptom_name']?.toString().trim();
    final count = item['count'] ?? 0;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border),
      ),
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
                Text(
                  'พบในการประเมิน $count ครั้ง',
                  style: AppTextStyles.body3.copyWith(
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
          Container(
            constraints: const BoxConstraints(minWidth: 38),
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: AppColors.surfacePrimary,
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

class _StatisticalAnalysisCard extends StatelessWidget {
  final Map<String, dynamic> analysis;

  const _StatisticalAnalysisCard({required this.analysis});

  String _number(dynamic value, {String suffix = ''}) {
    if (value == null) return 'ข้อมูลไม่เพียงพอ';
    final number = value is num ? value : num.tryParse('$value');
    if (number == null) return 'ข้อมูลไม่เพียงพอ';
    final text = number % 1 == 0
        ? number.toInt().toString()
        : number.toStringAsFixed(1);
    return '$text$suffix';
  }

  String _change(Map<String, dynamic> series) {
    final value = series['change'];
    if (value == null) return 'ข้อมูลไม่เพียงพอสำหรับเปรียบเทียบ';
    final number = (value as num).toDouble();
    if (number == 0) return 'ค่าแรกและค่าล่าสุดเท่ากัน';
    return number > 0
        ? 'ค่าล่าสุดเพิ่มขึ้น ${_number(number.abs())}'
        : 'ค่าล่าสุดลดลง ${_number(number.abs())}';
  }

  @override
  Widget build(BuildContext context) {
    final completeness = Map<String, dynamic>.from(
      analysis['data_completeness'] ?? {},
    );
    final severity = Map<String, dynamic>.from(analysis['severity'] ?? {});

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(9),
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.analytics_outlined,
                  color: AppColors.primary,
                  size: 21,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('สรุปเชิงสถิติ', style: AppTextStyles.body1Bold),
                    Text(
                      'คำนวณจากข้อมูลที่บันทึกในช่วงเวลาที่เลือก',
                      style: AppTextStyles.body3.copyWith(
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          _AnalysisRow(
            label: 'ความครบถ้วนของบันทึก',
            value: _number(completeness['coverage_percent'], suffix: '%'),
            detail:
                '${completeness['recorded_days'] ?? 0} จาก '
                '${completeness['period_days'] ?? 0} วัน',
          ),
          const Divider(height: 22),
          _AnalysisRow(
            label: 'ความรุนแรงเฉลี่ย',
            value: _number(severity['average']),
            detail: _change(severity),
          ),
          const SizedBox(height: 14),
          Text(
            'ข้อมูลนี้เป็นการสรุปทางสถิติ ไม่ใช่การวินิจฉัยทางการแพทย์',
            style: AppTextStyles.body3.copyWith(color: AppColors.textSecondary),
          ),
        ],
      ),
    );
  }
}

class _AiTrendSummaryCard extends StatelessWidget {
  final Map<String, dynamic> data;

  const _AiTrendSummaryCard({required this.data});

  @override
  Widget build(BuildContext context) {
    final observations = List<dynamic>.from(data['observations'] ?? const []);
    final selfCare = List<dynamic>.from(data['self_care'] ?? const []);
    final warningSigns = List<dynamic>.from(data['warning_signs'] ?? const []);
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.auto_awesome_outlined, color: AppColors.primary),
              const SizedBox(width: 10),
              Expanded(
                child: Text('AI สรุปแนวโน้ม', style: AppTextStyles.body1Bold),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(data['summary']?.toString() ?? '', style: AppTextStyles.body2),
          ...observations.map(
            (item) => Padding(
              padding: const EdgeInsets.only(top: 6),
              child: Text('• $item', style: AppTextStyles.body2),
            ),
          ),
          if (selfCare.isNotEmpty) ...[
            const SizedBox(height: 14),
            Text('คำแนะนำดูแลตัวเอง', style: AppTextStyles.body2Bold),
            ...selfCare.map(
              (item) => Padding(
                padding: const EdgeInsets.only(top: 6),
                child: Text('• $item', style: AppTextStyles.body2),
              ),
            ),
          ],
          if (warningSigns.isNotEmpty) ...[
            const SizedBox(height: 14),
            Text('สิ่งที่ควรสังเกต', style: AppTextStyles.body2Bold),
            ...warningSigns.map(
              (item) => Padding(
                padding: const EdgeInsets.only(top: 6),
                child: Text('• $item', style: AppTextStyles.body2),
              ),
            ),
          ],
          const SizedBox(height: 10),
          Text(
            data['disclaimer']?.toString() ?? '',
            style: AppTextStyles.body3.copyWith(color: AppColors.textSecondary),
          ),
        ],
      ),
    );
  }
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
                color: AppColors.textSecondary,
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

class _AnalysisRow extends StatelessWidget {
  final String label;
  final String value;
  final String detail;

  const _AnalysisRow({
    required this.label,
    required this.value,
    required this.detail,
  });

  @override
  Widget build(BuildContext context) => LayoutBuilder(
    builder: (context, constraints) {
      final textScale = MediaQuery.textScalerOf(context).scale(14) / 14;
      final stackValues = constraints.maxWidth < 310 || textScale > 1.25;
      final labelBlock = Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: AppTextStyles.body2Bold),
          const SizedBox(height: 2),
          Text(
            detail,
            style: AppTextStyles.body3.copyWith(color: AppColors.textSecondary),
          ),
        ],
      );
      final valueText = Text(
        value,
        textAlign: stackValues ? TextAlign.left : TextAlign.right,
        style: AppTextStyles.body1Bold.copyWith(color: AppColors.primary),
      );

      if (stackValues) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [labelBlock, const SizedBox(height: 8), valueText],
        );
      }
      return Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Expanded(child: labelBlock),
          const SizedBox(width: 12),
          valueText,
        ],
      );
    },
  );
}

class _SeverityBars extends StatelessWidget {
  final List<dynamic> trend;

  const _SeverityBars({required this.trend});

  @override
  Widget build(BuildContext context) => SizedBox(
    height: 116,
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: trend.map<Widget>((item) {
        final value = ((item['severity'] as num?)?.toDouble() ?? 1).clamp(
          1,
          10,
        );
        return Expanded(
          child: Semantics(
            label: 'ระดับความรุนแรง ${value.round()} จาก 10',
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 3),
              child: Container(
                height: 18 + value * 8,
                decoration: BoxDecoration(
                  color: value >= 7 ? AppColors.warning : AppColors.primary,
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
          ),
        );
      }).toList(),
    ),
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
        Icon(icon, size: 36, color: AppColors.textHint),
        const SizedBox(height: 10),
        Text(
          text,
          textAlign: TextAlign.center,
          style: AppTextStyles.body2.copyWith(color: AppColors.textSecondary),
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
            Text(
              'บันทึก $recordedDays จาก $days วัน • Check-in ${items.length} ครั้ง',
              style: AppTextStyles.body2Bold,
            ),
            const SizedBox(height: 14),
            LinearProgressIndicator(
              value: recordedDays / days,
              minHeight: 10,
              borderRadius: BorderRadius.circular(8),
            ),
            const SizedBox(height: 16),
            Wrap(
              spacing: 20,
              runSpacing: 8,
              children: [
                _StatusLegend(
                  color: AppColors.success,
                  label: 'ดี $well ครั้ง',
                ),
                _StatusLegend(
                  color: AppColors.primary,
                  label: 'ปกติ $normal ครั้ง',
                ),
                _StatusLegend(
                  color: AppColors.danger,
                  label: 'ไม่ค่อยดี $unwell ครั้ง',
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
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
