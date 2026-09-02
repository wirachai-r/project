import 'symptom_model.dart';

class DailyHealthRecordModel {
  final dynamic id;
  final DateTime recordedOn;
  final DateTime? recordedAt;
  final String status;
  final String? note;
  final List<SymptomModel> symptoms;
  final List<DailyHealthEpisodeLinkModel> healthEpisodes;

  const DailyHealthRecordModel({
    required this.id,
    required this.recordedOn,
    this.recordedAt,
    required this.status,
    this.note,
    this.symptoms = const [],
    this.healthEpisodes = const [],
  });

  factory DailyHealthRecordModel.fromJson(Map<String, dynamic> json) =>
      DailyHealthRecordModel(
        id: json['id'],
        recordedOn: DateTime.parse(json['recorded_on'] as String),
        recordedAt: DateTime.tryParse(
          json['recorded_at']?.toString() ?? '',
        )?.toLocal(),
        status: json['status'] as String,
        note: json['note'] as String?,
        symptoms: (json['symptoms'] as List<dynamic>? ?? const [])
            .map(
              (item) =>
                  SymptomModel.fromJson(Map<String, dynamic>.from(item as Map)),
            )
            .toList(),
        healthEpisodes: (json['health_episodes'] as List<dynamic>? ?? const [])
            .map(
              (item) => DailyHealthEpisodeLinkModel.fromJson(
                Map<String, dynamic>.from(item as Map),
              ),
            )
            .toList(),
      );
}

class DailyHealthEpisodeLinkModel {
  final dynamic id;
  final String status;
  final List<String> symptomNames;

  const DailyHealthEpisodeLinkModel({
    required this.id,
    required this.status,
    required this.symptomNames,
  });

  factory DailyHealthEpisodeLinkModel.fromJson(Map<String, dynamic> json) =>
      DailyHealthEpisodeLinkModel(
        id: json['id'],
        status: json['status']?.toString() ?? 'A',
        symptomNames: List<String>.from(json['symptom_names'] ?? const []),
      );
}
