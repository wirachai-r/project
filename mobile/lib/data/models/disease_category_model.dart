class DiseaseCategoryModel {
  final String diseaseCategoryId;
  final String categoryName;
  final String? categoryNameEn;
  final String? icon;

  DiseaseCategoryModel({
    required this.diseaseCategoryId,
    required this.categoryName,
    this.categoryNameEn,
    this.icon,
  });

  factory DiseaseCategoryModel.fromJson(Map<String, dynamic> json) {
    return DiseaseCategoryModel(
      diseaseCategoryId: json['disease_category_id'] ?? '',
      categoryName: json['category_name'] ?? '',
      categoryNameEn: json['category_name_en'],
      icon: json['icon'],
    );
  }
}
