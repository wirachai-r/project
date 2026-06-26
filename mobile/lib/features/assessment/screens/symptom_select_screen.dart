import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_button.dart';
import 'assessment_screen.dart';

class SymptomSelectScreen extends StatefulWidget {
  const SymptomSelectScreen({super.key});

  @override
  State<SymptomSelectScreen> createState() => _SymptomSelectScreenState();
}

class _SymptomSelectScreenState extends State<SymptomSelectScreen> {
  final Set<String> _selected = {};
  final _searchCtrl = TextEditingController();

  final _popular = [
    {'id': 'fever', 'name': 'ตัวร้อน (ไข้)', 'icon': Icons.thermostat},
    {'id': 'headache', 'name': 'ปวดหัว', 'icon': Icons.psychology},
    {'id': 'stomach', 'name': 'ปวดท้อง', 'icon': Icons.sick},
    {'id': 'itch', 'name': 'ผื่นคัน', 'icon': Icons.healing},
  ];

  final _others = [
    {'id': 'cough', 'name': 'ไอแห้ง', 'icon': Icons.air},
    {'id': 'breath', 'name': 'หายใจลำบาก', 'icon': Icons.wind_power},
    {'id': 'tired', 'name': 'อ่อนเพลีย', 'icon': Icons.bedtime},
    {'id': 'nose', 'name': 'น้ำมูกไหล', 'icon': Icons.face},
    {'id': 'throat', 'name': 'เจ็บคอ', 'icon': Icons.record_voice_over},
    {'id': 'back', 'name': 'ปวดหลัง', 'icon': Icons.accessibility_new},
  ];

  @override
  Widget build(BuildContext context) {
    final hp = Responsive.horizontalPadding;

    return ResponsiveBuilder(
      builder: (context) => Scaffold(
        backgroundColor: AppColors.white,
        appBar: AppBar(
          automaticallyImplyLeading: false,
          title: const Text('ระบุอาการของคุณ'),
          actions: [
            IconButton(
              icon: const Icon(Icons.close),
              onPressed: () => Navigator.pop(context),
            ),
          ],
        ),
        body: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                padding: EdgeInsets.symmetric(horizontal: hp),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    SizedBox(height: Responsive.dp(8)),
                    Text('เลือกอาการของคุณ', style: AppTextStyles.h3),
                    SizedBox(height: Responsive.dp(4)),
                    Text(
                      'คุณสามารถเลือกได้มากกว่า 1 อาการ เพื่อให้ AI วิเคราะห์ได้อย่างแม่นยำ',
                      style: AppTextStyles.body3.copyWith(color: AppColors.textSecondary),
                    ),
                    SizedBox(height: Responsive.dp(16)),

                    // Search
                    TextField(
                      controller: _searchCtrl,
                      decoration: InputDecoration(
                        hintText: 'ค้นหาอาการ เช่น ปวดหัว, ตัวร้อน',
                        prefixIcon: const Icon(Icons.search, color: AppColors.textSecondary),
                        filled: true,
                        fillColor: AppColors.surface,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(30),
                          borderSide: BorderSide.none,
                        ),
                      ),
                    ),
                    SizedBox(height: Responsive.dp(20)),

                    // Popular
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('ยอดนิยม', style: AppTextStyles.body1Bold),
                        GestureDetector(
                          onTap: () {},
                          child: Text('ดูทั้งหมด',
                              style: AppTextStyles.body3Bold
                                  .copyWith(color: AppColors.primary)),
                        ),
                      ],
                    ),
                    SizedBox(height: Responsive.dp(12)),
                    GridView.count(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      crossAxisCount: 2,
                      mainAxisSpacing: 12,
                      crossAxisSpacing: 12,
                      childAspectRatio: Responsive.isSmall ? 1.4 : 1.6,
                      children: _popular
                          .map((s) => _PopularCard(
                                item: s,
                                selected: _selected.contains(s['id']),
                                onTap: () => setState(() {
                                  final id = s['id'] as String;
                                  _selected.contains(id)
                                      ? _selected.remove(id)
                                      : _selected.add(id);
                                }),
                              ))
                          .toList(),
                    ),
                    SizedBox(height: Responsive.dp(20)),

                    Text('อาการอื่นๆ', style: AppTextStyles.body1Bold),
                    SizedBox(height: Responsive.dp(12)),
                    ..._others.map((s) => _OtherItem(
                          item: s,
                          selected: _selected.contains(s['id']),
                          onTap: () => setState(() {
                            final id = s['id'] as String;
                            _selected.contains(id)
                                ? _selected.remove(id)
                                : _selected.add(id);
                          }),
                        )),
                    SizedBox(height: Responsive.dp(16)),
                  ],
                ),
              ),
            ),

            // Bottom bar
            Container(
              padding: EdgeInsets.fromLTRB(hp, 12, hp, 28),
              decoration: const BoxDecoration(
                color: AppColors.white,
                border: Border(top: BorderSide(color: AppColors.border)),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      'เลือกแล้ว ${_selected.length} อาการ',
                      style: AppTextStyles.body3
                          .copyWith(color: AppColors.textSecondary),
                    ),
                  ),
                  if (_selected.isNotEmpty)
                    GestureDetector(
                      onTap: () => setState(() => _selected.clear()),
                      child: Text('ล้างทั้งหมด',
                          style: AppTextStyles.body3Bold
                              .copyWith(color: AppColors.primary)),
                    ),
                  const SizedBox(width: 12),
                  SizedBox(
                    width: 130,
                    child: AppButton(
                      label: 'ถัดไป →',
                      height: 48,
                      onTap: _selected.isEmpty
                          ? null
                          : () => Navigator.push(
                              context,
                              MaterialPageRoute(
                                  builder: (_) => AssessmentScreen(
                                      selectedSymptoms: _selected.toList()))),
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
}

class _PopularCard extends StatelessWidget {
  final Map item;
  final bool selected;
  final VoidCallback onTap;

  const _PopularCard(
      {required this.item, required this.selected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        decoration: BoxDecoration(
          color: selected ? AppColors.primaryLight : AppColors.surface,
          borderRadius: BorderRadius.circular(16),
          border: selected
              ? Border.all(color: AppColors.primary, width: 1.5)
              : null,
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(item['icon'] as IconData,
                size: 30,
                color: selected ? AppColors.primary : AppColors.textSecondary),
            const SizedBox(height: 8),
            Text(item['name'] as String,
                textAlign: TextAlign.center,
                style: AppTextStyles.body3Bold.copyWith(
                    color:
                        selected ? AppColors.primary : AppColors.textPrimary)),
          ],
        ),
      ),
    );
  }
}

class _OtherItem extends StatelessWidget {
  final Map item;
  final bool selected;
  final VoidCallback onTap;

  const _OtherItem(
      {required this.item, required this.selected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        margin: EdgeInsets.only(bottom: Responsive.dp(10)),
        padding: EdgeInsets.symmetric(
            horizontal: Responsive.dp(16), vertical: Responsive.dp(14)),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: selected
              ? Border.all(color: AppColors.primary, width: 1.5)
              : null,
        ),
        child: Row(
          children: [
            Icon(item['icon'] as IconData,
                size: 22,
                color: selected ? AppColors.primary : AppColors.textSecondary),
            const SizedBox(width: 12),
            Expanded(
              child: Text(item['name'] as String,
                  style: AppTextStyles.body2Bold.copyWith(
                      color: selected
                          ? AppColors.primary
                          : AppColors.textPrimary)),
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
