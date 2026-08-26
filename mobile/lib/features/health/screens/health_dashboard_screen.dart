import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../data/repositories/personal_health_repository.dart';
import '../../../shared/widgets/app_feedback.dart';

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

  String _dateParam(DateTime value) =>
      '${value.year.toString().padLeft(4, '0')}-'
      '${value.month.toString().padLeft(2, '0')}-'
      '${value.day.toString().padLeft(2, '0')}';

  int get _activeDays => _customRange == null
      ? _days
      : _customRange!.duration.inDays + 1;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final generation = ++_loadGeneration;
    if (_data != null && mounted) setState(() => _refreshing = true);
    try {
      final result = await context.read<PersonalHealthRepository>().dashboard(
        days: _days,
        from: _customRange == null ? null : _dateParam(_customRange!.start),
        to: _customRange == null ? null : _dateParam(_customRange!.end),
      );
      if (!mounted || generation != _loadGeneration) return;
      setState(() {
        _data = result;
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
    final temperatures = List<dynamic>.from(_data!['temperature_trend'] ?? []);
    final dailyStatuses = List<dynamic>.from(
      _data!['daily_status_trend'] ?? [],
    );
    final analysis = Map<String, dynamic>.from(
      _data!['statistical_analysis'] ?? {},
    );

    return LayoutBuilder(
      builder: (context, constraints) => ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(
          constraints.maxWidth >= 600
              ? (constraints.maxWidth - 720) / 2 + 32
              : 20,
          8,
          constraints.maxWidth >= 600
              ? (constraints.maxWidth - 720) / 2 + 32
              : 20,
          32,
        ),
        children: [
          _buildHero(),
          const SizedBox(height: 16),
          Container(
            padding: EdgeInsets.zero,
            decoration: BoxDecoration(
              color: Colors.transparent,
              borderRadius: BorderRadius.circular(14),
            ),
            child: SegmentedButton<int>(
              expandedInsets: EdgeInsets.zero,
              showSelectedIcon: false,
              style: ButtonStyle(
                side: const WidgetStatePropertyAll(
                  BorderSide(color: AppColors.primary, width: 1.5),
                ),
                visualDensity: const VisualDensity(
                  horizontal: -2,
                  vertical: -2,
                ),
                backgroundColor: WidgetStateProperty.resolveWith(
                  (states) => states.contains(WidgetState.selected)
                      ? AppColors.primary
                      : AppColors.surfaceElevated,
                ),
                foregroundColor: WidgetStateProperty.resolveWith(
                  (states) => states.contains(WidgetState.selected)
                      ? AppColors.white
                      : AppColors.textPrimary,
                ),
                padding: const WidgetStatePropertyAll(
                  EdgeInsets.symmetric(horizontal: 6),
                ),
                minimumSize: const WidgetStatePropertyAll(Size(0, 44)),
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                shape: WidgetStatePropertyAll(
                  RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
              ),
              segments: const [
                ButtonSegment(value: 7, label: Text('7 วัน')),
                ButtonSegment(value: 30, label: Text('30 วัน')),
                ButtonSegment(value: 90, label: Text('90 วัน')),
                ButtonSegment(
                  value: 0,
                  icon: Icon(Icons.date_range_outlined, size: 17),
                  label: Text('กำหนด'),
                ),
              ],
              selected: {_days},
              onSelectionChanged: (selection) {
                if (selection.first == 0) {
                  _pickCustomRange();
                } else {
                  setState(() {
                    _days = selection.first;
                    _customRange = null;
                  });
                  _load();
                }
              },
            ),
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
          const SizedBox(height: 16),
          _StatisticalAnalysisCard(analysis: analysis),
          const SizedBox(height: 16),
          LayoutBuilder(
            builder: (context, constraints) {
              final width = (constraints.maxWidth - 12) / 2;
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
                    width: constraints.maxWidth,
                    label: 'ควรเฝ้าระวัง',
                    value: summary['urgent_count'] ?? 0,
                    icon: Icons.health_and_safety_outlined,
                    accent: AppColors.warning,
                  ),
                ],
              );
            },
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
            Card(
              child: Column(
                children: [
                  for (var index = 0; index < symptoms.length; index++) ...[
                    ListTile(
                      minTileHeight: 64,
                      leading: const CircleAvatar(
                        backgroundColor: AppColors.primaryLight,
                        child: Icon(
                          Icons.monitor_heart_outlined,
                          color: AppColors.primary,
                        ),
                      ),
                      title: Text(
                        symptoms[index]['symptom_name'] ?? 'ไม่ระบุอาการ',
                        style: AppTextStyles.body2Bold,
                      ),
                      trailing: Text(
                        '${symptoms[index]['count'] ?? 0} ครั้ง',
                        style: AppTextStyles.body2.copyWith(
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ),
                    if (index < symptoms.length - 1) const Divider(indent: 72),
                  ],
                ],
              ),
            ),
          const SizedBox(height: 28),
          const AppSectionHeader(title: 'แนวโน้มความรุนแรงล่าสุด'),
          const SizedBox(height: 12),
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
          const AppSectionHeader(title: 'แนวโน้มอุณหภูมิ'),
          const SizedBox(height: 12),
          _TrendCard(
            points: temperatures
                .map(
                  (item) => _TrendPoint(
                    value: (item['temperature'] as num?)?.toDouble() ?? 0,
                    label: '${item['temperature']} °C',
                  ),
                )
                .toList(),
            emptyText: 'ยังไม่มีข้อมูลอุณหภูมิในช่วงเวลานี้',
            color: AppColors.warning,
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
    padding: const EdgeInsets.all(22),
    decoration: BoxDecoration(
      color: AppColors.primary,
      borderRadius: BorderRadius.circular(20),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
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
        const SizedBox(height: 16),
        Text(
          'ภาพรวมสุขภาพของคุณ',
          style: AppTextStyles.h3.copyWith(color: Colors.white),
        ),
        const SizedBox(height: 4),
        Text(
          'สรุปจากข้อมูลที่คุณบันทึกไว้ในแอป',
          style: AppTextStyles.body2.copyWith(color: Colors.white70),
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
    final temperature = Map<String, dynamic>.from(
      analysis['temperature'] ?? {},
    );

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(20),
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
          const Divider(height: 22),
          _AnalysisRow(
            label: 'อุณหภูมิเฉลี่ย',
            value: _number(temperature['average'], suffix: ' °C'),
            detail: _change(temperature),
          ),
          const SizedBox(height: 14),
          Text(
            'ข้อมูลนี้เป็นการสรุปทางสถิติ ไม่ใช่การวินิจฉัยทางการแพทย์',
            style: AppTextStyles.body3.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
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
  Widget build(BuildContext context) => Row(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: AppTextStyles.body2Bold),
            const SizedBox(height: 2),
            Text(
              detail,
              style: AppTextStyles.body3.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
      ),
      const SizedBox(width: 12),
      Text(
        value,
        textAlign: TextAlign.right,
        style: AppTextStyles.body1Bold.copyWith(color: AppColors.primary),
      ),
    ],
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

class _TrendPoint {
  final double value;
  final String label;

  const _TrendPoint({required this.value, required this.label});
}

class _TrendCard extends StatelessWidget {
  final List<_TrendPoint> points;
  final String emptyText;
  final Color color;

  const _TrendCard({
    required this.points,
    required this.emptyText,
    required this.color,
  });

  @override
  Widget build(BuildContext context) => Card(
    child: Padding(
      padding: const EdgeInsets.all(20),
      child: points.isEmpty
          ? _InlineEmpty(icon: Icons.thermostat_rounded, text: emptyText)
          : Semantics(
              label: points.map((point) => point.label).join(', '),
              child: SizedBox(
                height: 150,
                width: double.infinity,
                child: CustomPaint(
                  painter: _LineTrendPainter(
                    values: points.map((point) => point.value).toList(),
                    color: color,
                  ),
                ),
              ),
            ),
    ),
  );
}

class _LineTrendPainter extends CustomPainter {
  final List<double> values;
  final Color color;

  const _LineTrendPainter({required this.values, required this.color});

  @override
  void paint(Canvas canvas, Size size) {
    final gridPaint = Paint()
      ..color = AppColors.border
      ..strokeWidth = 1;
    for (var row = 0; row <= 3; row++) {
      final y = size.height * row / 3;
      canvas.drawLine(Offset(0, y), Offset(size.width, y), gridPaint);
    }

    final minValue = values.reduce((a, b) => a < b ? a : b);
    final maxValue = values.reduce((a, b) => a > b ? a : b);
    final range = maxValue - minValue;
    final path = Path();
    final pointPaint = Paint()..color = color;
    final linePaint = Paint()
      ..color = color
      ..strokeWidth = 3
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round;

    for (var index = 0; index < values.length; index++) {
      final x = values.length == 1
          ? size.width / 2
          : size.width * index / (values.length - 1);
      final normalized = range == 0 ? 0.5 : (values[index] - minValue) / range;
      final y = size.height - 12 - normalized * (size.height - 24);
      if (index == 0) {
        path.moveTo(x, y);
      } else {
        path.lineTo(x, y);
      }
      canvas.drawCircle(Offset(x, y), 4, pointPaint);
    }
    canvas.drawPath(path, linePaint);
  }

  @override
  bool shouldRepaint(covariant _LineTrendPainter oldDelegate) =>
      oldDelegate.values != values || oldDelegate.color != color;
}

class _DailyStatusSummary extends StatelessWidget {
  final List<dynamic> items;
  final int days;

  const _DailyStatusSummary({required this.items, required this.days});

  @override
  Widget build(BuildContext context) {
    final unwell = items.where((item) => item['status'] == 'unwell').length;
    final well = items.where((item) => item['status'] == 'well').length;
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
              'บันทึกแล้ว ${items.length} จาก $days วัน',
              style: AppTextStyles.body2Bold,
            ),
            const SizedBox(height: 14),
            LinearProgressIndicator(
              value: items.length / days,
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
                  label: 'สบายดี $well วัน',
                ),
                _StatusLegend(
                  color: AppColors.danger,
                  label: 'มีอาการ $unwell วัน',
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
