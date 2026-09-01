import 'package:flutter/material.dart';
import 'package:mobile/data/services/central_http_client.dart' as http;
import 'package:intl/intl.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/pdf_file_saver.dart';
import '../../../core/utils/thai_date_formatter.dart';

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
  bool _dailyRecords = true;
  bool _loading = false;

  Future<void> _selectRange() async {
    final selected = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDateRange: _range,
    );
    if (selected != null) setState(() => _range = selected);
  }

  Future<void> _download() async {
    if (!_assessments && !_dailyRecords) {
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
              'include_follow_ups': '0',
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
      await savePdfFile(filename, response.bodyBytes);
      if (mounted) _message('บันทึกรายงาน PDF เรียบร้อยแล้ว: $filename');
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('รายงานประวัติสุขภาพ', style: AppTextStyles.h4),
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, thickness: 1, color: AppColors.border),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(
            'เลือกช่วงเวลาและข้อมูลที่ต้องการรวมในรายงาน PDF',
            style: AppTextStyles.body1,
          ),
          const SizedBox(height: 20),
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: const Icon(Icons.date_range_rounded),
            title: const Text('ช่วงวันที่'),
            subtitle: Text(
              '${formatThaiDate(_range.start)} - ${formatThaiDate(_range.end)}',
            ),
            trailing: const Icon(Icons.chevron_right_rounded),
            onTap: _selectRange,
          ),
          const Divider(height: 32),
          const Text(
            'ข้อมูลในรายงาน',
            style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold),
          ),
          CheckboxListTile(
            contentPadding: EdgeInsets.zero,
            value: _assessments,
            onChanged: (value) => setState(() => _assessments = value ?? false),
            title: const Text('ประวัติการประเมินอาการ'),
          ),
          CheckboxListTile(
            contentPadding: EdgeInsets.zero,
            value: _dailyRecords,
            onChanged: (value) =>
                setState(() => _dailyRecords = value ?? false),
            title: const Text('บันทึกสุขภาพรายวัน'),
          ),
          const SizedBox(height: 16),
          const Card(
            child: Padding(
              padding: EdgeInsets.all(14),
              child: Text(
                'รายงานนี้เป็นข้อมูลที่บันทึกในระบบ ไม่ใช่เอกสารวินิจฉัยหรือคำแนะนำแทนบุคลากรทางการแพทย์',
              ),
            ),
          ),
          const SizedBox(height: 24),
          FilledButton.icon(
            onPressed: _loading ? null : _download,
            icon: _loading
                ? const SizedBox.square(
                    dimension: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.picture_as_pdf_rounded),
            label: const Text('สร้างรายงาน PDF'),
          ),
        ],
      ),
    );
  }
}
