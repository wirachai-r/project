import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';

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

  Future<void> _call(String number) => launchUrl(Uri.parse('tel:$number'));

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: AppColors.background,
    appBar: AppBar(
      title: Text('เบอร์โทรฉุกเฉิน', style: AppTextStyles.h4),
      centerTitle: true,
      bottom: const PreferredSize(
        preferredSize: Size.fromHeight(0.5),
        child: Divider(height: 0.5, color: AppColors.border),
      ),
    ),
    body: ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: AppColors.danger.withValues(alpha: 0.08),
            borderRadius: BorderRadius.circular(16),
          ),
          child: Row(
            children: [
              const Icon(Icons.info_outline, color: AppColors.danger),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  'กรณีเจ็บป่วยหรืออุบัติเหตุฉุกเฉิน โทร 1669 และแจ้งสถานที่ อาการ และเบอร์ติดต่อให้ชัดเจน',
                  style: AppTextStyles.body2,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        ..._contacts.map(
          (contact) => Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: Material(
              color: AppColors.white,
              shape: RoundedRectangleBorder(
                side: const BorderSide(color: AppColors.border),
                borderRadius: BorderRadius.circular(16),
              ),
              child: InkWell(
                borderRadius: BorderRadius.circular(16),
                onTap: () => _call(contact.$1),
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
                            Text(contact.$2, style: AppTextStyles.body1Bold),
                            Text(
                              contact.$3,
                              style: AppTextStyles.body3.copyWith(
                                color: AppColors.textSecondary,
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
      ],
    ),
  );
}
