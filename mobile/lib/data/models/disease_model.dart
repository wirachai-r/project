import 'disease_category_model.dart';
import 'treatment_order_model.dart';

class DiseaseModel {
  final String diseaseId;
  final String diseaseName;
  final String? diseaseNameEn;
  final String? description;
  final String? cause;
  final String? symptomDescription;
  final String? complications;
  final String? diagnosis;
  final String? medicalTreatment;
  final String? selfCare;
  final String? whenToSeeDoctor;
  final String? prevention;
  final String? recommendations;
  final String? diseaseImage;
  final String? reference;
  final List<String> references;
  final DateTime? publishedAt;
  final DateTime? updatedAt;
  final int viewCount;
  final String status;
  final bool isPopular; // 👈 เพิ่ม
  final DiseaseCategoryModel? category;
  final List<TreatmentOrderModel> treatmentOrders;

  DiseaseModel({
    required this.diseaseId,
    required this.diseaseName,
    this.diseaseNameEn,
    this.description,
    this.cause,
    this.symptomDescription,
    this.complications,
    this.diagnosis,
    this.medicalTreatment,
    this.selfCare,
    this.whenToSeeDoctor,
    this.prevention,
    this.recommendations,
    this.diseaseImage,
    this.reference,
    this.references = const [],
    this.publishedAt,
    this.updatedAt,
    this.viewCount = 0,
    required this.status,
    this.isPopular = false,
    this.category,
    this.treatmentOrders = const [],
  });

  factory DiseaseModel.fromJson(Map<String, dynamic> json) {
    return DiseaseModel(
      diseaseId: json['disease_id'] ?? '',
      diseaseName: json['disease_name'] ?? '',
      diseaseNameEn: json['disease_name_en'],
      description: json['description'],
      cause: json['cause'],
      symptomDescription: json['symptom_description'],
      complications: json['complications'],
      diagnosis: json['diagnosis'],
      medicalTreatment: json['medical_treatment'],
      selfCare: json['self_care'],
      whenToSeeDoctor: json['when_to_see_doctor'],
      prevention: json['prevention'],
      recommendations: json['recommendations'],
      diseaseImage: json['disease_image'],
      reference: json['reference'],
      references: (json['references'] as List<dynamic>? ?? const [])
          .map((item) => item.toString())
          .toList(),
      publishedAt: _parseDate(json['published_at'] ?? json['created_at']),
      updatedAt: _parseDate(json['updated_at']),
      viewCount: int.tryParse('${json['view_count'] ?? 0}') ?? 0,
      status: json['status'] ?? '1',
      isPopular: _parseBool(json['is_popular']),
      category: json['category'] != null
          ? DiseaseCategoryModel.fromJson(json['category'])
          : null,
      treatmentOrders: (json['treatment_orders'] as List<dynamic>? ?? [])
          .map((e) => TreatmentOrderModel.fromJson(e))
          .toList(),
    );
  }

  static bool _parseBool(dynamic v) {
    if (v == null) return false;
    if (v is bool) return v;
    if (v is int) return v == 1;
    if (v is String) return v == '1' || v.toLowerCase() == 'true';
    return false;
  }

  static DateTime? _parseDate(dynamic value) =>
      value == null ? null : DateTime.tryParse(value.toString());

  List<String> get symptomList => (symptomDescription ?? '')
      .split('\n')
      .where((e) => e.trim().isNotEmpty)
      .toList();

  List<String> get preventionList =>
      (prevention ?? '').split('\n').where((e) => e.trim().isNotEmpty).toList();
}
