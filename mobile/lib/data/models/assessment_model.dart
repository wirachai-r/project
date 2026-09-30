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

  factory AnswerChoiceModel.fromJson(Map<String, dynamic> json) =>
      AnswerChoiceModel(
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
  final String? detail;
  final String questionType; // S=Single, M=Multiple
  final int? minRequired;
  final List<AnswerChoiceModel> choices;

  const QuestionBoxModel({
    required this.boxId,
    required this.questionText,
    this.questionImage,
    this.detail,
    required this.questionType,
    this.minRequired,
    required this.choices,
  });

  bool get isMultiple => questionType == 'M';

  factory QuestionBoxModel.fromJson(Map<String, dynamic> json) =>
      QuestionBoxModel(
        boxId: json['box_id'],
        questionText: json['question_text'],
        questionImage: json['question_image'],
        detail: json['detail'],
        questionType: json['question_type'] ?? 'S',
        minRequired: json['min_required'] as int?,
        choices: (json['choices'] as List? ?? [])
            .map((c) => AnswerChoiceModel.fromJson(c))
            .toList(),
      );
}

class DiseaseModel {
  final String diseaseId;
  final String diseaseName;
  final String? diseaseNameEn;
  final int order;
  final int? matchPercent;
  final int? supportingSymptomCount;
  final int? evaluatedSymptomCount;
  final bool meetsMinimumSupport;

  // ข้อมูลโรคแบบละเอียด ส่งมาพร้อมกับผลการประเมินแล้ว (ไม่ต้องเรียก API ซ้ำ)
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
  final String? reference;

  const DiseaseModel({
    required this.diseaseId,
    required this.diseaseName,
    this.diseaseNameEn,
    this.order = 0,
    this.matchPercent,
    this.supportingSymptomCount,
    this.evaluatedSymptomCount,
    this.meetsMinimumSupport = true,
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
    this.reference,
  });

  // มีอย่างน้อย 1 field รายละเอียดให้แสดง ใช้ตัดสินใจว่าจะโชว์ปุ่ม "ขยาย" ไหม
  bool get hasDetail =>
      (description?.isNotEmpty ?? false) ||
      (cause?.isNotEmpty ?? false) ||
      (symptomDescription?.isNotEmpty ?? false) ||
      (complications?.isNotEmpty ?? false) ||
      (diagnosis?.isNotEmpty ?? false) ||
      (medicalTreatment?.isNotEmpty ?? false) ||
      (selfCare?.isNotEmpty ?? false) ||
      (whenToSeeDoctor?.isNotEmpty ?? false) ||
      (prevention?.isNotEmpty ?? false) ||
      (recommendations?.isNotEmpty ?? false);

  factory DiseaseModel.fromJson(Map<String, dynamic> json) => DiseaseModel(
    diseaseId: json['disease_id'],
    diseaseName: json['disease_name'] ?? '',
    diseaseNameEn: json['disease_name_en'],
    order: json['order'] ?? 0,
    matchPercent: (json['match_percent'] as num?)?.round(),
    supportingSymptomCount: (json['supporting_symptom_count'] as num?)?.round(),
    evaluatedSymptomCount: (json['evaluated_symptom_count'] as num?)?.round(),
    meetsMinimumSupport: json['meets_minimum_support'] != false,
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
    reference: json['reference'],
  );
}

class AssessmentResultModel {
  final dynamic id;
  final String urgencyLevel; // R, P, Y, G, W
  final String shouldSeeDoctor;
  final String? recommendation;
  final String? timeFrame;
  final String? timeFrameEn;
  final String? medicalReference;
  final String? ruleId;
  final List<DiseaseModel> diseases;
  final List<NextDiagramModel> nextDiagrams;

  const AssessmentResultModel({
    required this.id,
    required this.urgencyLevel,
    required this.shouldSeeDoctor,
    this.recommendation,
    this.timeFrame,
    this.timeFrameEn,
    this.medicalReference,
    this.ruleId,
    this.diseases = const [],
    this.nextDiagrams = const [],
  });

  bool get needsDoctor => shouldSeeDoctor == 'Y';

  String get diseaseNamesText => diseases.map((d) => d.diseaseName).join(', ');

  String get urgencyLabel => switch (urgencyLevel) {
    'R' => 'วิกฤต (แดง)',
    'P' => 'เร่งด่วน (ชมพู)',
    'Y' => 'ควรพบแพทย์ (เหลือง)',
    'G' => 'ไม่เร่งด่วน (เขียว)',
    _ => 'ปกติ (ขาว)',
  };

  factory AssessmentResultModel.fromJson(Map<String, dynamic> json) =>
      AssessmentResultModel(
        id: json['id'],
        urgencyLevel: json['urgency_level'],
        shouldSeeDoctor: json['should_see_doctor'] ?? 'N',
        recommendation: json['recommendation'],
        timeFrame: json['time_frame'],
        timeFrameEn: json['time_frame_en'],
        medicalReference: json['medical_reference'],
        ruleId: json['rule_id']?.toString(),
        diseases: (json['diseases'] as List? ?? [])
            .map((d) => DiseaseModel.fromJson(d))
            .toList(),
        nextDiagrams: (json['next_diagrams'] as List? ?? [])
            .map((d) => NextDiagramModel.fromJson(d))
            .toList(),
      );
}

class NextDiagramModel {
  final String diagramId;
  final String diagramName;
  final String? diagramNameEn;
  final String? promptText;
  final String? targetBoxId;
  final int order;

  const NextDiagramModel({
    required this.diagramId,
    required this.diagramName,
    this.diagramNameEn,
    this.promptText,
    this.targetBoxId,
    this.order = 0,
  });

  factory NextDiagramModel.fromJson(Map<String, dynamic> json) =>
      NextDiagramModel(
        diagramId: json['diagram_id'],
        diagramName: json['diagram_name'] ?? '',
        diagramNameEn: json['diagram_name_en'],
        promptText: json['prompt_text'],
        targetBoxId: json['target_box_id'],
        order: json['order'] ?? 0,
      );
}

class AssessmentModel {
  final dynamic id;
  final String symptomId;
  final String? symptomName;
  final String? diagramId;
  final String assessmentStatus; // P=Processing, C=Completed
  final String assessmentType;
  final DateTime? startedAt;
  final DateTime? completedAt;
  final List<AssessmentResultModel> results;
  final Map<String, dynamic>? healthEpisode;

  const AssessmentModel({
    required this.id,
    required this.symptomId,
    this.symptomName,
    required this.diagramId,
    required this.assessmentStatus,
    this.assessmentType = 'classic',
    this.startedAt,
    this.completedAt,
    this.results = const [],
    this.healthEpisode,
  });

  bool get isCompleted => assessmentStatus == 'C';

  factory AssessmentModel.fromJson(Map<String, dynamic> json) =>
      AssessmentModel(
        id: json['id'],
        symptomId: json['symptom_id'],
        symptomName: json['symptom']?['symptom_name'],
        diagramId: json['diagram_id'],
        assessmentStatus: json['assessment_status'] ?? 'P',
        assessmentType: json['assessment_type'] ?? 'classic',
        startedAt: json['started_at'] != null
            ? DateTime.parse(json['started_at'])
            : null,
        completedAt: json['completed_at'] != null
            ? DateTime.parse(json['completed_at'])
            : null,
        results: (json['results'] as List? ?? [])
            .map((r) => AssessmentResultModel.fromJson(r))
            .toList(),
        healthEpisode: json['health_episode'] is Map
            ? Map<String, dynamic>.from(json['health_episode'])
            : null,
      );
}

class PendingAssessmentModel {
  final dynamic assessmentId;
  final String symptomId;
  final String? symptomName;
  final String diagramId;
  final DateTime? startedAt;
  final QuestionBoxModel currentBox;
  final List<String> selectedChoiceIds;
  final List<PendingAnsweredBoxModel> answeredBoxes;

  const PendingAssessmentModel({
    required this.assessmentId,
    required this.symptomId,
    this.symptomName,
    required this.diagramId,
    required this.currentBox,
    this.startedAt,
    this.selectedChoiceIds = const [],
    this.answeredBoxes = const [],
  });

  factory PendingAssessmentModel.fromJson(
    Map<String, dynamic> json,
  ) => PendingAssessmentModel(
    assessmentId: json['assessment_id'],
    symptomId: json['symptom_id'] as String,
    symptomName: json['symptom_name'] as String?,
    diagramId: json['diagram_id'] as String,
    startedAt: json['started_at'] != null
        ? DateTime.parse(json['started_at'])
        : null,
    currentBox: QuestionBoxModel.fromJson(json['current_box']),
    selectedChoiceIds: (json['selected_choice_ids'] as List? ?? [])
        .map((id) => id.toString())
        .toList(),
    answeredBoxes: (json['answered_boxes'] as List? ?? const [])
        .map(
          (item) =>
              PendingAnsweredBoxModel.fromJson(Map<String, dynamic>.from(item)),
        )
        .toList(),
  );
}

class PendingAnsweredBoxModel {
  final QuestionBoxModel box;
  final List<String> selectedChoiceIds;

  const PendingAnsweredBoxModel({
    required this.box,
    this.selectedChoiceIds = const [],
  });

  factory PendingAnsweredBoxModel.fromJson(Map<String, dynamic> json) =>
      PendingAnsweredBoxModel(
        box: QuestionBoxModel.fromJson(Map<String, dynamic>.from(json['box'])),
        selectedChoiceIds: (json['selected_choice_ids'] as List? ?? const [])
            .map((id) => id.toString())
            .toList(),
      );
}
