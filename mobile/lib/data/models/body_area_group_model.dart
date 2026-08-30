class BodyAreaGroupModel {
  final int id;
  final String name;
  final String? nameEn;
  final String? description;
  final String? imageUrl;
  final int symptomsCount;
  final List<BodyAreaSubgroupModel> subgroups;

  const BodyAreaGroupModel({
    required this.id,
    required this.name,
    this.nameEn,
    this.description,
    this.imageUrl,
    required this.symptomsCount,
    this.subgroups = const [],
  });

  factory BodyAreaGroupModel.fromJson(Map<String, dynamic> json) =>
      BodyAreaGroupModel(
        id: json['id'] as int,
        name: json['name'] as String,
        nameEn: json['name_en'] as String?,
        description: json['description'] as String?,
        imageUrl: json['image_url'] as String?,
        symptomsCount: (json['symptoms_count'] as num?)?.toInt() ?? 0,
        subgroups: (json['subgroups'] as List? ?? [])
            .map((item) => BodyAreaSubgroupModel.fromJson(item as Map<String, dynamic>))
            .toList(),
      );
}

class BodyAreaSubgroupModel {
  final int id;
  final String name;
  final String? nameEn;
  final String? description;
  final String? imageUrl;
  final int symptomsCount;

  const BodyAreaSubgroupModel({
    required this.id,
    required this.name,
    this.nameEn,
    this.description,
    this.imageUrl,
    required this.symptomsCount,
  });

  factory BodyAreaSubgroupModel.fromJson(Map<String, dynamic> json) =>
      BodyAreaSubgroupModel(
        id: json['id'] as int,
        name: json['name'] as String,
        nameEn: json['name_en'] as String?,
        description: json['description'] as String?,
        imageUrl: json['image_url'] as String?,
        symptomsCount: (json['symptoms_count'] as num?)?.toInt() ?? 0,
      );
}
