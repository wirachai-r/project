class FollowUpEntryModel {
  final dynamic id;
  final int? severity;
  final double? temperature;
  final String? note;
  final DateTime recordedAt;
  final Map<int, dynamic> answers;

  const FollowUpEntryModel({
    required this.id,
    required this.severity,
    this.temperature,
    this.note,
    required this.recordedAt,
    this.answers = const {},
  });

  factory FollowUpEntryModel.fromJson(Map<String, dynamic> json) =>
      FollowUpEntryModel(
        id: json['id'],
        severity: (json['severity'] as num?)?.toInt(),
        temperature: (json['temperature'] as num?)?.toDouble(),
        note: json['note']?.toString(),
        recordedAt: DateTime.parse(json['recorded_at'].toString()),
        answers: {
          for (final answer in (json['answers'] as List? ?? const []))
            if (answer is Map && answer['question_template_id'] != null)
              int.parse(
                answer['question_template_id'].toString(),
              ): answer['answer_value'] is Map
                  ? answer['answer_value']['value']
                  : answer['answer_value'],
        },
      );
}

class FollowUpQuestionModel {
  final int id;
  final String questionText;
  final String? description;
  final String answerType;
  final List<String> options;
  final String? unit;
  final bool isRequired;
  final bool isGlobal;

  const FollowUpQuestionModel({
    required this.id,
    required this.questionText,
    this.description,
    required this.answerType,
    this.options = const [],
    this.unit,
    required this.isRequired,
    this.isGlobal = false,
  });

  factory FollowUpQuestionModel.fromJson(Map<String, dynamic> json) =>
      FollowUpQuestionModel(
        id: int.parse(json['id'].toString()),
        questionText: json['question_text'].toString(),
        description: json['description']?.toString(),
        answerType: json['answer_type'].toString(),
        options: (json['options'] as List? ?? const [])
            .map((item) => item.toString())
            .toList(),
        unit: json['unit']?.toString(),
        isRequired: json['is_required'] == true || json['is_required'] == 1,
        isGlobal: json['is_global'] == true || json['is_global'] == 1,
      );
}

class EpisodeSymptomModel {
  final dynamic id;
  final String? symptomId;
  final String symptomName;
  final bool isPrimary;
  final String status;
  final List<FollowUpEntryModel> entries;
  final List<FollowUpQuestionModel> questions;

  const EpisodeSymptomModel({
    required this.id,
    this.symptomId,
    required this.symptomName,
    required this.isPrimary,
    required this.status,
    this.entries = const [],
    this.questions = const [],
  });

  factory EpisodeSymptomModel.fromJson(Map<String, dynamic> json) =>
      EpisodeSymptomModel(
        id: json['id'],
        symptomId: json['symptom_id']?.toString(),
        symptomName: json['symptom_name']?.toString() ?? 'ไม่ระบุอาการ',
        isPrimary: json['is_primary'] == true || json['is_primary'] == 1,
        status: json['status']?.toString() ?? 'A',
        entries: (json['entries'] as List? ?? const [])
            .map(
              (item) =>
                  FollowUpEntryModel.fromJson(Map<String, dynamic>.from(item)),
            )
            .toList(),
        questions: (json['questions'] as List? ?? const [])
            .map(
              (item) => FollowUpQuestionModel.fromJson(
                Map<String, dynamic>.from(item),
              ),
            )
            .toList(),
      );
}

class HealthEpisodeModel {
  final dynamic id;
  final dynamic sourceAssessmentId;
  final String status;
  final DateTime startedAt;
  final List<EpisodeSymptomModel> symptoms;
  final DateTime? endedAt;
  final String? endReason;
  final String? endNote;
  final List<HealthEpisodeAssessmentModel> assessments;

  const HealthEpisodeModel({
    required this.id,
    this.sourceAssessmentId,
    required this.status,
    required this.startedAt,
    required this.symptoms,
    this.endedAt,
    this.endReason,
    this.endNote,
    this.assessments = const [],
  });

  factory HealthEpisodeModel.fromJson(Map<String, dynamic> json) =>
      HealthEpisodeModel(
        id: json['id'],
        sourceAssessmentId: json['source_assessment_id'],
        status: json['status']?.toString() ?? 'A',
        startedAt: DateTime.parse(json['started_at'].toString()),
        endedAt: DateTime.tryParse(json['ended_at']?.toString() ?? ''),
        endReason: json['end_reason']?.toString(),
        endNote: json['end_note']?.toString(),
        assessments: (json['assessments'] as List? ?? const [])
            .map(
              (item) => HealthEpisodeAssessmentModel.fromJson(
                Map<String, dynamic>.from(item),
              ),
            )
            .toList(),
        symptoms: (json['symptoms'] as List? ?? const [])
            .map(
              (item) =>
                  EpisodeSymptomModel.fromJson(Map<String, dynamic>.from(item)),
            )
            .toList(),
      );
}

class HealthEpisodeAssessmentModel {
  final dynamic id;
  final String symptomName;
  final DateTime? completedAt;
  final String relationshipType;

  const HealthEpisodeAssessmentModel({
    required this.id,
    required this.symptomName,
    this.completedAt,
    required this.relationshipType,
  });

  factory HealthEpisodeAssessmentModel.fromJson(Map<String, dynamic> json) =>
      HealthEpisodeAssessmentModel(
        id: json['id'],
        symptomName: json['symptom_name']?.toString() ?? 'ไม่ระบุอาการ',
        completedAt: DateTime.tryParse(json['completed_at']?.toString() ?? ''),
        relationshipType: json['relationship_type']?.toString() ?? 'related',
      );
}
