class AdaptiveQuestionModel {
  final String symptomId;
  final String text;
  final String? detail;
  final int number;

  const AdaptiveQuestionModel({required this.symptomId, required this.text, this.detail, required this.number});

  factory AdaptiveQuestionModel.fromJson(Map<String, dynamic> json) => AdaptiveQuestionModel(
    symptomId: json['symptom_id'], text: json['text'], detail: json['detail'],
    number: json['number'] ?? 1,
  );
}

class AdaptiveDiseaseResultModel {
  final String? diseaseId;
  final String diseaseName;
  final int matchPercent;
  final bool hasArticle;

  const AdaptiveDiseaseResultModel({this.diseaseId, required this.diseaseName, required this.matchPercent, required this.hasArticle});

  factory AdaptiveDiseaseResultModel.fromJson(Map<String, dynamic> json) => AdaptiveDiseaseResultModel(
    diseaseId: json['disease_id'], diseaseName: json['disease_name'] ?? '',
    matchPercent: json['match_percent'] ?? 0, hasArticle: json['has_article'] == true,
  );
}
