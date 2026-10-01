import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:pdfrx/pdfrx.dart';
import 'package:share_plus/share_plus.dart';

import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/pdf_file_saver.dart';

class HealthReportPreviewScreen extends StatefulWidget {
  final Uint8List bytes;
  final String filename;

  const HealthReportPreviewScreen({
    super.key,
    required this.bytes,
    required this.filename,
  });

  @override
  State<HealthReportPreviewScreen> createState() =>
      _HealthReportPreviewScreenState();
}

class _HealthReportPreviewScreenState extends State<HealthReportPreviewScreen> {
  bool _saving = false;
  bool _sharing = false;

  Future<void> _save() async {
    if (_saving) return;
    setState(() => _saving = true);
    try {
      final savedPath = await savePdfFile(widget.filename, widget.bytes);
      if (!mounted) return;
      final message = savedPath == null
          ? 'ดาวน์โหลดไฟล์ PDF เรียบร้อยแล้ว'
          : 'บันทึกไฟล์ PDF แล้วที่ $savedPath';
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('ไม่สามารถบันทึกไฟล์ PDF ได้')),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _share() async {
    if (_sharing) return;
    setState(() => _sharing = true);
    try {
      await SharePlus.instance.share(
        ShareParams(
          files: [
            XFile.fromData(
              widget.bytes,
              mimeType: 'application/pdf',
              name: widget.filename,
            ),
          ],
          text: 'รายงานสุขภาพของฉัน',
          fileNameOverrides: [widget.filename],
        ),
      );
    } finally {
      if (mounted) setState(() => _sharing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        scrolledUnderElevation: 0,
        surfaceTintColor: Colors.transparent,
        centerTitle: true,
        title: Text('ตัวอย่างรายงานสุขภาพ', style: AppTextStyles.h4),
        actions: [
          IconButton(
            tooltip: 'บันทึกไฟล์ PDF',
            onPressed: _saving ? null : _save,
            icon: _saving
                ? const SizedBox.square(
                    dimension: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.download_rounded),
          ),
          IconButton(
            tooltip: 'แชร์ไฟล์ PDF',
            onPressed: _sharing ? null : _share,
            icon: _sharing
                ? const SizedBox.square(
                    dimension: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.ios_share_rounded),
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(0.5),
          child: Divider(
            height: 0.5,
            thickness: 0.5,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: PdfViewer.data(
        widget.bytes,
        sourceName: widget.filename,
        params: const PdfViewerParams(
          margin: 12,
          backgroundColor: Color(0xFFEFF1F8),
        ),
      ),
    );
  }
}
