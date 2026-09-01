import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../data/models/health_episode_model.dart';
import '../../../data/models/symptom_model.dart';
import '../../../data/repositories/personal_health_repository.dart';
import '../../../data/repositories/symptom_repository.dart';
import '../../../shared/widgets/app_button.dart';

class FollowUpScreen extends StatefulWidget {
  final dynamic assessmentId;
  final String symptomName;

  const FollowUpScreen({
    super.key,
    required this.assessmentId,
    required this.symptomName,
  });

  @override
  State<FollowUpScreen> createState() => _FollowUpScreenState();
}

class _FollowUpScreenState extends State<FollowUpScreen> {
  HealthEpisodeModel? _episode;
  final Map<dynamic, _SymptomDraft> _drafts = {};
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    for (final draft in _drafts.values) {
      draft.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final episode = await context
          .read<PersonalHealthRepository>()
          .startHealthEpisode(widget.assessmentId);
      if (!mounted) return;
      setState(() {
        _replaceEpisode(episode);
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error =
            'ไม่สามารถเปิดการติดตามอาการได้ กรุณาเข้าสู่ระบบแล้วลองอีกครั้ง';
      });
    }
  }

  void _replaceEpisode(HealthEpisodeModel episode) {
    _episode = episode;
    for (final symptom in episode.symptoms) {
      _drafts.putIfAbsent(
        symptom.id,
        () => _SymptomDraft(symptom.questions),
      );
    }
  }

  Future<void> _addSymptom() async {
    if (_episode == null) return;
    final symptoms = await context.read<SymptomRepository>().getSymptoms(
      status: '1',
    );
    if (!mounted) return;
    final existing = _episode!.symptoms
        .map((item) => item.symptomId)
        .whereType<String>()
        .toSet();
    final selected = await showModalBottomSheet<_SymptomChoice>(
      context: context,
      isScrollControlled: true,
      builder: (_) => _SymptomPicker(
        symptoms: symptoms
            .where((item) => !existing.contains(item.symptomId))
            .toList(),
      ),
    );
    if (selected == null || !mounted) return;
    try {
      final repository = context.read<PersonalHealthRepository>();
      await repository.addEpisodeSymptom(
        _episode!.id,
        symptomId: selected.symptomId,
        customSymptomText: selected.customText,
      );
      final refreshed = await repository.healthEpisode(_episode!.id);
      if (!mounted) return;
      setState(() => _replaceEpisode(refreshed));
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('เพิ่มอาการไม่สำเร็จ หรืออาการนี้อยู่ในการติดตามแล้ว'),
        ),
      );
    }
  }

  Future<void> _save() async {
    if (_episode == null || _saving) return;
    FocusManager.instance.primaryFocus?.unfocus();
    for (final draft in _drafts.values) {
      final text = draft.temperature.text.trim();
      final value = text.isEmpty ? null : double.tryParse(text);
      if (text.isNotEmpty && (value == null || value < 30 || value > 45)) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('อุณหภูมิต้องอยู่ระหว่าง 30–45 °C')),
        );
        return;
      }
      if (!draft.hasRequiredAnswers) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('กรุณาตอบคำถามที่มีเครื่องหมาย * ให้ครบ')),
        );
        return;
      }
    }
    setState(() => _saving = true);
    try {
      final repository = context.read<PersonalHealthRepository>();
      for (final symptom in _episode!.symptoms.where(
        (item) => item.status == 'A',
      )) {
        final draft = _drafts[symptom.id]!;
        await repository.addEpisodeFollowUp(
          symptom.id,
          severity: draft.severity.round(),
          temperature: double.tryParse(draft.temperature.text.trim()),
          note: draft.note.text,
          answers: draft.serializedAnswers,
        );
      }
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('บันทึกการติดตามทุกอาการแล้ว')),
      );
      Navigator.pop(context, true);
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('บันทึกไม่สำเร็จ กรุณาลองอีกครั้ง')),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('ติดตามอาการ')),
    body: _loading
        ? const Center(child: CircularProgressIndicator())
        : _error != null
        ? Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Text(_error!),
            ),
          )
        : _body(),
  );

  Widget _body() => SafeArea(
    child: ListView(
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
      children: [
        Text('อาการที่กำลังติดตาม', style: AppTextStyles.h3),
        const SizedBox(height: 6),
        Text(
          'อาการแรกเป็นอาการหลัก คุณสามารถเพิ่มอาการร่วมได้หลายอาการ โดยข้อมูลและกราฟจะแยกจากกัน',
          style: AppTextStyles.body2.copyWith(color: AppColors.textSecondary),
        ),
        const SizedBox(height: 14),
        OutlinedButton.icon(
          onPressed: _addSymptom,
          icon: const Icon(Icons.add_rounded),
          label: const Text('เพิ่มอาการใหม่ในการติดตาม'),
        ),
        const SizedBox(height: 18),
        ..._episode!.symptoms
            .where((item) => item.status == 'A')
            .map(_symptomCard),
        const SizedBox(height: 8),
        AppButton(
          label: _saving ? 'กำลังบันทึก...' : 'บันทึกอาการทั้งหมดวันนี้',
          loading: _saving,
          onTap: _save,
        ),
      ],
    ),
  );

  Widget _symptomCard(EpisodeSymptomModel symptom) {
    final draft = _drafts[symptom.id]!;
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    symptom.symptomName,
                    style: AppTextStyles.body1Bold,
                  ),
                ),
                if (symptom.isPrimary)
                  const Chip(
                    label: Text('อาการหลัก'),
                    visualDensity: VisualDensity.compact,
                  ),
              ],
            ),
            const SizedBox(height: 12),
            Text('ระดับความรุนแรง ${draft.severity.round()} / 10'),
            Slider(
              value: draft.severity,
              min: 1,
              max: 10,
              divisions: 9,
              onChanged: (value) => setState(() => draft.severity = value),
            ),
            TextField(
              controller: draft.temperature,
              keyboardType: const TextInputType.numberWithOptions(
                decimal: true,
              ),
              inputFormatters: [
                FilteringTextInputFormatter.allow(RegExp(r'^\d{0,2}([.]\d?)?')),
              ],
              decoration: const InputDecoration(
                labelText: 'อุณหภูมิ (°C) — ไม่บังคับ',
                prefixIcon: Icon(Icons.thermostat_outlined),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: draft.note,
              minLines: 2,
              maxLines: 4,
              maxLength: 500,
              decoration: const InputDecoration(
                labelText: 'บันทึกเพิ่มเติม — ไม่บังคับ',
              ),
            ),
            if (symptom.questions.isNotEmpty) ...[
              const SizedBox(height: 16),
              const Divider(),
              const SizedBox(height: 8),
              Text('คำถามติดตาม', style: AppTextStyles.body1Bold),
              const SizedBox(height: 4),
              Text(
                'ตอบตามอาการที่สังเกตได้ในตอนนี้',
                style: AppTextStyles.body2.copyWith(
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 12),
              ...symptom.questions.map(
                (question) => _questionField(draft, question),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _questionField(
    _SymptomDraft draft,
    FollowUpQuestionModel question,
  ) {
    final title = '${question.questionText}${question.isRequired ? ' *' : ''}';
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: AppTextStyles.body2Bold),
          if (question.description?.trim().isNotEmpty == true) ...[
            const SizedBox(height: 3),
            Text(
              question.description!,
              style: AppTextStyles.body2.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
          ],
          const SizedBox(height: 8),
          if (question.answerType == 'boolean')
            SegmentedButton<bool>(
              segments: const [
                ButtonSegment(value: true, label: Text('มี')),
                ButtonSegment(value: false, label: Text('ไม่มี')),
              ],
              emptySelectionAllowed: true,
              selected: draft.answers[question.id] is bool
                  ? {draft.answers[question.id] as bool}
                  : <bool>{},
              onSelectionChanged: (values) => setState(
                () => draft.answers[question.id] =
                    values.isEmpty ? null : values.first,
              ),
            )
          else if (question.answerType == 'single_choice')
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: question.options.map(
                (option) => ChoiceChip(
                  label: Text(option),
                  selected: draft.answers[question.id] == option,
                  onSelected: (_) => setState(
                    () => draft.answers[question.id] = option,
                  ),
                ),
              ).toList(),
            )
          else if (question.answerType == 'multiple_choice')
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: question.options.map((option) {
                final selected =
                    (draft.answers[question.id] as List<String>? ?? const [])
                        .contains(option);
                return FilterChip(
                  label: Text(option),
                  selected: selected,
                  onSelected: (checked) => setState(() {
                    final values = List<String>.from(
                      draft.answers[question.id] as List<String>? ?? const [],
                    );
                    checked ? values.add(option) : values.remove(option);
                    draft.answers[question.id] = values;
                  }),
                );
              }).toList(),
            )
          else if (question.answerType == 'scale')
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  draft.answers[question.id]?.toString() ?? 'ยังไม่ได้เลือกระดับ',
                  style: AppTextStyles.body2Bold,
                ),
                Slider(
                  value: (() {
                    final selected = question.options.indexOf(
                      draft.answers[question.id]?.toString() ?? '',
                    );
                    return (selected < 0 ? 0 : selected).toDouble();
                  })(),
                  min: 0,
                  max: (question.options.length - 1).toDouble(),
                  divisions: question.options.length - 1,
                  label: draft.answers[question.id]?.toString() ??
                      question.options.first,
                  onChanged: (value) => setState(
                    () => draft.answers[question.id] =
                        question.options[value.round()],
                  ),
                ),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(question.options.first, style: AppTextStyles.body3),
                    Text(question.options.last, style: AppTextStyles.body3),
                  ],
                ),
              ],
            )
          else if (question.answerType == 'date' ||
              question.answerType == 'time')
            TextField(
              controller: draft.questionControllers[question.id],
              readOnly: true,
              decoration: InputDecoration(
                hintText: question.answerType == 'date'
                    ? 'เลือกวันที่'
                    : 'เลือกเวลา',
                suffixIcon: Icon(
                  question.answerType == 'date'
                      ? Icons.calendar_today_outlined
                      : Icons.schedule_outlined,
                ),
              ),
              onTap: () async {
                if (question.answerType == 'date') {
                  final value = await showDatePicker(
                    context: context,
                    firstDate: DateTime(2000),
                    lastDate: DateTime.now(),
                    initialDate: DateTime.now(),
                  );
                  if (value != null) {
                    draft.questionControllers[question.id]!.text =
                        '${value.year.toString().padLeft(4, '0')}-${value.month.toString().padLeft(2, '0')}-${value.day.toString().padLeft(2, '0')}';
                  }
                } else {
                  final value = await showTimePicker(
                    context: context,
                    initialTime: TimeOfDay.now(),
                  );
                  if (value != null) {
                    draft.questionControllers[question.id]!.text =
                        '${value.hour.toString().padLeft(2, '0')}:${value.minute.toString().padLeft(2, '0')}';
                  }
                }
                if (mounted) setState(() {});
              },
            )
          else
            TextField(
              controller: draft.questionControllers[question.id],
              keyboardType: question.answerType == 'number'
                  ? const TextInputType.numberWithOptions(decimal: true)
                  : TextInputType.text,
              maxLines: question.answerType == 'text' ? 3 : 1,
              maxLength: question.answerType == 'text' ? 500 : null,
              decoration: InputDecoration(
                hintText: question.answerType == 'number'
                    ? 'กรอกตัวเลข${question.unit == null ? '' : ' (${question.unit})'}'
                    : 'กรอกคำตอบ',
              ),
            ),
        ],
      ),
    );
  }
}

class _SymptomDraft {
  double severity = 5;
  final temperature = TextEditingController();
  final note = TextEditingController();
  final List<FollowUpQuestionModel> questions;
  final Map<int, dynamic> answers = {};
  final Map<int, TextEditingController> questionControllers = {};

  _SymptomDraft(this.questions) {
    for (final question in questions) {
      if (question.answerType == 'text' ||
          question.answerType == 'number' ||
          question.answerType == 'date' ||
          question.answerType == 'time') {
        questionControllers[question.id] = TextEditingController();
      }
    }
  }

  bool get hasRequiredAnswers => questions.where((item) => item.isRequired).every(
    (question) {
      final controllerValue = questionControllers[question.id]?.text.trim();
      final answer = answers[question.id];
      return (answer != null && (answer is! List || answer.isNotEmpty)) ||
          controllerValue?.isNotEmpty == true;
    },
  );

  List<Map<String, dynamic>> get serializedAnswers => questions
      .map((question) {
        dynamic value = answers[question.id];
        final text = questionControllers[question.id]?.text.trim();
        if (text?.isNotEmpty == true) {
          value = question.answerType == 'number'
              ? double.tryParse(text!)
              : text;
        }
        if (value == null || (value is List && value.isEmpty)) return null;
        return <String, dynamic>{
          'question_template_id': question.id,
          'value': value,
        };
      })
      .whereType<Map<String, dynamic>>()
      .toList();

  void dispose() {
    temperature.dispose();
    note.dispose();
    for (final controller in questionControllers.values) {
      controller.dispose();
    }
  }
}

class _SymptomPicker extends StatefulWidget {
  final List<SymptomModel> symptoms;
  const _SymptomPicker({required this.symptoms});

  @override
  State<_SymptomPicker> createState() => _SymptomPickerState();
}

class _SymptomPickerState extends State<_SymptomPicker> {
  String query = '';

  @override
  Widget build(BuildContext context) {
    final filtered = widget.symptoms
        .where(
          (item) =>
              item.symptomName.toLowerCase().contains(query.toLowerCase()),
        )
        .toList();
    return SafeArea(
      child: SizedBox(
        height: MediaQuery.sizeOf(context).height * 0.72,
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: TextField(
                autofocus: true,
                onChanged: (value) => setState(() => query = value),
                decoration: const InputDecoration(
                  labelText: 'ค้นหาอาการที่ต้องการติดตาม',
                  prefixIcon: Icon(Icons.search),
                ),
              ),
            ),
            Expanded(
              child: filtered.isEmpty
                  ? const Center(child: Text('ไม่พบอาการในรายการ'))
                  : ListView.builder(
                      itemCount: filtered.length,
                      itemBuilder: (context, index) => ListTile(
                        title: Text(filtered[index].symptomName),
                        onTap: () => Navigator.pop(
                          context,
                          _SymptomChoice(symptomId: filtered[index].symptomId),
                        ),
                      ),
                    ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
              child: SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  icon: const Icon(Icons.edit_outlined),
                  label: const Text('ไม่พบในรายการ — เพิ่มชื่ออาการเอง'),
                  onPressed: () async {
                    final controller = TextEditingController(text: query);
                    final text = await showDialog<String>(
                      context: context,
                      builder: (dialogContext) => AlertDialog(
                        title: const Text('เพิ่มอาการที่ต้องการติดตาม'),
                        content: TextField(
                          controller: controller,
                          maxLength: 200,
                          autofocus: true,
                          decoration: const InputDecoration(
                            labelText: 'ชื่ออาการ',
                          ),
                        ),
                        actions: [
                          TextButton(
                            onPressed: () => Navigator.pop(dialogContext),
                            child: const Text('ยกเลิก'),
                          ),
                          FilledButton(
                            onPressed: () {
                              final value = controller.text.trim();
                              if (value.isNotEmpty) {
                                Navigator.pop(dialogContext, value);
                              }
                            },
                            child: const Text('เพิ่ม'),
                          ),
                        ],
                      ),
                    );
                    controller.dispose();
                    if (text != null && context.mounted) {
                      Navigator.pop(context, _SymptomChoice(customText: text));
                    }
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SymptomChoice {
  final String? symptomId;
  final String? customText;

  const _SymptomChoice({this.symptomId, this.customText});
}
