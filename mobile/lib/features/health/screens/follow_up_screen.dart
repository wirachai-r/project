import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../data/repositories/personal_health_repository.dart';
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
  final _formKey = GlobalKey<FormState>();
  final _temperatureController = TextEditingController();
  final _noteController = TextEditingController();
  double _severity = 5;
  bool _saving = false;

  @override
  void dispose() {
    _temperatureController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    FocusManager.instance.primaryFocus?.unfocus();
    setState(() => _saving = true);
    try {
      await context.read<PersonalHealthRepository>().addFollowUp(
        widget.assessmentId,
        severity: _severity.round(),
        temperature: double.tryParse(_temperatureController.text.trim()),
        note: _noteController.text.trim(),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('บันทึกการติดตามอาการแล้ว')));
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
    body: SafeArea(
      child: Form(
        key: _formKey,
        child: ListView(
          keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
          children: [
            _buildHeader(),
            const SizedBox(height: 24),
            _buildSeverityCard(),
            const SizedBox(height: 16),
            TextFormField(
              controller: _temperatureController,
              keyboardType: const TextInputType.numberWithOptions(
                decimal: true,
              ),
              inputFormatters: [
                FilteringTextInputFormatter.allow(RegExp(r'^\d{0,2}([.]\d?)?')),
              ],
              textInputAction: TextInputAction.next,
              decoration: const InputDecoration(
                labelText: 'อุณหภูมิร่างกาย (°C)',
                hintText: 'เช่น 37.5 (ไม่บังคับ)',
                prefixIcon: Icon(Icons.thermostat_outlined),
              ),
              validator: (value) {
                final text = value?.trim() ?? '';
                if (text.isEmpty) return null;
                final temperature = double.tryParse(text);
                if (temperature == null ||
                    temperature < 30 ||
                    temperature > 45) {
                  return 'กรุณาระบุอุณหภูมิระหว่าง 30–45 °C';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _noteController,
              minLines: 4,
              maxLines: 7,
              maxLength: 500,
              textCapitalization: TextCapitalization.sentences,
              decoration: const InputDecoration(
                labelText: 'บันทึกเพิ่มเติม',
                hintText: 'เช่น อาการดีขึ้นหรือแย่ลงอย่างไร',
                alignLabelWithHint: true,
                prefixIcon: Padding(
                  padding: EdgeInsets.only(bottom: 78),
                  child: Icon(Icons.notes_rounded),
                ),
              ),
            ),
            const SizedBox(height: 8),
            _buildEmergencyNotice(),
            const SizedBox(height: 24),
            AppButton(
              label: _saving ? 'กำลังบันทึก...' : 'บันทึกอาการวันนี้',
              icon: const Icon(Icons.check_rounded),
              loading: _saving,
              onTap: _save,
            ),
          ],
        ),
      ),
    ),
  );

  Widget _buildHeader() => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(20),
    decoration: BoxDecoration(
      color: AppColors.primaryLight,
      borderRadius: BorderRadius.circular(20),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(widget.symptomName, style: AppTextStyles.h3),
        const SizedBox(height: 6),
        Text(
          'บันทึกสิ่งที่คุณรู้สึกวันนี้ เพื่อดูแนวโน้มการเปลี่ยนแปลง',
          style: AppTextStyles.body2.copyWith(color: AppColors.textSecondary),
        ),
      ],
    ),
  );

  Widget _buildSeverityCard() => Card(
    child: Padding(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text('ระดับความรุนแรง', style: AppTextStyles.body1Bold),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  '${_severity.round()} / 10',
                  style: AppTextStyles.body2Bold.copyWith(
                    color: AppColors.primary,
                  ),
                ),
              ),
            ],
          ),
          Slider(
            value: _severity,
            min: 1,
            max: 10,
            divisions: 9,
            label: '${_severity.round()}',
            onChanged: (value) => setState(() => _severity = value),
          ),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'เล็กน้อย',
                style: AppTextStyles.body3.copyWith(
                  color: AppColors.textSecondary,
                ),
              ),
              Text(
                'รุนแรงมาก',
                style: AppTextStyles.body3.copyWith(
                  color: AppColors.textSecondary,
                ),
              ),
            ],
          ),
        ],
      ),
    ),
  );

  Widget _buildEmergencyNotice() => Container(
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: AppColors.danger.withValues(alpha: 0.08),
      borderRadius: BorderRadius.circular(16),
      border: Border.all(color: AppColors.danger.withValues(alpha: 0.25)),
    ),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Icon(Icons.emergency_outlined, color: AppColors.danger),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            'หากอาการรุนแรงขึ้น หายใจลำบาก หรือหมดสติ ให้โทร 1669 ทันที',
            style: AppTextStyles.body2.copyWith(color: AppColors.danger),
          ),
        ),
      ],
    ),
  );
}
