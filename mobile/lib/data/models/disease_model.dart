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

  List<String> get symptomList => (symptomDescription ?? '')
      .split('\n')
      .where((e) => e.trim().isNotEmpty)
      .toList();

  List<String> get preventionList =>
      (prevention ?? '').split('\n').where((e) => e.trim().isNotEmpty).toList();
}
