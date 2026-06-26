class AnswerChoiceModel {
  final String choiceId;
  final String choiceText;
  final String? choiceTextEn;
  final String? choiceImage;
  final int order;

  const AnswerChoiceModel({
    required this.choiceId,
    required this.choiceText,
    this.choiceTextEn,
    this.choiceImage,
    required this.order,
  });

  factory AnswerChoiceModel.fromJson(Map<String, dynamic> json) => AnswerChoiceModel(
        choiceId: json['choice_id'],
        choiceText: json['choice_text'],
        choiceTextEn: json['choice_text_en'],
        choiceImage: json['choice_image'],
        order: json['order'] ?? 0,
      );
}

class QuestionBoxModel {
  final String boxId;
  final String questionText;
  final String? questionImage;
  final String questionType; // S=Single, M=Multiple
  final List<AnswerChoiceModel> choices;

  const QuestionBoxModel({
    required this.boxId,
    required this.questionText,
    this.questionImage,
    required this.questionType,
    required this.choices,
  });

  bool get isMultiple => questionType == 'M';

  factory QuestionBoxModel.fromJson(Map<String, dynamic> json) => QuestionBoxModel(
        boxId: json['box_id'],
        questionText: json['question_text'],
        questionImage: json['question_image'],
        questionType: json['question_type'] ?? 'S',
        choices: (json['choices'] as List? ?? [])
            .map((c) => AnswerChoiceModel.fromJson(c))
            .toList(),
      );
}

class AssessmentResultModel {
  final dynamic id;
  final String urgencyLevel; // R, P, Y, G, W
  final String shouldSeeDoctor;
  final String? recommendation;
  final String ruleId;
  final String diseaseId;
  final String? diseaseName;

  const AssessmentResultModel({
    required this.id,
    required this.urgencyLevel,
    required this.shouldSeeDoctor,
    this.recommendation,
    required this.ruleId,
    required this.diseaseId,
    this.diseaseName,
  });

  bool get needsDoctor => shouldSeeDoctor == 'Y';

  String get urgencyLabel => switch (urgencyLevel) {
        'R' => 'วิกฤต (แดง)',
        'P' => 'เร่งด่วน (ชมพู)',
        'Y' => 'ควรพบแพทย์ (เหลือง)',
        'G' => 'ไม่เร่งด่วน (เขียว)',
        _ => 'ปกติ (ขาว)',
      };

  factory AssessmentResultModel.fromJson(Map<String, dynamic> json) => AssessmentResultModel(
        id: json['id'],
        urgencyLevel: json['urgency_level'],
        shouldSeeDoctor: json['should_see_doctor'] ?? 'N',
        recommendation: json['recommendation'],
        ruleId: json['rule_id'],
        diseaseId: json['disease_id'],
        diseaseName: json['disease']?['disease_name'],
      );
}

class AssessmentModel {
  final dynamic id;
  final String symptomId;
  final String? symptomName;
  final String diagramId;
  final String assessmentStatus; // P=Processing, C=Completed
  final DateTime? startedAt;
  final DateTime? completedAt;
  final List<AssessmentResultModel> results;

  const AssessmentModel({
    required this.id,
    required this.symptomId,
    this.symptomName,
    required this.diagramId,
    required this.assessmentStatus,
    this.startedAt,
    this.completedAt,
    this.results = const [],
  });

  bool get isCompleted => assessmentStatus == 'C';

  factory AssessmentModel.fromJson(Map<String, dynamic> json) => AssessmentModel(
        id: json['id'],
        symptomId: json['symptom_id'],
        symptomName: json['symptom']?['symptom_name'],
        diagramId: json['diagram_id'],
        assessmentStatus: json['assessment_status'] ?? 'P',
        startedAt: json['started_at'] != null ? DateTime.parse(json['started_at']) : null,
        completedAt: json['completed_at'] != null ? DateTime.parse(json['completed_at']) : null,
        results: (json['results'] as List? ?? [])
            .map((r) => AssessmentResultModel.fromJson(r))
            .toList(),
      );
}
