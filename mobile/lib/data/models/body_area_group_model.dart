class BodyAreaGroupModel {
  final int id;
  final String name;
  final String? nameEn;
  final String? description;
  final String? imageUrl;
  final int symptomsCount;

  const BodyAreaGroupModel({
    required this.id,
    required this.name,
    this.nameEn,
    this.description,
    this.imageUrl,
    required this.symptomsCount,
  });

  factory BodyAreaGroupModel.fromJson(Map<String, dynamic> json) =>
      BodyAreaGroupModel(
        id: json['id'] as int,
        name: json['name'] as String,
        nameEn: json['name_en'] as String?,
        description: json['description'] as String?,
        imageUrl: json['image_url'] as String?,
        symptomsCount: (json['symptoms_count'] as num?)?.toInt() ?? 0,
      );
}
