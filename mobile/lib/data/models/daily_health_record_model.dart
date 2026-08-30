import 'symptom_model.dart';

class DailyHealthRecordModel {
  final dynamic id;
  final DateTime recordedOn;
  final String status;
  final String? note;
  final List<SymptomModel> symptoms;

  const DailyHealthRecordModel({
    required this.id,
    required this.recordedOn,
    required this.status,
    this.note,
    this.symptoms = const [],
  });

  factory DailyHealthRecordModel.fromJson(Map<String, dynamic> json) =>
      DailyHealthRecordModel(
        id: json['id'],
        recordedOn: DateTime.parse(json['recorded_on'] as String),
        status: json['status'] as String,
        note: json['note'] as String?,
        symptoms: (json['symptoms'] as List<dynamic>? ?? const [])
            .map(
              (item) => SymptomModel.fromJson(
                Map<String, dynamic>.from(item as Map),
              ),
            )
            .toList(),
      );
}
