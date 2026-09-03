import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:flutter_html/flutter_html.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/rich_text_html.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../core/constants/api_constants.dart';
import '../../../data/models/disease_model.dart';
import '../providers/disease_detail_provider.dart';
import '../../../shared/widgets/bookmark_button.dart';
import '../../../shared/widgets/content_report_button.dart';
import '../../../shared/widgets/reference_links_section.dart';

class DiseaseDetailScreen extends StatefulWidget {
  final String diseaseId;
  const DiseaseDetailScreen({super.key, required this.diseaseId});

  @override
  State<DiseaseDetailScreen> createState() => _DiseaseDetailScreenState();
}

class _DiseaseDetailScreenState extends State<DiseaseDetailScreen> {
  String? _failedImageUrl;
  String? _selectedSectionId;
  final _scrollController = ScrollController();
  final _sectionScrollController = ScrollController();
  final _diseaseTitleKey = GlobalKey();
  bool _showTitleInAppBar = false;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_updateAppBarTitle);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DiseaseDetailProvider>().load(widget.diseaseId);
    });
  }

  @override
  void dispose() {
    _scrollController
      ..removeListener(_updateAppBarTitle)
      ..dispose();
    _sectionScrollController.dispose();
    super.dispose();
  }

  void _updateAppBarTitle() {
    final titleContext = _diseaseTitleKey.currentContext;
    if (titleContext == null) return;
    final renderBox = titleContext.findRenderObject() as RenderBox?;
    if (renderBox == null || !renderBox.hasSize) return;
    final titleBottom =
        renderBox.localToGlobal(Offset.zero).dy + renderBox.size.height;
    final appBarBottom = MediaQuery.paddingOf(context).top + kToolbarHeight;
    final shouldShowTitle = titleBottom <= appBarBottom;
    if (shouldShowTitle != _showTitleInAppBar) {
      setState(() => _showTitleInAppBar = shouldShowTitle);
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<DiseaseDetailProvider>();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Text(
          _showTitleInAppBar &&
                  provider.detail?.diseaseName.trim().isNotEmpty == true
              ? provider.detail!.diseaseName
              : 'รายละเอียดข้อมูลโรค',
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: AppTextStyles.h4,
        ),
        centerTitle: true,
        actions: [
          ContentReportButton(
            targetType: 'disease',
            targetId: widget.diseaseId,
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(0.5),
          child: Divider(height: 0.5, thickness: 0.5, color: AppColors.border),
        ),
      ),
      body: _buildBody(provider),
    );
  }

  Widget _buildBody(DiseaseDetailProvider provider) {
    if (provider.isLoading) {
      return const AppLoadingView();
    }
    if (provider.error != null) {
      return AppMessageView.error(
        message: provider.error!,
        onAction: () => provider.load(widget.diseaseId, trackView: false),
      );
    }

    final disease = provider.detail;
    if (disease == null) {
      return const AppMessageView.empty(
        title: 'ไม่พบข้อมูลโรค',
        message: 'ข้อมูลนี้อาจถูกย้ายหรือยังไม่พร้อมใช้งาน',
      );
    }

    return RefreshIndicator(
      color: AppColors.primary,
      backgroundColor: AppColors.white,
      elevation: 0,
      onRefresh: () => provider.load(widget.diseaseId, trackView: false),
      child: ListView(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 32),
        children: [
          _buildHeroImage(disease.diseaseImage),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 22, 20, 0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildHeader(disease),
                const SizedBox(height: 12),
                _buildArticleMeta(disease),
                const SizedBox(height: 18),
                _buildActions(disease),
                const SizedBox(height: 22),
                _buildSectionPicker(disease),
                const SizedBox(height: 18),
                _buildSelectedSection(disease),
                ReferenceLinksSection(links: disease.references),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader(DiseaseModel disease) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (disease.category != null) ...[
          Chip(label: Text(disease.category!.categoryName)),
          const SizedBox(height: 10),
        ],
        Text(
          disease.diseaseName,
          key: _diseaseTitleKey,
          style: AppTextStyles.h2,
        ),
        if (_hasText(disease.diseaseNameEn))
          Text(
            '(${disease.diseaseNameEn})',
            style: AppTextStyles.h4.copyWith(color: AppColors.textSecondary),
          ),
      ],
    );
  }

  Widget _buildHeroImage(String? image) {
    final imageUrl = _resolveImageUrl(image);
    if (imageUrl == null || imageUrl == _failedImageUrl) {
      return const SizedBox.shrink();
    }

    return AspectRatio(
      aspectRatio: 16 / 9,
      child: Image.network(
        imageUrl,
        width: double.infinity,
        fit: BoxFit.cover,
        frameBuilder: (context, child, frame, wasSynchronouslyLoaded) {
          if (wasSynchronouslyLoaded || frame != null) return child;
          return Container(
            color: AppColors.surface,
            alignment: Alignment.center,
            child: const CircularProgressIndicator(),
          );
        },
        errorBuilder: (_, __, ___) {
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (mounted && _failedImageUrl != imageUrl) {
              setState(() => _failedImageUrl = imageUrl);
            }
          });
          return const ColoredBox(color: AppColors.surface);
        },
      ),
    );
  }

  bool _hasText(String? value) => value?.trim().isNotEmpty == true;

  List<MapEntry<String, String>> _availableSections(DiseaseModel disease) => [
    if (_hasText(disease.description))
      const MapEntry('description', 'เกี่ยวกับโรค'),
    if (_hasText(disease.symptomDescription))
      const MapEntry('symptoms', 'อาการ'),
    if (_hasText(disease.cause)) const MapEntry('cause', 'สาเหตุ'),
    if (_hasText(disease.complications))
      const MapEntry('complications', 'ภาวะแทรกซ้อน'),
    if (_hasText(disease.diagnosis)) const MapEntry('diagnosis', 'การวินิจฉัย'),
    if (_hasText(disease.medicalTreatment))
      const MapEntry('treatment', 'การรักษา'),
    if (_hasText(disease.selfCare)) const MapEntry('selfCare', 'การดูแลตัวเอง'),
    if (_hasText(disease.whenToSeeDoctor))
      const MapEntry('doctor', 'ควรพบแพทย์เมื่อใด'),
    if (_hasText(disease.prevention))
      const MapEntry('prevention', 'การป้องกัน'),
    if (_hasText(disease.recommendations))
      const MapEntry('recommendations', 'คำแนะนำ'),
  ];

  Widget _buildSectionPicker(DiseaseModel disease) {
    final sections = _availableSections(disease);
    if (sections.length < 2) return const SizedBox.shrink();
    final selectedId = sections.any((item) => item.key == _selectedSectionId)
        ? _selectedSectionId!
        : sections.first.key;

    return SizedBox(
      height: 52,
      child: ListView.separated(
        controller: _sectionScrollController,
        physics: const BouncingScrollPhysics(),
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.only(right: 20),
        itemCount: sections.length,
        separatorBuilder: (_, __) => const SizedBox.shrink(),
        itemBuilder: (context, index) {
          final section = sections[index];
          final selected = section.key == selectedId;
          return Material(
            color: selected
                ? AppColors.primary.withValues(alpha: 0.16)
                : Colors.transparent,
            child: InkWell(
              onTap: () => _selectSection(sections, index),
              child: Container(
                constraints: const BoxConstraints(minWidth: 142),
                padding: const EdgeInsets.symmetric(horizontal: 22),
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  border: Border(
                    bottom: BorderSide(
                      color: selected
                          ? AppColors.primary
                          : AppColors.borderStrong,
                      width: selected ? 3 : 1,
                    ),
                  ),
                ),
                child: Text(
                  section.value,
                  maxLines: 1,
                  style:
                      (selected ? AppTextStyles.body2Bold : AppTextStyles.body2)
                          .copyWith(
                            color: selected
                                ? AppColors.primary
                                : AppColors.textSecondary,
                          ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildSelectedSection(DiseaseModel disease) {
    final sections = _availableSections(disease);
    if (sections.isEmpty) return const SizedBox.shrink();
    final id = sections.any((item) => item.key == _selectedSectionId)
        ? _selectedSectionId!
        : sections.first.key;

    final content = switch (id) {
      'symptoms' => _sectionCard(
        icon: Icons.assignment_outlined,
        iconColor: AppColors.primary,
        title: 'อาการ',
        child: _html(disease.symptomDescription!),
      ),
      'cause' => _sectionCard(
        icon: Icons.coronavirus_outlined,
        iconColor: AppColors.primaryMid,
        title: 'สาเหตุ',
        child: _html(disease.cause!),
      ),
      'complications' => _sectionCard(
        icon: Icons.warning_amber_rounded,
        iconColor: AppColors.warning,
        title: 'ภาวะแทรกซ้อน',
        child: _html(disease.complications!),
      ),
      'diagnosis' => _sectionCard(
        icon: Icons.fact_check_outlined,
        iconColor: AppColors.primary,
        title: 'การวินิจฉัย',
        child: _html(disease.diagnosis!),
      ),
      'treatment' => _sectionCard(
        icon: Icons.medication_outlined,
        iconColor: AppColors.primary,
        title: 'การรักษา',
        child: _html(disease.medicalTreatment!),
      ),
      'selfCare' => _sectionCard(
        icon: Icons.self_improvement_rounded,
        iconColor: AppColors.success,
        title: 'การดูแลตัวเอง',
        child: _html(disease.selfCare!),
      ),
      'doctor' => _sectionCard(
        icon: Icons.local_hospital_outlined,
        iconColor: AppColors.danger,
        title: 'ควรพบแพทย์เมื่อใด',
        child: _html(disease.whenToSeeDoctor!),
      ),
      'prevention' => _sectionCard(
        icon: Icons.shield_outlined,
        iconColor: AppColors.success,
        title: 'การป้องกัน',
        child: _html(disease.prevention!),
      ),
      'recommendations' => _sectionCard(
        icon: Icons.lightbulb_outline_rounded,
        iconColor: AppColors.warning,
        title: 'คำแนะนำ',
        child: _html(disease.recommendations!),
      ),
      _ => _sectionCard(
        icon: Icons.info_outline_rounded,
        iconColor: AppColors.primary,
        title: 'เกี่ยวกับโรค',
        child: _html(disease.description!),
      ),
    };

    return GestureDetector(
      behavior: HitTestBehavior.translucent,
      onHorizontalDragEnd: (details) {
        final velocity = details.primaryVelocity ?? 0;
        if (velocity.abs() < 180) return;
        final index = sections.indexWhere((item) => item.key == id);
        _selectSection(sections, velocity < 0 ? index + 1 : index - 1);
      },
      child: AnimatedSwitcher(
        duration: const Duration(milliseconds: 220),
        switchInCurve: Curves.easeOut,
        switchOutCurve: Curves.easeIn,
        child: KeyedSubtree(key: ValueKey(id), child: content),
      ),
    );
  }

  void _selectSection(List<MapEntry<String, String>> sections, int index) {
    if (index < 0 || index >= sections.length) return;
    setState(() => _selectedSectionId = sections[index].key);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_sectionScrollController.hasClients) return;
      const estimatedTabWidth = 142.0;
      final viewportWidth = _sectionScrollController.position.viewportDimension;
      final target =
          (index * estimatedTabWidth - (viewportWidth - estimatedTabWidth) / 2)
              .clamp(
                _sectionScrollController.position.minScrollExtent,
                _sectionScrollController.position.maxScrollExtent,
              )
              .toDouble();
      _sectionScrollController.animateTo(
        target,
        duration: const Duration(milliseconds: 250),
        curve: Curves.easeOut,
      );
    });
  }

  String? _resolveImageUrl(String? value) {
    final image = value?.trim();
    if (image == null || image.isEmpty) return null;

    final uri = Uri.tryParse(image);
    if (uri != null && uri.hasScheme) return uri.toString();

    final apiUri = Uri.parse(ApiConstants.baseUrl);
    final origin = Uri(
      scheme: apiUri.scheme,
      host: apiUri.host,
      port: apiUri.hasPort ? apiUri.port : null,
    );
    return origin
        .resolve(image.startsWith('/') ? image : '/storage/$image')
        .toString();
  }

  Widget _buildArticleMeta(DiseaseModel disease) {
    final publishedText = _formatDate(disease.publishedAt);
    final updatedText = _formatDate(disease.updatedAt);
    final showUpdated = updatedText != null && updatedText != publishedText;

    return Wrap(
      spacing: 16,
      runSpacing: 8,
      children: [
        if (publishedText != null)
          _metaItem(
            Icons.calendar_today_outlined,
            'วันที่เผยแพร่ $publishedText',
          ),
        if (showUpdated)
          _metaItem(Icons.update_rounded, 'แก้ไขล่าสุด $updatedText'),
        _metaItem(Icons.visibility_outlined, '${disease.viewCount} ครั้ง'),
      ],
    );
  }

  Widget _buildActions(DiseaseModel disease) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 6),
    decoration: BoxDecoration(
      color: AppColors.white,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: AppColors.border),
    ),
    child: Row(
      children: [
        Expanded(
          child: _ContentAction(
            label: 'แชร์',
            icon: Icons.ios_share_rounded,
            onTap: () => SharePlus.instance.share(
              ShareParams(
                text:
                    'ข้อมูลโรค: ${disease.diseaseName}'
                    '${_hasText(disease.diseaseNameEn) ? ' (${disease.diseaseNameEn})' : ''}'
                    '\nอ่านเพิ่มเติมในแอป',
              ),
            ),
          ),
        ),
        Container(width: 1, height: 36, color: AppColors.border),
        Expanded(
          child: BookmarkButton(
            type: 'App\\Models\\Disease',
            itemId: widget.diseaseId,
            selectedColor: AppColors.warning,
            compact: true,
            label: 'บันทึก',
            labelStyle: AppTextStyles.body3,
          ),
        ),
      ],
    ),
  );

  String? _formatDate(DateTime? date) {
    if (date == null) return null;
    return formatThaiDate(date);
  }

  Widget _metaItem(IconData icon, String text) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Icon(icon, size: 16, color: AppColors.textSecondary),
      const SizedBox(width: 6),
      Text(
        text,
        style: AppTextStyles.body3.copyWith(color: AppColors.textSecondary),
      ),
    ],
  );

  Widget _html(String value) {
    return Html(
      data: RichTextHtml.resolveMediaUrls(value),
      extensions: RichTextHtml.imageExtensions,
      style: {
        'body': Style(
          margin: Margins.zero,
          padding: HtmlPaddings.zero,
          color: AppColors.textPrimary,
          fontSize: FontSize(16),
          lineHeight: const LineHeight(1.7),
        ),
        'p': Style(margin: Margins.only(bottom: 12)),
        'ul': Style(margin: Margins.only(bottom: 8)),
        'ol': Style(margin: Margins.only(bottom: 8)),
        'img': Style(margin: Margins.symmetric(vertical: 10)),
      },
    );
  }

  Widget _sectionCard({
    required IconData icon,
    required Color iconColor,
    required String title,
    required Widget child,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: iconColor, size: 20),
              const SizedBox(width: 8),
              Text(title, style: AppTextStyles.body1Bold),
            ],
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}

class _ContentAction extends StatelessWidget {
  final String label;
  final IconData icon;
  final VoidCallback onTap;

  const _ContentAction({
    required this.label,
    required this.icon,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) => Material(
    color: Colors.transparent,
    borderRadius: BorderRadius.circular(14),
    child: InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      hoverColor: AppColors.surface,
      splashColor: AppColors.primary.withValues(alpha: 0.10),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: AppColors.textPrimary, size: 24),
            const SizedBox(height: 4),
            Text(label, style: AppTextStyles.body3),
          ],
        ),
      ),
    ),
  );
}
