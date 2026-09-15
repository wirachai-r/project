import 'package:flutter/material.dart';
import 'package:checkup/data/services/central_http_client.dart' as http;
import 'package:intl/intl.dart';
import 'package:share_plus/share_plus.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/buddhist_calendar_delegate.dart';
import '../../../core/utils/pdf_file_saver.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_layout.dart';

class HealthReportScreen extends StatefulWidget {
  final String token;

  const HealthReportScreen({super.key, required this.token});

  @override
  State<HealthReportScreen> createState() => _HealthReportScreenState();
}

class _HealthReportScreenState extends State<HealthReportScreen> {
  DateTimeRange _range = DateTimeRange(
    start: DateTime.now().subtract(const Duration(days: 29)),
    end: DateTime.now(),
  );
  bool _assessments = true;
  bool _followUps = true;
  bool _dailyRecords = true;
  bool _loading = false;

  Future<void> _selectRange() async {
    final selected = await showDateRangePicker(
      context: context,
      calendarDelegate: const BuddhistCalendarDelegate(),
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDateRange: _range,
    );
    if (selected != null) setState(() => _range = selected);
  }

  Future<void> _download() async {
    if (!_assessments && !_followUps && !_dailyRecords) {
      _message('กรุณาเลือกข้อมูลอย่างน้อยหนึ่งประเภท');
      return;
    }
    setState(() => _loading = true);
    try {
      final formatter = DateFormat('yyyy-MM-dd');
      final uri =
          Uri.parse(
            '${ApiConstants.baseUrl}${ApiConstants.healthReport}',
          ).replace(
            queryParameters: {
              'from': formatter.format(_range.start),
              'to': formatter.format(_range.end),
              'include_assessments': _assessments ? '1' : '0',
              'include_follow_ups': _followUps ? '1' : '0',
              'include_daily_records': _dailyRecords ? '1' : '0',
            },
          );
      final response = await http.get(
        uri,
        headers: {
          'Accept': 'application/pdf',
          'Authorization': 'Bearer ${widget.token}',
        },
      );
      if (response.statusCode != 200) {
        if (mounted) {
          _message('ไม่สามารถสร้างรายงานได้ (${response.statusCode})');
        }
        return;
      }

      final filename =
          'health-report-${formatter.format(_range.start)}-${formatter.format(_range.end)}.pdf';
      final savedPath = await savePdfFile(filename, response.bodyBytes);
      if (mounted) await _showDownloadSuccess(filename, savedPath);
    } catch (error) {
      debugPrint('Health report download failed: $error');
      if (mounted) _message('ไม่สามารถสร้างรายงานได้ กรุณาลองใหม่');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _message(String text) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(text)));
  }

  Future<void> _showDownloadSuccess(String filename, String? savedPath) async {
    await showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (sheetContext) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(24, 8, 24, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 58,
                height: 58,
                decoration: const BoxDecoration(
                  color: AppColors.primaryLight,
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.check_rounded,
                  color: AppColors.primary,
                  size: 32,
                ),
              ),
              const SizedBox(height: 14),
              Text('สร้างรายงานเรียบร้อยแล้ว', style: AppTextStyles.h3),
              const SizedBox(height: 8),
              Text(
                savedPath == null
                    ? 'ดาวน์โหลด $filename ผ่านเบราว์เซอร์แล้ว กรุณาตรวจสอบโฟลเดอร์ดาวน์โหลด'
                    : 'บันทึกไฟล์ไว้ที่\n$savedPath',
                textAlign: TextAlign.center,
                style: AppTextStyles.body2.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
              if (savedPath != null) ...[
                const SizedBox(height: 18),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: () async {
                      await SharePlus.instance.share(
                        ShareParams(
                          files: [XFile(savedPath)],
                          text: 'รายงานสุขภาพของฉัน',
                        ),
                      );
                    },
                    icon: const Icon(Icons.ios_share_rounded),
                    label: const Text('เปิดหรือแชร์ไฟล์ PDF'),
                  ),
                ),
              ],
              const SizedBox(height: 6),
              TextButton(
                onPressed: () => Navigator.pop(sheetContext),
                child: const Text('ปิด'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('สร้างรายงานสุขภาพ', style: AppTextStyles.h4),
        bottom: PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(
            height: 1,
            thickness: 1,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: ResponsiveBuilder(
        builder: (context) => AppContentWidth(
          child: ListView(
            padding: EdgeInsets.fromLTRB(
              Responsive.horizontalPadding,
              20,
              Responsive.horizontalPadding,
              32,
            ),
            children: [
              const AppHeroIntro(
                icon: Icons.picture_as_pdf_rounded,
                title: 'สร้างรายงานสุขภาพ',
                description: 'เลือกช่วงเวลาและข้อมูลที่ต้องการรวมไว้ในไฟล์ PDF',
              ),
              const SizedBox(height: 24),
              AppPanel(
                padding: EdgeInsets.zero,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    ListTile(
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 18,
                        vertical: 8,
                      ),
                      leading: Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: AppColors.primaryLight,
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: const Icon(
                          Icons.date_range_rounded,
                          color: AppColors.primary,
                        ),
                      ),
                      title: const Text('ช่วงวันที่'),
                      subtitle: Text(
                        '${formatThaiDate(_range.start)} - ${formatThaiDate(_range.end)}',
                      ),
                      trailing: const Icon(Icons.chevron_right_rounded),
                      onTap: _selectRange,
                    ),
                    const Divider(),
                    Padding(
                      padding: const EdgeInsets.fromLTRB(18, 16, 18, 6),
                      child: Text('ข้อมูลในรายงาน', style: AppTextStyles.h4),
                    ),
                    CheckboxListTile(
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 18,
                      ),
                      value: _assessments,
                      onChanged: (value) =>
                          setState(() => _assessments = value ?? false),
                      title: const Text('ประวัติการประเมินอาการ'),
                      subtitle: const Text(
                        'ผลคัดกรองและข้อมูลที่อาจเกี่ยวข้อง',
                      ),
                      secondary: const Icon(Icons.fact_check_outlined),
                    ),
                    const Divider(indent: 64),
                    CheckboxListTile(
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 18,
                      ),
                      value: _followUps,
                      onChanged: (value) =>
                          setState(() => _followUps = value ?? false),
                      title: const Text('การติดตามอาการ'),
                      subtitle: const Text(
                        'อาการ ระดับความรุนแรง และบันทึกในแต่ละวัน',
                      ),
                      secondary: const Icon(Icons.monitor_heart_outlined),
                    ),
                    const Divider(indent: 64),
                    CheckboxListTile(
                      contentPadding: const EdgeInsets.fromLTRB(18, 0, 18, 10),
                      value: _dailyRecords,
                      onChanged: (value) =>
                          setState(() => _dailyRecords = value ?? false),
                      title: const Text('บันทึกสุขภาพรายวัน'),
                      subtitle: const Text(
                        'สถานะสุขภาพและรายละเอียดที่บันทึกประจำวัน',
                      ),
                      secondary: const Icon(Icons.favorite_outline_rounded),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              const AppInfoBanner(
                icon: Icons.health_and_safety_outlined,
                message:
                    'รายงานนี้เป็นข้อมูลที่บันทึกในระบบ ไม่ใช่เอกสารวินิจฉัยหรือคำแนะนำแทนบุคลากรทางการแพทย์',
              ),
              const SizedBox(height: 24),
              AppButton(
                label: 'สร้างรายงาน PDF',
                icon: const Icon(Icons.download_rounded),
                loading: _loading,
                onTap: _loading ? null : _download,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
