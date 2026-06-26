import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_button.dart';

class AssessmentScreen extends StatefulWidget {
  final List<String> selectedSymptoms;

  const AssessmentScreen({super.key, required this.selectedSymptoms});

  @override
  State<AssessmentScreen> createState() => _AssessmentScreenState();
}

class _AssessmentScreenState extends State<AssessmentScreen> {
  String? _selectedChoice;
  int _currentQ = 0;

  final _questions = [
    {
      'question': 'คุณมีอาการร่วมอะไรอีกไหม',
      'choices': [
        {'id': 'yes', 'text': 'ใช่', 'icon': Icons.check},
        {'id': 'no', 'text': 'ไม่ใช่', 'icon': Icons.close},
      ],
    },
    {
      'question': 'อาการของคุณเริ่มมาตั้งแต่เมื่อไหร่',
      'choices': [
        {'id': '1d', 'text': 'ไม่เกิน 1 วัน', 'icon': Icons.schedule},
        {'id': '3d', 'text': '1-3 วัน', 'icon': Icons.calendar_today},
        {'id': '7d', 'text': 'มากกว่า 3 วัน', 'icon': Icons.calendar_month},
      ],
    },
  ];

  @override
  Widget build(BuildContext context) {
    final q = _questions[_currentQ];
    final choices = q['choices'] as List;
    final hp = Responsive.horizontalPadding;

    return ResponsiveBuilder(
      builder: (context) => Scaffold(
        backgroundColor: AppColors.white,
        appBar: AppBar(
          title: const Text('ประเมินอาการ'),
          leading: IconButton(
            icon: const Icon(Icons.arrow_back),
            onPressed: () {
              if (_currentQ > 0) {
                setState(() { _currentQ--; _selectedChoice = null; });
              } else {
                Navigator.pop(context);
              }
            },
          ),
        ),
        body: Column(
          children: [
            // Progress
            TweenAnimationBuilder<double>(
              tween: Tween(begin: 0, end: (_currentQ + 1) / _questions.length),
              duration: const Duration(milliseconds: 300),
              builder: (_, value, __) => LinearProgressIndicator(
                value: value,
                backgroundColor: AppColors.border,
                valueColor: const AlwaysStoppedAnimation(AppColors.primary),
                minHeight: 4,
              ),
            ),
            Expanded(
              child: SingleChildScrollView(
                padding: EdgeInsets.all(hp),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    SizedBox(height: Responsive.dp(8)),
                    // AI hint
                    Container(
                      padding: EdgeInsets.all(Responsive.dp(16)),
                      decoration: BoxDecoration(
                        color: AppColors.primaryLight,
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Container(
                            width: 36,
                            height: 36,
                            decoration: BoxDecoration(
                              color: AppColors.primary,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Icon(Icons.smart_toy_outlined,
                                color: AppColors.white, size: 20),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('อาการอื่นๆ ที่พบร่วมด้วย',
                                    style: AppTextStyles.body2Bold),
                                const SizedBox(height: 4),
                                Text(
                                  'ระบบกำลังวิเคราะห์ความสัมพันธ์ของอาการทั้งหมด',
                                  style: AppTextStyles.body3
                                      .copyWith(color: AppColors.textSecondary),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    SizedBox(height: Responsive.dp(28)),
                    Text(q['question'] as String, style: AppTextStyles.h4),
                    SizedBox(height: Responsive.dp(20)),
                    ...choices.map((c) => _ChoiceItem(
                          choice: c as Map,
                          selected: _selectedChoice == c['id'],
                          onTap: () =>
                              setState(() => _selectedChoice = c['id'] as String),
                        )),
                  ],
                ),
              ),
            ),

            // Bottom buttons
            Container(
              padding: EdgeInsets.fromLTRB(hp, 12, hp, 32),
              decoration: const BoxDecoration(
                color: AppColors.white,
                border: Border(top: BorderSide(color: AppColors.border)),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: AppButton(
                      label: 'ย้อนกลับ',
                      outlined: true,
                      onTap: () {
                        if (_currentQ > 0) {
                          setState(() { _currentQ--; _selectedChoice = null; });
                        } else {
                          Navigator.pop(context);
                        }
                      },
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: AppButton(
                      label: 'ดำเนินการต่อ',
                      onTap: _selectedChoice == null
                          ? null
                          : () {
                              if (_currentQ < _questions.length - 1) {
                                setState(() {
                                  _currentQ++;
                                  _selectedChoice = null;
                                });
                              } else {
                                _showResult();
                              }
                            },
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showResult() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const _ResultSheet(),
    );
  }
}

class _ChoiceItem extends StatelessWidget {
  final Map choice;
  final bool selected;
  final VoidCallback onTap;

  const _ChoiceItem(
      {required this.choice, required this.selected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        margin: EdgeInsets.only(bottom: Responsive.dp(12)),
        padding: EdgeInsets.symmetric(
            horizontal: Responsive.dp(20), vertical: Responsive.dp(18)),
        decoration: BoxDecoration(
          color: selected ? AppColors.primaryLight : AppColors.white,
          border: Border.all(
            color: selected ? AppColors.primary : AppColors.border,
            width: selected ? 1.5 : 1,
          ),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(
          children: [
            Icon(choice['icon'] as IconData,
                size: 20,
                color: selected ? AppColors.primary : AppColors.textSecondary),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                choice['text'] as String,
                style: AppTextStyles.body2Bold.copyWith(
                    color: selected ? AppColors.primary : AppColors.textPrimary),
              ),
            ),
            AnimatedContainer(
              duration: const Duration(milliseconds: 150),
              width: 22,
              height: 22,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                    color: selected ? AppColors.primary : AppColors.border,
                    width: 1.5),
                color: selected ? AppColors.primary : Colors.transparent,
              ),
              child: selected
                  ? const Icon(Icons.check, color: AppColors.white, size: 14)
                  : null,
            ),
          ],
        ),
      ),
    );
  }
}

class _ResultSheet extends StatelessWidget {
  const _ResultSheet();

  @override
  Widget build(BuildContext context) {
    final hp = Responsive.horizontalPadding;

    return Container(
      height: MediaQuery.of(context).size.height * 0.72,
      decoration: const BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        children: [
          const SizedBox(height: 8),
          Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
                color: AppColors.border,
                borderRadius: BorderRadius.circular(2)),
          ),
          SizedBox(height: Responsive.dp(20)),
          Expanded(
            child: SingleChildScrollView(
              padding: EdgeInsets.symmetric(horizontal: hp),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('ผลการประเมิน', style: AppTextStyles.h3),
                  SizedBox(height: Responsive.dp(16)),
                  _UrgencyCard(
                    level: 'ระดับเหลือง (Yellow)',
                    desc: 'ควรพบแพทย์ภายใน 24 ชั่วโมง',
                    color: AppColors.urgencyYellow,
                    bg: const Color(0xFFFFFBE6),
                  ),
                  SizedBox(height: Responsive.dp(16)),
                  Container(
                    padding: EdgeInsets.all(Responsive.dp(16)),
                    decoration: BoxDecoration(
                      color: AppColors.surface,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('คำแนะนำเบื้องต้น', style: AppTextStyles.body1Bold),
                        SizedBox(height: Responsive.dp(8)),
                        Text(
                          '• พักผ่อนให้เพียงพอ ดื่มน้ำมากๆ\n• รับประทานยาลดไข้ตามคำแนะนำ\n• หากมีไข้สูงเกิน 39°C ควรพบแพทย์ทันที',
                          style: AppTextStyles.body2
                              .copyWith(color: AppColors.textSecondary, height: 1.7),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          Padding(
            padding: EdgeInsets.fromLTRB(hp, 0, hp, 32),
            child: Column(
              children: [
                AppButton(
                  label: 'กลับหน้าหลัก',
                  onTap: () => Navigator.of(context).popUntil((r) => r.isFirst),
                ),
                SizedBox(height: Responsive.dp(12)),
                AppButton(
                  label: 'ค้นหาสถานพยาบาลใกล้เคียง',
                  outlined: true,
                  onTap: () {},
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _UrgencyCard extends StatelessWidget {
  final String level;
  final String desc;
  final Color color;
  final Color bg;

  const _UrgencyCard(
      {required this.level,
      required this.desc,
      required this.color,
      required this.bg});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.all(Responsive.dp(16)),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withValues(alpha: 0.4)),
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
            child: const Icon(Icons.warning_amber_rounded,
                color: AppColors.white, size: 24),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(level,
                    style: AppTextStyles.body2Bold.copyWith(color: color)),
                Text(desc,
                    style: AppTextStyles.body3
                        .copyWith(color: AppColors.textSecondary)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
