import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/fuzzy_search.dart';
import '../../../core/utils/responsive.dart';
import '../../../data/models/body_area_group_model.dart';
import '../../../data/models/symptom_model.dart';
import '../../../data/repositories/symptom_repository.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../shared/widgets/app_layout.dart';
import '../../../shared/widgets/symptom_icon.dart';
import '../widgets/assessment_progress.dart';
import 'assessment_screen.dart';
import 'symptom_select_screen.dart';

class BodyAreaGroupScreen extends StatefulWidget {
  const BodyAreaGroupScreen({super.key});

  @override
  State<BodyAreaGroupScreen> createState() => _BodyAreaGroupScreenState();
}

class _BodyAreaGroupScreenState extends State<BodyAreaGroupScreen> {
  late Future<List<BodyAreaGroupModel>> _groups;

  @override
  void initState() {
    super.initState();
    _groups = context.read<SymptomRepository>().getBodyAreaGroups();
  }

  Future<void> _refreshGroups() async {
    final groups = context.read<SymptomRepository>().getBodyAreaGroups();
    setState(() => _groups = groups);
    await groups;
  }

  void _openGroup(BodyAreaGroupModel group) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => group.subgroups.isEmpty
            ? _BodyAreaSymptomsScreen(group: group)
            : _BodyAreaSubgroupsScreen(group: group),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    final padding = Responsive.horizontalPadding;

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        centerTitle: true,
        automaticallyImplyLeading: false,
        title: Text('เลือกบริเวณที่ไม่สบาย', style: AppTextStyles.h4),
        actions: [
          IconButton(
            tooltip: 'ปิด',
            onPressed: () => Navigator.maybePop(context),
            icon: const Icon(Icons.close_rounded),
          ),
        ],
        bottom: PreferredSize(
          preferredSize: Size.fromHeight(0.5),
          child: Divider(
            height: 0.5,
            thickness: 0.5,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
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
                _groups = context.read<SymptomRepository>().getBodyAreaGroups();
              }),
            );
          }
          final groups = snapshot.data ?? [];
          return RefreshIndicator(
            color: AppColors.primary,
            backgroundColor: Theme.of(context).colorScheme.surface,
            elevation: 0,
            onRefresh: _refreshGroups,
            child: AppContentWidth(
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: EdgeInsets.fromLTRB(padding, 16, padding, 28),
                children: [
                  const AssessmentProgress(
                    currentStep: 1,
                    title: 'คุณไม่สบายตรงไหน?',
                    description: 'เลือกบริเวณที่ใกล้เคียงกับอาการมากที่สุด',
                  ),
                  const SizedBox(height: 20),
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
              ),
            ),
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
        borderRadius: BorderRadius.circular(16),
        child: AspectRatio(
          aspectRatio: 16 / 9,
          child: Ink(
            decoration: BoxDecoration(
              color: AppColors.primary,
              borderRadius: BorderRadius.circular(16),
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: Stack(
                fit: StackFit.expand,
                children: [
                  if (group.imageUrl?.isNotEmpty == true)
                    CachedNetworkImage(
                      imageUrl: group.imageUrl!,
                      fit: BoxFit.cover,
                      fadeInDuration: const Duration(milliseconds: 250),
                      placeholder: (_, _) => const _BodyAreaImagePlaceholder(),
                      errorWidget: (_, _, _) =>
                          const _BodyAreaImagePlaceholder(),
                    ),
                  if (group.imageUrl?.isNotEmpty != true)
                    const _BodyAreaImagePlaceholder(),
                  DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.centerLeft,
                        end: Alignment.centerRight,
                        stops: const [0, .52, 1],
                        colors: [
                          const Color(0xFF111052).withValues(alpha: .96),
                          const Color(0xFF17156B).withValues(alpha: .72),
                          const Color(0xFF17156B).withValues(alpha: .06),
                        ],
                      ),
                    ),
                  ),
                  Positioned(
                    top: 16,
                    left: 18,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 9,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: Theme.of(
                          context,
                        ).colorScheme.surface.withValues(alpha: .16),
                        borderRadius: BorderRadius.circular(99),
                        border: Border.all(
                          color: AppColors.white.withValues(alpha: .24),
                        ),
                      ),
                      child: Text(
                        '${group.symptomsCount} อาการ',
                        style: AppTextStyles.body2.copyWith(
                          color: AppColors.white,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(18, 46, 18, 16),
                    child: Align(
                      alignment: Alignment.bottomLeft,
                      child: ConstrainedBox(
                        constraints: BoxConstraints(
                          maxWidth: Responsive.isSmall ? 190 : 230,
                        ),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              group.name,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: AppTextStyles.h2.copyWith(
                                color: AppColors.white,
                              ),
                            ),
                            if (group.description?.isNotEmpty == true) ...[
                              const SizedBox(height: 8),
                              Text(
                                group.description!,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: AppTextStyles.body2.copyWith(
                                  color: AppColors.white.withValues(alpha: .88),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ),
                  ),
                  Positioned(
                    right: 14,
                    bottom: 14,
                    child: Container(
                      width: 36,
                      height: 36,
                      decoration: BoxDecoration(
                        color: Theme.of(
                          context,
                        ).colorScheme.surface.withValues(alpha: .92),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.arrow_forward_rounded,
                        size: 20,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _BodyAreaImagePlaceholder extends StatelessWidget {
  const _BodyAreaImagePlaceholder();

  @override
  Widget build(BuildContext context) {
    return const ColoredBox(
      color: AppColors.primary,
      child: Align(
        alignment: Alignment.centerRight,
        child: Padding(
          padding: EdgeInsets.only(right: 34),
          child: Icon(
            Icons.accessibility_new_rounded,
            size: 92,
            color: Color(0x33FFFFFF),
          ),
        ),
      ),
    );
  }
}

class _BodyAreaSubgroupsScreen extends StatelessWidget {
  final BodyAreaGroupModel group;

  const _BodyAreaSubgroupsScreen({required this.group});

  void _openSymptoms(BuildContext context, [BodyAreaSubgroupModel? subgroup]) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) =>
            _BodyAreaSymptomsScreen(group: group, subgroup: subgroup),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        centerTitle: true,
        title: Text(group.name, style: AppTextStyles.h4),
        bottom: PreferredSize(
          preferredSize: Size.fromHeight(0.5),
          child: Divider(
            height: 0.5,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: AppContentWidth(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 20, 16, 28),
          children: [
            const AssessmentProgress(
              currentStep: 1,
              title: 'เลือกบริเวณย่อย',
              description: 'เลือกตำแหน่งที่ใกล้เคียงกับอาการของคุณ',
            ),
            const SizedBox(height: 18),
            ...group.subgroups.map(
              (subgroup) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Material(
                  color: Theme.of(context).colorScheme.surface,
                  borderRadius: BorderRadius.circular(18),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(18),
                    onTap: () => _openSymptoms(context, subgroup),
                    child: Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(
                          color: Theme.of(context).colorScheme.outlineVariant,
                        ),
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 56,
                            height: 56,
                            clipBehavior: Clip.antiAlias,
                            decoration: BoxDecoration(
                              color: Theme.of(
                                context,
                              ).colorScheme.surfaceContainerLow,
                              borderRadius: BorderRadius.all(
                                Radius.circular(14),
                              ),
                            ),
                            child: subgroup.imageUrl?.isNotEmpty == true
                                ? CachedNetworkImage(
                                    imageUrl: subgroup.imageUrl!,
                                    fit: BoxFit.cover,
                                    errorWidget: (_, _, _) => const Icon(
                                      Icons.location_on_outlined,
                                      color: AppColors.primary,
                                    ),
                                  )
                                : const Icon(
                                    Icons.location_on_outlined,
                                    color: AppColors.primary,
                                  ),
                          ),
                          const SizedBox(width: 13),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  subgroup.name,
                                  style: AppTextStyles.body1Bold,
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  subgroup.description?.isNotEmpty == true
                                      ? subgroup.description!
                                      : '${subgroup.symptomsCount} อาการ',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: AppTextStyles.body2.copyWith(
                                    color: Theme.of(
                                      context,
                                    ).colorScheme.onSurfaceVariant,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Icon(
                            Icons.arrow_forward_ios_rounded,
                            size: 16,
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurfaceVariant,
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 6),
            OutlinedButton.icon(
              onPressed: () => _openSymptoms(context),
              icon: const Icon(Icons.list_alt_rounded),
              label: Text('ดูอาการทั้งหมดใน${group.name}'),
            ),
          ],
        ),
      ),
    );
  }
}

class _BodyAreaSymptomsScreen extends StatefulWidget {
  final BodyAreaGroupModel group;
  final BodyAreaSubgroupModel? subgroup;

  const _BodyAreaSymptomsScreen({required this.group, this.subgroup});

  @override
  State<_BodyAreaSymptomsScreen> createState() =>
      _BodyAreaSymptomsScreenState();
}

class _BodyAreaSymptomsScreenState extends State<_BodyAreaSymptomsScreen> {
  late Future<List<SymptomModel>> _symptoms;
  final _searchController = TextEditingController();
  String _search = '';

  @override
  void initState() {
    super.initState();
    _symptoms = _load();
    _searchController.addListener(_onSearchChanged);
  }

  void _onSearchChanged() {
    setState(() => _search = _searchController.text.trim().toLowerCase());
  }

  @override
  void dispose() {
    _searchController
      ..removeListener(_onSearchChanged)
      ..dispose();
    super.dispose();
  }

  Future<List<SymptomModel>> _load() {
    if (widget.subgroup != null) {
      return context.read<SymptomRepository>().getBodyAreaSubgroupSymptoms(
        widget.group.id,
        widget.subgroup!.id,
      );
    }
    return context.read<SymptomRepository>().getBodyAreaSymptoms(
      widget.group.id,
    );
  }

  Future<void> _refresh() async {
    final symptoms = _load();
    setState(() => _symptoms = symptoms);
    await symptoms;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        centerTitle: true,
        title: Text(
          widget.subgroup?.name ?? widget.group.name,
          style: AppTextStyles.h4,
        ),
        bottom: PreferredSize(
          preferredSize: Size.fromHeight(0.5),
          child: Divider(
            height: 0.5,
            thickness: 0.5,
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
      ),
      body: FutureBuilder<List<SymptomModel>>(
        future: _symptoms,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppLoadingView();
          }
          if (snapshot.hasError) {
            return AppMessageView.error(
              message: 'ไม่สามารถโหลดรายการอาการได้',
              onAction: _refresh,
            );
          }

          final symptoms = snapshot.data ?? [];
          if (symptoms.isEmpty) {
            return const AppMessageView.empty(
              title: 'ยังไม่มีอาการ',
              message: 'กลุ่มนี้ยังไม่มีรายการอาการที่เปิดใช้งาน',
            );
          }

          final filteredSymptoms = _search.isEmpty
              ? symptoms
              : symptoms
                    .where(
                      (symptom) => fuzzyContains(symptom.symptomName, _search),
                    )
                    .toList();

          return RefreshIndicator(
            color: AppColors.primary,
            backgroundColor: Theme.of(context).colorScheme.surface,
            elevation: 0,
            onRefresh: _refresh,
            child: AppContentWidth(
              child: ListView.separated(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(16, 20, 16, 28),
                itemCount: filteredSymptoms.length + 1,
                separatorBuilder: (_, _) => const SizedBox(height: 10),
                itemBuilder: (context, index) {
                  if (index == 0) {
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 4),
                      child: Column(
                        children: [
                          AssessmentProgress(
                            currentStep: 2,
                            title: 'เลือกอาการ',
                            description:
                                'เลือกอาการที่ใกล้เคียงกับคุณมากที่สุด',
                            trailing: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 6,
                              ),
                              decoration: BoxDecoration(
                                color: Theme.of(
                                  context,
                                ).colorScheme.surfaceContainerLow,
                                borderRadius: BorderRadius.circular(99),
                              ),
                              child: Text(
                                '${filteredSymptoms.length} อาการ',
                                style: AppTextStyles.body3Bold.copyWith(
                                  color: AppColors.primary,
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(height: 12),
                          SearchBar(
                            controller: _searchController,
                            hintText: 'ค้นหาอาการ',
                            leading: Icon(
                              Icons.search_rounded,
                              color: Theme.of(
                                context,
                              ).colorScheme.onSurfaceVariant,
                            ),
                            trailing: [
                              if (_search.isNotEmpty)
                                IconButton(
                                  tooltip: 'ล้างคำค้นหา',
                                  onPressed: _searchController.clear,
                                  icon: const Icon(Icons.close_rounded),
                                ),
                            ],
                          ),
                          if (_search.isNotEmpty &&
                              filteredSymptoms.isEmpty) ...[
                            const SizedBox(height: 48),
                            Text(
                              'ไม่พบอาการที่ค้นหา',
                              style: AppTextStyles.body2.copyWith(
                                color: Theme.of(
                                  context,
                                ).colorScheme.onSurfaceVariant,
                              ),
                            ),
                          ],
                        ],
                      ),
                    );
                  }

                  final symptom = filteredSymptoms[index - 1];
                  return Material(
                    color: Theme.of(context).colorScheme.surface,
                    borderRadius: BorderRadius.circular(18),
                    child: InkWell(
                      borderRadius: BorderRadius.circular(18),
                      onTap: () => Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => AssessmentScreen(
                            symptomId: symptom.symptomId,
                            symptomName: symptom.symptomName,
                          ),
                        ),
                      ),
                      child: Container(
                        padding: const EdgeInsets.fromLTRB(14, 13, 12, 13),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(18),
                          border: Border.all(
                            color: Theme.of(context).colorScheme.outlineVariant,
                          ),
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 42,
                              height: 42,
                              alignment: Alignment.center,
                              decoration: BoxDecoration(
                                color: Theme.of(
                                  context,
                                ).colorScheme.surfaceContainerLow,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: SymptomIcon(
                                iconName: symptom.symptomImage,
                                size: 21,
                                color: AppColors.primary,
                              ),
                            ),
                            const SizedBox(width: 13),
                            Expanded(
                              child: Text(
                                symptom.symptomName,
                                style: AppTextStyles.body1Bold,
                              ),
                            ),
                            const SizedBox(width: 8),
                            Icon(
                              Icons.arrow_forward_ios_rounded,
                              size: 16,
                              color: Theme.of(
                                context,
                              ).colorScheme.onSurfaceVariant,
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
          );
        },
      ),
    );
  }
}
