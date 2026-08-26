import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../data/models/body_area_group_model.dart';
import '../../../data/models/symptom_model.dart';
import '../../../data/repositories/symptom_repository.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'assessment_screen.dart';
import 'symptom_select_screen.dart';

class BodyAreaGroupScreen extends StatefulWidget {
  const BodyAreaGroupScreen({super.key});

  @override
  State<BodyAreaGroupScreen> createState() => _BodyAreaGroupScreenState();
}

class _BodyAreaGroupScreenState extends State<BodyAreaGroupScreen> {
  final _searchController = TextEditingController();
  late Future<List<BodyAreaGroupModel>> _groups;

  @override
  void initState() {
    super.initState();
    _groups = context.read<SymptomRepository>().getBodyAreaGroups();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _openGroup(BodyAreaGroupModel group) async {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );
    try {
      final symptoms = await context
          .read<SymptomRepository>()
          .getBodyAreaSymptoms(group.id);
      if (!mounted) return;
      Navigator.pop(context);
      await Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => _BodyAreaSymptomsScreen(
            title: group.name,
            symptoms: symptoms,
          ),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(
          const SnackBar(content: Text('ไม่สามารถโหลดรายการอาการได้')),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    final padding = Responsive.horizontalPadding;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('เลือกบริเวณที่ไม่สบาย')),
      body: FutureBuilder<List<BodyAreaGroupModel>>(
        future: _groups,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppLoadingView();
          }
          if (snapshot.hasError) {
            return AppMessageView.error(
              message: 'ไม่สามารถโหลดกลุ่มบริเวณได้',
              onAction: () => setState(() {
                _groups = context
                    .read<SymptomRepository>()
                    .getBodyAreaGroups();
              }),
            );
          }
          final groups = snapshot.data ?? [];
          return ListView(
            padding: EdgeInsets.fromLTRB(padding, 16, padding, 28),
            children: [
              SearchBar(
                controller: _searchController,
                hintText: 'ค้นหาอาการ เช่น ปวดหัว ท้องเสีย',
                leading: const Icon(Icons.search_rounded),
                onTap: () => Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const SymptomSelectScreen(),
                  ),
                ),
              ),
              const SizedBox(height: 22),
              Text('คุณไม่สบายตรงไหน?', style: AppTextStyles.h3),
              const SizedBox(height: 6),
              Text(
                'เลือกกลุ่มบริเวณเพื่อดูอาการที่เกี่ยวข้อง',
                style: AppTextStyles.body2.copyWith(
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 16),
              if (groups.isEmpty)
                const AppMessageView.empty(
                  title: 'ยังไม่มีกลุ่มบริเวณ',
                  message: 'กรุณาค้นหาอาการทั้งหมดแทน',
                )
              else
                ...groups.map(
                  (group) => Padding(
                    padding: const EdgeInsets.only(bottom: 14),
                    child: _BodyAreaCard(
                      group: group,
                      onTap: () => _openGroup(group),
                    ),
                  ),
                ),
              OutlinedButton.icon(
                onPressed: () => Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const SymptomSelectScreen(),
                  ),
                ),
                icon: const Icon(Icons.help_outline_rounded),
                label: const Text('ไม่แน่ใจบริเวณ ดูอาการทั้งหมด'),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _BodyAreaCard extends StatelessWidget {
  final BodyAreaGroupModel group;
  final VoidCallback onTap;

  const _BodyAreaCard({required this.group, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Semantics(
      button: true,
      label: '${group.name} มี ${group.symptomsCount} อาการ',
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Ink(
          height: Responsive.dp(190),
          decoration: BoxDecoration(
            color: AppColors.primary,
            borderRadius: BorderRadius.circular(20),
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(20),
            child: Stack(
              fit: StackFit.expand,
              children: [
                if (group.imageUrl != null)
                  CachedNetworkImage(
                    imageUrl: group.imageUrl!,
                    fit: BoxFit.cover,
                    errorWidget: (_, _, _) => const SizedBox.shrink(),
                  ),
                DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [
                        Colors.black.withValues(alpha: .68),
                        Colors.black.withValues(alpha: .08),
                      ],
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.all(22),
                  child: Align(
                    alignment: Alignment.centerLeft,
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 210),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            group.name,
                            style: AppTextStyles.h2.copyWith(
                              color: AppColors.white,
                            ),
                          ),
                          if (group.description?.isNotEmpty == true) ...[
                            const SizedBox(height: 8),
                            Text(
                              group.description!,
                              maxLines: 3,
                              overflow: TextOverflow.ellipsis,
                              style: AppTextStyles.body2.copyWith(
                                color: AppColors.white,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _BodyAreaSymptomsScreen extends StatelessWidget {
  final String title;
  final List<SymptomModel> symptoms;

  const _BodyAreaSymptomsScreen({required this.title, required this.symptoms});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: Text(title)),
      body: symptoms.isEmpty
          ? const AppMessageView.empty(
              title: 'ยังไม่มีอาการ',
              message: 'กลุ่มนี้ยังไม่มีรายการอาการที่เปิดใช้งาน',
            )
          : ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: symptoms.length,
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (context, index) {
                final symptom = symptoms[index];
                return Card(
                  child: ListTile(
                    title: Text(
                      symptom.symptomName,
                      style: AppTextStyles.body1Bold,
                    ),
                    subtitle: symptom.description?.isNotEmpty == true
                        ? Text(
                            symptom.description!,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          )
                        : null,
                    trailing: const Icon(Icons.chevron_right_rounded),
                    onTap: () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => AssessmentScreen(
                          symptomId: symptom.symptomId,
                          symptomName: symptom.symptomName,
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
    );
  }
}
