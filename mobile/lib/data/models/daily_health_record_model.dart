class DailyHealthRecordModel {
  final dynamic id;
  final DateTime recordedOn;
  final String status;
  final String? note;

  const DailyHealthRecordModel({
    required this.id,
    required this.recordedOn,
    required this.status,
    this.note,
  });

  factory DailyHealthRecordModel.fromJson(Map<String, dynamic> json) =>
      DailyHealthRecordModel(
        id: json['id'],
        recordedOn: DateTime.parse(json['recorded_on'] as String),
        status: json['status'] as String,
        note: json['note'] as String?,
      );
}
