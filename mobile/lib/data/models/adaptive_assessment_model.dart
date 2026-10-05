class AdaptiveQuestionModel {
  final int? questionId;
  final String symptomId;
  final String text;
  final String? detail;
  final int number;
  final String answerType;
  final List<AdaptiveQuestionOptionModel> options;

  const AdaptiveQuestionModel({
    this.questionId,
    required this.symptomId,
    required this.text,
    this.detail,
    required this.number,
    required this.answerType,
    required this.options,
  });

  factory AdaptiveQuestionModel.fromJson(Map<String, dynamic> json) =>
      AdaptiveQuestionModel(
        questionId: json['question_id'],
        symptomId: json['symptom_id'],
        text: json['text'],
        detail: json['detail'],
        number: json['number'] ?? 1,
        answerType: json['answer_type'] ?? 'yes_no_unsure',
        options: (json['options'] as List? ?? const [])
            .map(
              (item) => AdaptiveQuestionOptionModel.fromJson(
                Map<String, dynamic>.from(item),
              ),
            )
            .toList(),
      );
}

class AdaptiveQuestionOptionModel {
  final int? id;
  final String value;
  final String text;

  const AdaptiveQuestionOptionModel({
    this.id,
    required this.value,
    required this.text,
  });

  factory AdaptiveQuestionOptionModel.fromJson(Map<String, dynamic> json) =>
      AdaptiveQuestionOptionModel(
        id: json['id'],
        value: json['value'] ?? '',
        text: json['text'] ?? '',
      );
}

class AdaptiveDiseaseResultModel {
  final String? diseaseId;
  final String diseaseName;
  final int matchPercent;
  final int? supportingSymptomCount;
  final int? evaluatedSymptomCount;
  final bool meetsMinimumSupport;
  final bool hasArticle;

  const AdaptiveDiseaseResultModel({
    this.diseaseId,
    required this.diseaseName,
    required this.matchPercent,
    this.supportingSymptomCount,
    this.evaluatedSymptomCount,
    this.meetsMinimumSupport = true,
    required this.hasArticle,
  });

  int get symptomMatchPercent {
    final supporting = supportingSymptomCount;
    final evaluated = evaluatedSymptomCount;
    if (supporting != null && evaluated != null && evaluated > 0) {
      return ((supporting / evaluated) * 100)
          .round()
          .clamp(0, 100)
          .toInt();
    }
    return matchPercent.clamp(0, 100).toInt();
  }

  factory AdaptiveDiseaseResultModel.fromJson(
    Map<String, dynamic> json,
  ) => AdaptiveDiseaseResultModel(
    diseaseId: json['disease_id'],
    diseaseName: json['disease_name'] ?? '',
    matchPercent: json['match_percent'] ?? 0,
    supportingSymptomCount: (json['supporting_symptom_count'] as num?)?.round(),
    evaluatedSymptomCount: (json['evaluated_symptom_count'] as num?)?.round(),
    meetsMinimumSupport: json['meets_minimum_support'] != false,
    hasArticle: json['has_article'] == true,
  );
}
