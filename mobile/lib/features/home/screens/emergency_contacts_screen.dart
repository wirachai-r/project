import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_layout.dart';

class EmergencyContactsScreen extends StatelessWidget {
  const EmergencyContactsScreen({super.key});

  static const _contacts = [
    (
      '1669',
      'การแพทย์ฉุกเฉิน',
      'อุบัติเหตุ เจ็บป่วยฉุกเฉิน และรถพยาบาล',
      Icons.emergency_outlined,
    ),
    (
      '191',
      'เหตุด่วนเหตุร้าย',
      'ตำรวจและเหตุอันตรายเร่งด่วน',
      Icons.local_police_outlined,
    ),
    (
      '199',
      'อัคคีภัยและกู้ภัย',
      'แจ้งเหตุเพลิงไหม้และขอความช่วยเหลือกู้ภัย',
      Icons.fire_truck_outlined,
    ),
    (
      '1155',
      'ตำรวจท่องเที่ยว',
      'ช่วยเหลือนักท่องเที่ยวตลอด 24 ชั่วโมง',
      Icons.travel_explore_outlined,
    ),
    (
      '1300',
      'ศูนย์ช่วยเหลือสังคม',
      'ปัญหาสังคม เด็ก ผู้สูงอายุ และความรุนแรง',
      Icons.volunteer_activism_outlined,
    ),
    (
      '1323',
      'สายด่วนสุขภาพจิต',
      'ปรึกษาสุขภาพจิตตลอด 24 ชั่วโมง',
      Icons.psychology_outlined,
    ),
    (
      '1422',
      'สายด่วนกรมควบคุมโรค',
      'สอบถามข้อมูลโรคและภัยสุขภาพ',
      Icons.health_and_safety_outlined,
    ),
  ];

  Future<void> _call(BuildContext context, String number) async {
    final opened = await launchUrl(Uri.parse('tel:$number'));
    if (!opened && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('ไม่สามารถโทร $number จากอุปกรณ์นี้ได้')),
      );
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: Theme.of(context).scaffoldBackgroundColor,
    appBar: AppBar(
      title: Text('เบอร์โทรฉุกเฉิน', style: AppTextStyles.h4),
      centerTitle: true,
      bottom: PreferredSize(
        preferredSize: Size.fromHeight(0.5),
        child: Divider(
          height: 0.5,
          color: Theme.of(context).colorScheme.outlineVariant,
        ),
      ),
    ),
    body: ResponsiveBuilder(
      builder: (context) => AppContentWidth(
        child: ListView(
          padding: EdgeInsets.fromLTRB(
            Responsive.horizontalPadding,
            16,
            Responsive.horizontalPadding,
            32,
          ),
          children: [
            const AppInfoBanner(
              icon: Icons.emergency_outlined,
              color: AppColors.danger,
              message:
                  'กรณีเจ็บป่วยหรืออุบัติเหตุฉุกเฉิน โทร 1669 และแจ้งสถานที่ อาการ และเบอร์ติดต่อให้ชัดเจน',
            ),
            const SizedBox(height: 16),
            ..._contacts.map(
              (contact) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Material(
                  color: Theme.of(context).colorScheme.surface,
                  shape: RoundedRectangleBorder(
                    side: BorderSide(
                      color: Theme.of(context).colorScheme.outlineVariant,
                    ),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Semantics(
                    button: true,
                    label: 'โทร ${contact.$1} ${contact.$2}',
                    excludeSemantics: true,
                    child: InkWell(
                      borderRadius: BorderRadius.circular(16),
                      onTap: () => _call(context, contact.$1),
                      child: Padding(
                        padding: const EdgeInsets.all(14),
                        child: Row(
                          children: [
                            Container(
                              width: 44,
                              height: 44,
                              decoration: BoxDecoration(
                                color: AppColors.danger.withValues(alpha: 0.1),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Icon(contact.$4, color: AppColors.danger),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    contact.$2,
                                    style: AppTextStyles.body1Bold,
                                  ),
                                  Text(
                                    contact.$3,
                                    style: AppTextStyles.body3.copyWith(
                                      color: Theme.of(
                                        context,
                                      ).colorScheme.onSurfaceVariant,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            Column(
                              children: [
                                Text(
                                  contact.$1,
                                  style: AppTextStyles.body1Bold.copyWith(
                                    color: AppColors.danger,
                                  ),
                                ),
                                Text(
                                  'โทร',
                                  style: AppTextStyles.body3.copyWith(
                                    color: AppColors.danger,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    ),
  );
}
