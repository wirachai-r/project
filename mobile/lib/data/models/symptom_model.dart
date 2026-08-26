class SymptomCategoryModel {
  final String symptomCategoryId;
  final String categoryName;
  final String? categoryNameEn;
  final String? description;
  final String? icon;
  final String status;

  const SymptomCategoryModel({
    required this.symptomCategoryId,
    required this.categoryName,
    this.categoryNameEn,
    this.description,
    this.icon,
    required this.status,
  });

  factory SymptomCategoryModel.fromJson(Map<String, dynamic> json) =>
      SymptomCategoryModel(
        symptomCategoryId: json['symptom_category_id'],
        categoryName: json['category_name'],
        categoryNameEn: json['category_name_en'],
        description: json['description'],
        icon: json['icon'],
        status: json['status'] ?? '1',
      );
}

class SymptomModel {
  final String symptomId;
  final String symptomName;
  final String? symptomNameEn;
  final String? description;
  final String? symptomImage;
  final String status;
  final String symptomCategoryId;
  final SymptomCategoryModel? category;

  const SymptomModel({
    required this.symptomId,
    required this.symptomName,
    this.symptomNameEn,
    this.description,
    this.symptomImage,
    required this.status,
    required this.symptomCategoryId,
    this.category,
  });

  factory SymptomModel.fromJson(Map<String, dynamic> json) => SymptomModel(
    symptomId: json['symptom_id'],
    symptomName: json['symptom_name'],
    symptomNameEn: json['symptom_name_en'],
    description: json['description'],
    symptomImage: json['symptom_image'],
    status: json['status'] ?? '1',
    symptomCategoryId: json['symptom_category_id'],
    category: json['category'] != null
        ? SymptomCategoryModel.fromJson(json['category'])
        : null,
  );
}
