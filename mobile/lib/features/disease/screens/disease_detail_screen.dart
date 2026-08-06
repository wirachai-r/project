import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_html/flutter_html.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/constants/api_constants.dart';
import '../../../data/models/disease_model.dart';
import '../../../data/models/treatment_order_model.dart';
import '../providers/disease_detail_provider.dart';
import '../../../shared/widgets/bookmark_button.dart';

class DiseaseDetailScreen extends StatefulWidget {
  final String diseaseId;
  const DiseaseDetailScreen({super.key, required this.diseaseId});

  @override
  State<DiseaseDetailScreen> createState() => _DiseaseDetailScreenState();
}

class _DiseaseDetailScreenState extends State<DiseaseDetailScreen> {
  final Map<String, GlobalKey> _sectionKeys = {};
  String? _failedImageUrl;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DiseaseDetailProvider>().load(widget.diseaseId);
    });
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<DiseaseDetailProvider>();

    return Scaffold(
      appBar: AppBar(
        backgroundColor: AppColors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Text('ข้อมูลโรค', style: AppTextStyles.h4),
        centerTitle: true,
        actions: [BookmarkButton(type: 'App\\Models\\Disease', itemId: widget.diseaseId)],
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
      return const Center(child: CircularProgressIndicator());
    }
    if (provider.error != null) {
      return Center(child: Text(provider.error!, style: AppTextStyles.body2));
    }

    final disease = provider.detail;
    if (disease == null) {
      return Center(
        child: Text('ไม่พบข้อมูลโรค', style: AppTextStyles.body2),
      );
    }

    return RefreshIndicator(
      color: AppColors.primary,
      backgroundColor: AppColors.white,
      elevation: 0,
      onRefresh: () => provider.load(widget.diseaseId, trackView: false),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildHeader(
            disease.diseaseName,
            disease.diseaseNameEn,
            disease.diseaseImage,
          ),
          const SizedBox(height: 16),
          _buildArticleMeta(disease),
          const SizedBox(height: 16),
          _buildSectionPicker(disease),
          const SizedBox(height: 16),
          if (_hasText(disease.description))
            _sectionCard(
              sectionId: 'description',
              icon: Icons.info_outline_rounded,
              iconColor: AppColors.primary,
              title: 'เกี่ยวกับโรค',
              child: _html(disease.description!),
            ),
          const SizedBox(height: 24),

          if (_hasText(disease.symptomDescription))
            _sectionCard(
              sectionId: 'symptoms',
              icon: Icons.assignment_outlined,
              iconColor: AppColors.primary,
              title: 'อาการ',
              child: _html(disease.symptomDescription!),
            ),

          if (disease.cause != null && disease.cause!.trim().isNotEmpty)
            _sectionCard(
              sectionId: 'cause',
              icon: Icons.coronavirus_outlined,
              iconColor: AppColors.primaryMid,
              title: 'สาเหตุ',
              child: _html(disease.cause!),
            ),

          if (_hasText(disease.complications))
            _sectionCard(
              sectionId: 'complications',
              icon: Icons.warning_amber_rounded,
              iconColor: AppColors.warning,
              title: 'ภาวะแทรกซ้อน',
              child: _html(disease.complications!),
            ),
          if (_hasText(disease.diagnosis))
            _sectionCard(
              sectionId: 'diagnosis',
              icon: Icons.fact_check_outlined,
              iconColor: AppColors.primary,
              title: 'การวินิจฉัย',
              child: _html(disease.diagnosis!),
            ),
          if (_hasText(disease.medicalTreatment))
            _sectionCard(
              sectionId: 'treatment',
              icon: Icons.medication_outlined,
              iconColor: AppColors.primary,
              title: 'การรักษา',
              child: _html(disease.medicalTreatment!),
            ),
          if (_hasText(disease.selfCare))
            _sectionCard(
              sectionId: 'selfCare',
              icon: Icons.self_improvement_rounded,
              iconColor: AppColors.success,
              title: 'การดูแลตัวเอง',
              child: _html(disease.selfCare!),
            ),
          if (_hasText(disease.whenToSeeDoctor))
            _sectionCard(
              sectionId: 'doctor',
              icon: Icons.local_hospital_outlined,
              iconColor: AppColors.danger,
              title: 'ควรพบแพทย์เมื่อใด',
              child: _html(disease.whenToSeeDoctor!),
            ),
          if (_hasText(disease.prevention))
            _sectionCard(
              sectionId: 'prevention',
              icon: Icons.shield_outlined,
              iconColor: AppColors.success,
              title: 'การป้องกัน',
              child: _html(disease.prevention!),
            ),
          if (_hasText(disease.recommendations))
            _sectionCard(
              sectionId: 'recommendations',
              icon: Icons.lightbulb_outline_rounded,
              iconColor: AppColors.warning,
              title: 'คำแนะนำ',
              child: _html(disease.recommendations!),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader(String name, String? nameEn, String? image) {
    final imageUrl = _resolveImageUrl(image);
    final showImage = imageUrl != null && imageUrl != _failedImageUrl;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (showImage) ...[
          ClipRRect(
            borderRadius: BorderRadius.circular(16),
            child: AspectRatio(
              aspectRatio: 16 / 9,
              child: Image.network(
                imageUrl,
                width: double.infinity,
                fit: BoxFit.cover,
                frameBuilder: (context, child, frame, wasSynchronouslyLoaded) {
                  if (wasSynchronouslyLoaded || frame != null) return child;
                  return Container(
                    color: AppColors.primaryLight,
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
                  return const SizedBox.shrink();
                },
              ),
            ),
          ),
          const SizedBox(height: 12),
        ],
        Text(name, style: AppTextStyles.h3),
        if (nameEn != null && nameEn.isNotEmpty)
          Text(
            '($nameEn)',
            style: AppTextStyles.h4.copyWith(color: AppColors.textSecondary),
          ),
      ],
    );
  }

  bool _hasText(String? value) => value?.trim().isNotEmpty == true;

  List<MapEntry<String, String>> _availableSections(DiseaseModel disease) => [
    if (_hasText(disease.description)) const MapEntry('description', 'เกี่ยวกับโรค'),
    if (_hasText(disease.symptomDescription)) const MapEntry('symptoms', 'อาการ'),
    if (_hasText(disease.cause)) const MapEntry('cause', 'สาเหตุ'),
    if (_hasText(disease.complications)) const MapEntry('complications', 'ภาวะแทรกซ้อน'),
    if (_hasText(disease.diagnosis)) const MapEntry('diagnosis', 'การวินิจฉัย'),
    if (_hasText(disease.medicalTreatment)) const MapEntry('treatment', 'การรักษา'),
    if (_hasText(disease.selfCare)) const MapEntry('selfCare', 'การดูแลตัวเอง'),
    if (_hasText(disease.whenToSeeDoctor)) const MapEntry('doctor', 'ควรพบแพทย์เมื่อใด'),
    if (_hasText(disease.prevention)) const MapEntry('prevention', 'การป้องกัน'),
    if (_hasText(disease.recommendations)) const MapEntry('recommendations', 'คำแนะนำ'),
  ];

  Widget _buildSectionPicker(DiseaseModel disease) {
    final sections = _availableSections(disease);
    if (sections.length < 2) return const SizedBox.shrink();
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(14),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          isExpanded: true,
          hint: Text(
            'เลือกหัวข้อที่ต้องการอ่าน',
            style: AppTextStyles.body2,
          ),
          icon: const Icon(Icons.keyboard_arrow_down_rounded),
          items: sections
              .map((section) => DropdownMenuItem(
                    value: section.key,
                    child: Text(
                      section.value,
                      style: AppTextStyles.body2,
                    ),
                  ))
              .toList(),
          onChanged: (id) {
            final context = id == null ? null : _sectionKeys[id]?.currentContext;
            if (context != null) {
              Scrollable.ensureVisible(
                context,
                duration: const Duration(milliseconds: 350),
                curve: Curves.easeInOut,
                alignment: .08,
              );
            }
          },
        ),
      ),
    );
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
    return origin.resolve(image.startsWith('/') ? image : '/storage/$image').toString();
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

  String? _formatDate(DateTime? date) {
    if (date == null) return null;
    return '${date.day.toString().padLeft(2, '0')}/'
        '${date.month.toString().padLeft(2, '0')}/'
        '${date.year + 543}';
  }

  Widget _metaItem(IconData icon, String text) => Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: AppColors.textSecondary),
          const SizedBox(width: 6),
          Text(text, style: AppTextStyles.body3.copyWith(color: AppColors.textSecondary)),
        ],
      );

  Widget _html(String value) {
    final bodyStyle = AppTextStyles.body2;
    final boldStyle = AppTextStyles.body2Bold;

    return Html(
      data: value,
      style: {
        'body': Style(
          margin: Margins.zero,
          padding: HtmlPaddings.zero,
          color: bodyStyle.color,
          fontFamily: bodyStyle.fontFamily,
          fontFamilyFallback: bodyStyle.fontFamilyFallback,
          fontSize: FontSize(bodyStyle.fontSize ?? 14),
          fontWeight: bodyStyle.fontWeight,
          letterSpacing: bodyStyle.letterSpacing,
          lineHeight: const LineHeight(1.6),
        ),
        'p': Style(margin: Margins.only(bottom: 10)),
        'ul': Style(margin: Margins.only(bottom: 8)),
        'ol': Style(margin: Margins.only(bottom: 8)),
        'strong': Style(
          fontFamily: boldStyle.fontFamily,
          fontFamilyFallback: boldStyle.fontFamilyFallback,
          fontSize: FontSize(boldStyle.fontSize ?? 14),
          fontWeight: boldStyle.fontWeight,
        ),
      },
    );
  }

  Widget _sectionCard({
    required String sectionId,
    required IconData icon,
    required Color iconColor,
    required String title,
    required Widget child,
  }) {
    return Container(
      key: _sectionKeys.putIfAbsent(sectionId, GlobalKey.new),
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

  Widget _bulletItem(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 8),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.only(top: 6, right: 8),
          child: CircleAvatar(radius: 3, backgroundColor: AppColors.primary),
        ),
        Expanded(
          child: Text(
            text.trim(),
            style: AppTextStyles.body2.copyWith(height: 1.5),
          ),
        ),
      ],
    ),
  );

  Widget _treatmentItem(TreatmentOrderModel order) => Container(
    margin: const EdgeInsets.only(bottom: 10),
    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
    decoration: BoxDecoration(
      color: AppColors.surface,
      borderRadius: BorderRadius.circular(12),
    ),
    child: Row(
      children: [
        Icon(
          _treatmentIcon(order.orderName),
          color: AppColors.primary,
          size: 18,
        ),
        const SizedBox(width: 10),
        Expanded(child: Text(order.orderName, style: AppTextStyles.body2)),
      ],
    ),
  );

  Widget _careItem(String text) {
    // แยก "หัวข้อ: รายละเอียด" ถ้ามี ':' ในบรรทัด
    final parts = text.split(':');
    final title = parts.first.trim();
    final desc = parts.length > 1 ? parts.sublist(1).join(':').trim() : null;

    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(_careIcon(title), color: AppColors.primary, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: AppTextStyles.body2Bold),
                if (desc != null && desc.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(
                      desc,
                      style: AppTextStyles.body3.copyWith(
                        color: AppColors.textSecondary,
                        height: 1.5,
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  IconData _treatmentIcon(String name) {
    if (name.contains('ไข้') || name.contains('ปวด'))
      return Icons.medication_outlined;
    if (name.contains('น้ำมูก') || name.contains('แพ้'))
      return Icons.water_drop_outlined;
    if (name.contains('ไอ') || name.contains('เสมหะ'))
      return Icons.air_outlined;
    return Icons.medical_services_outlined;
  }

  IconData _careIcon(String title) {
    if (title.contains('น้ำ')) return Icons.local_drink_outlined;
    if (title.contains('พัก') || title.contains('นอน'))
      return Icons.bed_outlined;
    if (title.contains('อบอุ่น') || title.contains('อุณหภูมิ'))
      return Icons.thermostat_outlined;
    return Icons.tips_and_updates_outlined;
  }
}
