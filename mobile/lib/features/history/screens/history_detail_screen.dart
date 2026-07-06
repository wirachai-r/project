import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../providers/history_detail_provider.dart';

class HistoryDetailScreen extends StatefulWidget {
  final dynamic assessmentId;
  const HistoryDetailScreen({super.key, required this.assessmentId});

  @override
  State<HistoryDetailScreen> createState() => _HistoryDetailScreenState();
}

class _HistoryDetailScreenState extends State<HistoryDetailScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<HistoryDetailProvider>().load(
        assessmentId: widget.assessmentId,
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      // backgroundColor: const Color(0 intelligence_background_or_light_grey => 0xFFF8F9FA), // ใช้พื้นหลังสีเทาอ่อนเพื่อให้ Card ลอยเด่นแบบหน้า Result
      appBar: AppBar(
        backgroundColor: AppColors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Text('ผลการประเมินย้อนหลัง', style: AppTextStyles.h4),
        centerTitle: true,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(0.5),
          child: Divider(height: 0.5, thickness: 0.5, color: AppColors.border),
        ),
      ),
      body: Consumer<HistoryDetailProvider>(
        builder: (context, provider, _) {
          if (provider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }
          if (provider.error != null || provider.detail == null) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Text(
                  provider.error ?? 'ไม่พบข้อมูลการประเมิน',
                  style: AppTextStyles.body1,
                  textAlign: TextAlign.center,
                ),
              ),
            );
          }
          return _buildResultStyleContent(provider.detail!);
        },
      ),
    );
  }

  Widget _buildResultStyleContent(detail) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // 1. ส่วนหัวบอกอาการหลัก (คงเดิมไว้)
          Card(
            color: AppColors.white,
            elevation: 0,
            shape: RoundedRectangleBorder(
              side: BorderSide(color: AppColors.border, width: 0.5),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppColors.primary.withOpacity(0.1),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.assignment_outlined,
                      color: AppColors.primary,
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'อาการหลักที่ประเมิน',
                          style: AppTextStyles.body3.copyWith(
                            color: AppColors.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          detail.symptomName,
                          style: AppTextStyles.body1Bold,
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'วันที่: ${detail.createdAt}',
                          style: AppTextStyles.body3.copyWith(
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // 2. ส่วนแสดงผลการวินิจฉัยและระดับความรุนแรง
          Text('ผลการตรวจอาการเบื้องต้น', style: AppTextStyles.h3),
          const SizedBox(height: 12),

          if (detail.results.isEmpty)
            Card(
              color: AppColors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
              child: const Padding(
                padding: EdgeInsets.all(24),
                child: Center(
                  child: Text('ไม่พบความเสี่ยงร้ายแรงจากอาการดังกล่าว'),
                ),
              ),
            )
          else
            ...detail.results.map((res) {
              final urgencyColor = AppColors.urgencyColor(res.urgencyLevel);
              return Card(
                color: AppColors.white,
                elevation: 0,
                margin: const EdgeInsets.only(bottom: 20),
                shape: RoundedRectangleBorder(
                  side: BorderSide(color: AppColors.border, width: 0.5),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: urgencyColor.withOpacity(0.1),
                        borderRadius: const BorderRadius.only(
                          topLeft: Radius.circular(16),
                          topRight: Radius.circular(16),
                        ),
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(Icons.add_circle, color: urgencyColor, size: 20),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  AppColors.urgencyLabel(res.urgencyLevel),
                                  style: AppTextStyles.body1Bold.copyWith(
                                    color: urgencyColor,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  res.recommendation,
                                  style: AppTextStyles.body2,
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),

                    // 💥 ส่วนที่เพิ่มขึ้นมา: หัวข้อ "ดูข้อมูลโรค" และลิสต์การ์ดกางออกแบบในรูปภาพตัวอย่าง
                    if (res.diseases.isNotEmpty) ...[
                      Padding(
                        padding: EdgeInsets.only(top: 16, left: 16, right: 16),
                        child: Text(
                          'ข้อมูลโรค',
                          style: AppTextStyles.body2Bold,
                        ),
                      ),
                      Padding(
                        padding: const EdgeInsets.all(12),
                        child: Column(
                          children: res.diseases.map<Widget>((disease) {
                            return Theme(
                              // ลบเส้นขอบเริ่มต้นของ ExpansionTile ออกเพื่อให้คลีน ๆ แบบเว็บ
                              data: Theme.of(
                                context,
                              ).copyWith(dividerColor: Colors.transparent),
                              child: Card(
                                color: AppColors.white,
                                elevation: 0,
                                margin: const EdgeInsets.only(bottom: 8),
                                shape: RoundedRectangleBorder(
                                  side: BorderSide(
                                    color: AppColors.border.withOpacity(0.6),
                                    width: 0.5,
                                  ),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: ExpansionTile(
                                  title: Text(
                                    disease.diseaseName,
                                    style: AppTextStyles.body1Bold.copyWith(
                                      color: AppColors.primary,
                                    ),
                                  ),
                                  childrenPadding: const EdgeInsets.only(
                                    left: 16,
                                    right: 16,
                                    bottom: 16,
                                  ),
                                  expandedCrossAxisAlignment:
                                      CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      disease.description,
                                      style: AppTextStyles.body2.copyWith(
                                        height: 1.5,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            );
                          }).toList(),
                        ),
                      ),
                    ],
                  ],
                ),
              );
            }),

          const SizedBox(height: 16),

          // 3. ส่วนแสดงประวัติคำตอบ (คงเดิมไว้)
          if (detail.answers.isNotEmpty) ...[
            Text('ประวัติการตอบคำถาม', style: AppTextStyles.h3),
            const SizedBox(height: 12),
            Card(
              color: AppColors.white,
              elevation: 0,
              shape: RoundedRectangleBorder(
                side: BorderSide(color: AppColors.border, width: 0.5),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: detail.answers.length,
                  itemBuilder: (context, index) {
                    final ans = detail.answers[index];
                    return Padding(
                      padding: EdgeInsets.only(
                        bottom: index == detail.answers.length - 1 ? 0 : 12,
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Padding(
                            padding: EdgeInsets.only(top: 3),
                            child: Icon(
                              Icons.check_circle,
                              size: 16,
                              color: AppColors.success,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              ans.choiceText,
                              style: AppTextStyles.body2,
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
