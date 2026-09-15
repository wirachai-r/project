import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:checkup/data/services/central_http_client.dart' as http;

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../shared/widgets/app_layout.dart';

class FeedbackScreen extends StatefulWidget {
  const FeedbackScreen({super.key, required this.token});

  final String token;

  @override
  State<FeedbackScreen> createState() => _FeedbackScreenState();
}

class _FeedbackScreenState extends State<FeedbackScreen> {
  static const _categories = {
    'suggestion': 'ข้อเสนอแนะ',
    'bug': 'ปัญหาการใช้งาน',
    'content_error': 'ข้อมูลไม่ถูกต้อง',
    'other': 'อื่น ๆ',
  };
  static const _categoryLabels = {
    ..._categories,
    'inaccurate': 'ข้อมูลไม่ถูกต้อง',
    'outdated': 'ข้อมูลล้าสมัย',
    'unclear': 'ข้อมูลไม่ชัดเจน',
    'unsafe': 'ข้อมูลอาจไม่ปลอดภัย',
  };
  static const _feedbackTypeLabels = {
    'all': 'ทุกประเภท',
    'general': 'ความคิดเห็นทั่วไป',
    'content_error': 'รายงานข้อมูลผิด',
    'assessment': 'รายงานผลประเมิน',
  };
  static const _statusFilterLabels = {
    'all': 'ทุกสถานะ',
    'pending': 'รอตรวจสอบ',
    'in_review': 'กำลังตรวจสอบ',
    'resolved': 'ดำเนินการแล้ว',
    'dismissed': 'ปิดรายงาน',
  };

  final _formKey = GlobalKey<FormState>();
  final _messageController = TextEditingController();
  final _imagePicker = ImagePicker();
  final List<XFile> _attachments = [];
  String _selectedCategory = 'suggestion';
  String _historyType = 'all';
  String _historyStatus = 'all';
  bool _submitting = false;
  bool _loading = true;
  List<Map<String, dynamic>> _items = [];

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.feedback}'),
        headers: _headers,
      );
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body)['data'] as List<dynamic>;
        if (mounted) {
          setState(() => _items = data.cast<Map<String, dynamic>>());
        }
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.feedback}'),
      )..headers.addAll({
          'Accept': 'application/json',
          'Authorization': 'Bearer ${widget.token}',
        });
      request.fields.addAll({
        'feedback_type': 'general',
        'category': _selectedCategory,
        'message': _messageController.text.trim(),
      });
      for (final image in _attachments) {
        request.files.add(http.MultipartFile.fromBytes(
          'attachments[]',
          await image.readAsBytes(),
          filename: image.name,
        ));
      }
      final response = await http.Response.fromStream(await http.send(request));
      if (!mounted) return;
      if (response.statusCode == 201) {
        _messageController.clear();
        setState(() => _attachments.clear());
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('ส่งความคิดเห็นเรียบร้อยแล้ว ขอบคุณครับ'),
          ),
        );
        await _load();
      } else {
        throw Exception();
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('ส่งความคิดเห็นไม่สำเร็จ กรุณาลองใหม่')),
        );
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  Future<void> _pickAttachments() async {
    final images = await _imagePicker.pickMultiImage(
      imageQuality: 80,
      maxWidth: 1600,
    );
    if (!mounted || images.isEmpty) return;
    final remaining = 3 - _attachments.length;
    setState(() => _attachments.addAll(images.take(remaining)));
    if (images.length > remaining) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('แนบรูปได้สูงสุด 3 รูป')),
      );
    }
  }

  void _showDetails(Map<String, dynamic> item) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (context) => _FeedbackDetailsScreen(
          item: item,
          token: widget.token,
          category:
              _categoryLabels[item['category']?.toString()] ?? 'ไม่ระบุหัวข้อ',
          status: _statusLabel(item['status']?.toString()),
          statusColor: _statusColor(item['status']?.toString()),
          submittedDate: _submittedDate(item['created_at']),
        ),
      ),
    );
  }

  String _statusLabel(String? status) => switch (status) {
    'in_review' => 'กำลังตรวจสอบ',
    'resolved' => 'ดำเนินการแล้ว',
    'dismissed' => 'ปิดรายงาน',
    _ => 'รอตรวจสอบ',
  };

  Color _statusColor(String? status) => switch (status) {
    'resolved' => AppColors.success,
    'dismissed' => Theme.of(context).colorScheme.onSurfaceVariant,
    'in_review' => AppColors.primary,
    _ => AppColors.warning,
  };

  String _submittedDate(dynamic value) {
    final date = DateTime.tryParse(value?.toString() ?? '')?.toLocal();
    return date == null ? '' : formatThaiDate(date);
  }

  List<Map<String, dynamic>> get _filteredItems => _items.where((item) {
    final matchesType =
        _historyType == 'all' || item['feedback_type'] == _historyType;
    final matchesStatus =
        _historyStatus == 'all' || item['status'] == _historyStatus;
    return matchesType && matchesStatus;
  }).toList();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('ข้อเสนอแนะ', style: AppTextStyles.h4),
        bottom: PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(
            height: 1,
            thickness: 1,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: AppContentWidth(
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 32),
            children: [
              Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: AppColors.primaryLight.withValues(alpha: 0.55),
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.surface,
                        borderRadius: BorderRadius.circular(13),
                      ),
                      child: const Icon(
                        Icons.forum_outlined,
                        color: AppColors.primary,
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'ช่วยให้เราปรับปรุงแอป',
                            style: AppTextStyles.h4,
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'แจ้งสิ่งที่พบหรือเสนอสิ่งที่อยากให้ปรับปรุง ทีมงานจะนำไปตรวจสอบ',
                            style: AppTextStyles.body2.copyWith(
                              color: Theme.of(
                                context,
                              ).colorScheme.onSurfaceVariant,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.surface,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(
                    color: Theme.of(context).colorScheme.outlineVariant,
                  ),
                ),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('ส่งความคิดเห็น', style: AppTextStyles.body1Bold),
                      const SizedBox(height: 12),
                      DropdownButtonFormField<String>(
                        initialValue: _selectedCategory,
                        decoration: const InputDecoration(
                          labelText: 'หัวข้อ',
                          prefixIcon: Icon(Icons.topic_outlined),
                        ),
                        items: _categories.entries
                            .map(
                              (item) => DropdownMenuItem(
                                value: item.key,
                                child: Text(item.value),
                              ),
                            )
                            .toList(),
                        onChanged: _submitting
                            ? null
                            : (value) {
                                if (value != null) {
                                  setState(() => _selectedCategory = value);
                                }
                              },
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _messageController,
                        minLines: 4,
                        maxLines: 8,
                        decoration: const InputDecoration(
                          hintText: 'บอกสิ่งที่พบหรือสิ่งที่อยากให้ปรับปรุง',
                          alignLabelWithHint: true,
                        ),
                        validator: (value) => value?.trim().isEmpty ?? true
                            ? 'กรุณากรอกรายละเอียด'
                            : null,
                      ),
                      const SizedBox(height: 12),
                      OutlinedButton.icon(
                        onPressed: _submitting || _attachments.length >= 3
                            ? null
                            : _pickAttachments,
                        icon: const Icon(Icons.add_photo_alternate_outlined),
                        label: Text('แนบรูป (${_attachments.length}/3)'),
                      ),
                      if (_attachments.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: _attachments.asMap().entries.map((entry) {
                            return Stack(
                              clipBehavior: Clip.none,
                              children: [
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(12),
                                  child: FutureBuilder(
                                    future: entry.value.readAsBytes(),
                                    builder: (context, snapshot) => snapshot.hasData
                                        ? Image.memory(snapshot.data!, width: 88, height: 88, fit: BoxFit.cover)
                                        : const SizedBox(width: 88, height: 88, child: Center(child: CircularProgressIndicator(strokeWidth: 2))),
                                  ),
                                ),
                                Positioned(
                                  right: -8,
                                  top: -8,
                                  child: IconButton.filled(
                                    onPressed: _submitting ? null : () => setState(() => _attachments.removeAt(entry.key)),
                                    icon: const Icon(Icons.close, size: 16),
                                    constraints: const BoxConstraints.tightFor(width: 28, height: 28),
                                    padding: EdgeInsets.zero,
                                  ),
                                ),
                              ],
                            );
                          }).toList(),
                        ),
                      ],
                      const SizedBox(height: 8),
                      Text(
                        'ไม่บังคับ • สูงสุด 3 รูป รูปละไม่เกิน 5 MB กรุณาปิดบังข้อมูลส่วนตัวหรือข้อมูลสุขภาพที่ไม่ต้องการเปิดเผย',
                        style: AppTextStyles.body3.copyWith(
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                      ),
                      const SizedBox(height: 12),
                      FilledButton.icon(
                        onPressed: _submitting ? null : _submit,
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(50),
                        ),
                        icon: _submitting
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: AppColors.white,
                                ),
                              )
                            : const Icon(Icons.send_rounded, size: 19),
                        label: Text(
                          _submitting ? 'กำลังส่ง...' : 'ส่งความคิดเห็น',
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 10),
              Row(
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
                      'ช่องทางนี้ไม่เหมาะสำหรับเหตุฉุกเฉินทางการแพทย์',
                      style: AppTextStyles.body3.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 30),
              Text('ประวัติที่ส่ง', style: AppTextStyles.h4),
              const SizedBox(height: 12),
              LayoutBuilder(
                builder: (context, constraints) {
                  final typeFilter = DropdownButtonFormField<String>(
                    initialValue: _historyType,
                    decoration: const InputDecoration(labelText: 'ประเภท'),
                    items: _feedbackTypeLabels.entries
                        .map(
                          (item) => DropdownMenuItem(
                            value: item.key,
                            child: Text(item.value),
                          ),
                        )
                        .toList(),
                    onChanged: (value) {
                      if (value != null) {
                        setState(() => _historyType = value);
                      }
                    },
                  );
                  final statusFilter = DropdownButtonFormField<String>(
                    initialValue: _historyStatus,
                    decoration: const InputDecoration(labelText: 'สถานะ'),
                    items: _statusFilterLabels.entries
                        .map(
                          (item) => DropdownMenuItem(
                            value: item.key,
                            child: Text(item.value),
                          ),
                        )
                        .toList(),
                    onChanged: (value) {
                      if (value != null) {
                        setState(() => _historyStatus = value);
                      }
                    },
                  );
                  if (constraints.maxWidth < 360) {
                    return Column(
                      children: [
                        typeFilter,
                        const SizedBox(height: 10),
                        statusFilter,
                      ],
                    );
                  }
                  return Row(
                    children: [
                      Expanded(child: typeFilter),
                      const SizedBox(width: 10),
                      Expanded(child: statusFilter),
                    ],
                  );
                },
              ),
              const SizedBox(height: 12),
              if (_loading)
                const AppLoadingView()
              else if (_items.isEmpty)
                const _EmptyHistory()
              else if (_filteredItems.isEmpty)
                const _EmptyHistory(message: 'ไม่พบประวัติที่ตรงกับตัวกรอง')
              else
                ..._filteredItems.map(
                  (item) => _FeedbackHistoryCard(
                    category:
                        _categoryLabels[item['category']?.toString()] ??
                        'ไม่ระบุหัวข้อ (รายการเดิม)',
                    message: item['message']?.toString() ?? '-',
                    status: _statusLabel(item['status']?.toString()),
                    statusColor: _statusColor(item['status']?.toString()),
                    submittedDate: _submittedDate(item['created_at']),
                    onTap: () => _showDetails(item),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _FeedbackHistoryCard extends StatelessWidget {
  final String category;
  final String message;
  final String status;
  final Color statusColor;
  final String submittedDate;
  final VoidCallback onTap;

  const _FeedbackHistoryCard({
    required this.category,
    required this.message,
    required this.status,
    required this.statusColor,
    required this.submittedDate,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) => InkWell(
    onTap: onTap,
    borderRadius: BorderRadius.circular(18),
    splashFactory: NoSplash.splashFactory,
    overlayColor: const WidgetStatePropertyAll(Colors.transparent),
    child: Container(
    margin: const EdgeInsets.only(bottom: 10),
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(category, style: AppTextStyles.body2Bold),
        const SizedBox(height: 6),
        Text(message, style: AppTextStyles.body1, maxLines: 4),
        const SizedBox(height: 12),
        Row(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: statusColor.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 7,
                    height: 7,
                    decoration: BoxDecoration(
                      color: statusColor,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    status,
                    style: AppTextStyles.body3Bold.copyWith(color: statusColor),
                  ),
                ],
              ),
            ),
            const Spacer(),
            if (submittedDate.isNotEmpty)
              Text(
                submittedDate,
                style: AppTextStyles.body3.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
          ],
        ),
      ],
    ),
    ),
  );
}

class _FeedbackDetailsScreen extends StatelessWidget {
  const _FeedbackDetailsScreen({
    required this.item,
    required this.token,
    required this.category,
    required this.status,
    required this.statusColor,
    required this.submittedDate,
  });

  final Map<String, dynamic> item;
  final String token;
  final String category;
  final String status;
  final Color statusColor;
  final String submittedDate;

  @override
  Widget build(BuildContext context) {
    final attachments = List<dynamic>.from(item['attachments'] ?? const []);
    final adminNote = item['admin_note']?.toString().trim();
    return Scaffold(
      appBar: AppBar(
        title: Text('รายละเอียดที่ส่ง', style: AppTextStyles.h4),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Divider(
            height: 1,
            thickness: 1,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: AppContentWidth(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 20, 16, 32),
          children: [
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
            ),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(category, style: AppTextStyles.body2Bold),
              const SizedBox(height: 8),
              Text(item['message']?.toString() ?? '-', style: AppTextStyles.body1),
            ]),
          ),
          if (attachments.isNotEmpty) ...[
            const SizedBox(height: 20),
            Text('รูปที่แนบ', style: AppTextStyles.body2Bold),
            const SizedBox(height: 10),
            ...List.generate(attachments.length, (index) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Image.network(
                  '${ApiConstants.baseUrl}${ApiConstants.feedback}/${item['id']}/attachments/$index',
                  headers: {'Authorization': 'Bearer $token'},
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => const SizedBox(height: 80, child: Center(child: Icon(Icons.broken_image_outlined))),
                ),
              ),
            )),
          ],
          const SizedBox(height: 16),
          Text('สถานะ: $status', style: AppTextStyles.body2Bold.copyWith(color: statusColor)),
          if (submittedDate.isNotEmpty) Text('ส่งเมื่อ $submittedDate', style: AppTextStyles.body3),
          if (adminNote != null && adminNote.isNotEmpty) ...[
            const SizedBox(height: 20),
            Text('ข้อความจากผู้ดูแล', style: AppTextStyles.body2Bold),
            const SizedBox(height: 6),
            Text(adminNote, style: AppTextStyles.body1),
          ],
          ],
        ),
      ),
    );
  }
}

class _EmptyHistory extends StatelessWidget {
  final String message;

  const _EmptyHistory({this.message = 'ยังไม่มีความคิดเห็นที่ส่ง'});

  @override
  Widget build(BuildContext context) => Container(
    width: double.infinity,
    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 30),
    decoration: BoxDecoration(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
    ),
    child: Column(
      children: [
        Icon(
          Icons.mark_chat_unread_outlined,
          size: 38,
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
        const SizedBox(height: 10),
        Text(
          message,
          style: AppTextStyles.body2.copyWith(
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
      ],
    ),
  );
}
