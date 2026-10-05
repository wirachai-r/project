// history_model.dart

class HistoryDiseaseModel {
  final String diseaseName;
  final String description;

  const HistoryDiseaseModel({
    required this.diseaseName,
    required this.description,
  });

  factory HistoryDiseaseModel.fromJson(Map<String, dynamic> json) {
    return HistoryDiseaseModel(
      diseaseName: json['disease_name'] ?? json['name'] ?? '-',
      description:
          json['description'] ??
          json['disease_detail'] ??
          'ไม่มีข้อมูลรายละเอียดโรคนี้',
    );
  }
}

class HistoryResultModel {
  final String diseaseName;
  final String urgencyLevel; // R, P, Y, G, W
  final String? recommendation; // มีเฉพาะตอนโหลด detail
  final List<HistoryDiseaseModel> diseases; // มีเฉพาะตอนโหลด detail

  const HistoryResultModel({
    required this.diseaseName,
    required this.urgencyLevel,
    this.recommendation,
    this.diseases = const [],
  });

  factory HistoryResultModel.fromJson(Map<String, dynamic> json) {
    String extractedDiseaseName = '-';
    List<HistoryDiseaseModel> parsedDiseases = [];

    if (json['diseases'] is List) {
      var dList = json['diseases'] as List;
      parsedDiseases = dList
          .map(
            (d) => HistoryDiseaseModel.fromJson(Map<String, dynamic>.from(d)),
          )
          .toList();

      if (parsedDiseases.isNotEmpty) {
        extractedDiseaseName = parsedDiseases
            .map((d) => d.diseaseName)
            .join(', ');
      }
    } else if (json['disease'] is Map) {
      extractedDiseaseName = json['disease']['disease_name'] ?? '-';
      parsedDiseases = [
        HistoryDiseaseModel.fromJson(
          Map<String, dynamic>.from(json['disease']),
        ),
      ];
    } else if (json['disease_name'] != null) {
      extractedDiseaseName = json['disease_name'];
    }

    return HistoryResultModel(
      diseaseName: extractedDiseaseName,
      urgencyLevel: json['urgency_level']?.toString() ?? 'W',
      recommendation:
          json['recommendation']?.toString() ?? json['note']?.toString(),
      diseases: parsedDiseases,
    );
  }
}

class HistoryAnswerModel {
  final String choiceText;

  const HistoryAnswerModel({required this.choiceText});

  factory HistoryAnswerModel.fromJson(Map<String, dynamic> json) {
    return HistoryAnswerModel(
      choiceText: json['choice'] is Map
          ? (json['choice']['choice_text'] ?? '-')
          : (json['choice_text'] ?? json['question_text'] ?? '-'),
    );
  }
}

class HistoryItemModel {
  final dynamic id;
  final String symptomName;
  final String? symptomIcon;
  final String assessmentType;
  final String?
  assessmentStatus; // P=Processing, C=Completed (null เมื่อ endpoint ไม่ส่งมา)
  final String createdAt;
  final List<HistoryResultModel> results;
  final List<HistoryAnswerModel> answers; // ว่างเมื่อโหลดจากหน้า list

  const HistoryItemModel({
    required this.id,
    required this.symptomName,
    this.symptomIcon,
    this.assessmentType = 'classic',
    this.assessmentStatus,
    required this.createdAt,
    this.results = const [],
    this.answers = const [],
  });

  HistoryResultModel? get topResult =>
      results.isNotEmpty ? results.first : null;

  bool get isCompleted => assessmentStatus == 'C' || assessmentStatus == null;

  factory HistoryItemModel.fromJson(Map<String, dynamic> json) {
    // รองรับกรณีถูกครอบด้วย 'data' (จาก detail endpoint)
    final Map<String, dynamic> data = json['data'] is Map ? json['data'] : json;

    var resultsList = data['results'] as List? ?? [];
    List<HistoryResultModel> parsedResults = resultsList
        .map((r) => HistoryResultModel.fromJson(Map<String, dynamic>.from(r)))
        .toList();

    var answersList = data['answers'] as List? ?? [];
    List<HistoryAnswerModel> parsedAnswers = answersList
        .map((a) => HistoryAnswerModel.fromJson(Map<String, dynamic>.from(a)))
        .toList();

    String extractedSymptom = '-';
    String? extractedSymptomIcon;
    if (data['symptom'] is Map) {
      extractedSymptom = data['symptom']['symptom_name'] ?? '-';
      extractedSymptomIcon = data['symptom']['symptom_image']?.toString();
    } else if (data['symptom_name'] != null) {
      extractedSymptom = data['symptom_name'];
      extractedSymptomIcon = data['symptom_image']?.toString();
    }

    return HistoryItemModel(
      id: data['id'] ?? data['assessment_id'],
      symptomName: extractedSymptom,
      symptomIcon: extractedSymptomIcon,
      assessmentType: data['assessment_type']?.toString() ?? 'classic',
      assessmentStatus: data['assessment_status'],
      createdAt:
          data['completed_at']?.toString() ??
          data['created_at']?.toString() ??
          '',
      results: parsedResults,
      answers: parsedAnswers,
    );
  }
}
