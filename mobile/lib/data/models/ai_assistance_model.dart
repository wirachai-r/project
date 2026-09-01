class AiClarificationChoice {
  final String id;
  final String label;
  final String mapsTo;
  final String? mapsToChoiceId;

  const AiClarificationChoice({
    required this.id,
    required this.label,
    required this.mapsTo,
    this.mapsToChoiceId,
  });

  factory AiClarificationChoice.fromJson(Map<String, dynamic> json) =>
      AiClarificationChoice(
        id: json['id'].toString(),
        label: json['label'].toString(),
        mapsTo: json['maps_to'].toString(),
        mapsToChoiceId: json['maps_to_choice_id']?.toString(),
      );
}

class AiQuestionClarification {
  final int sessionId;
  final int questionId;
  final int attempt;
  final int maxAttempts;
  final String questionText;
  final String explanation;
  final List<AiClarificationChoice> choices;
  final List<AiClarificationHistoryEntry> history;

  const AiQuestionClarification({
    required this.sessionId,
    required this.questionId,
    required this.attempt,
    required this.maxAttempts,
    required this.questionText,
    required this.explanation,
    required this.choices,
    required this.history,
  });

  factory AiQuestionClarification.fromJson(Map<String, dynamic> json) =>
      AiQuestionClarification(
        sessionId: int.parse(json['session_id'].toString()),
        questionId: int.parse(json['question_id'].toString()),
        attempt: int.parse(json['attempt'].toString()),
        maxAttempts: int.parse(json['max_attempts'].toString()),
        questionText: json['question_text'].toString(),
        explanation: json['explanation'].toString(),
        choices: (json['choices'] as List? ?? const [])
            .map(
              (item) => AiClarificationChoice.fromJson(
                Map<String, dynamic>.from(item),
              ),
            )
            .toList(),
        history: (json['history'] as List? ?? const [])
            .map(
              (item) => AiClarificationHistoryEntry.fromJson(
                Map<String, dynamic>.from(item),
              ),
            )
            .toList(),
      );
}

class AiClarificationAnswerResult {
  final String status;
  final String mapsTo;
  final String? mapsToChoiceId;
  final int attempt;
  final bool canRetry;

  const AiClarificationAnswerResult({
    required this.status,
    required this.mapsTo,
    required this.mapsToChoiceId,
    required this.attempt,
    required this.canRetry,
  });

  factory AiClarificationAnswerResult.fromJson(Map<String, dynamic> json) =>
      AiClarificationAnswerResult(
        status: json['status'].toString(),
        mapsTo: json['maps_to'].toString(),
        mapsToChoiceId: json['maps_to_choice_id']?.toString(),
        attempt: int.parse(json['attempt'].toString()),
        canRetry: json['can_retry'] == true,
      );
}

class AiClarificationHistoryEntry {
  final int questionId;
  final String questionText;
  final String answerText;
  final String selectedChoiceId;
  final int attempt;
  final List<AiClarificationChoice> choices;

  const AiClarificationHistoryEntry({
    required this.questionId,
    required this.questionText,
    required this.answerText,
    required this.selectedChoiceId,
    required this.attempt,
    required this.choices,
  });

  factory AiClarificationHistoryEntry.fromJson(Map<String, dynamic> json) =>
      AiClarificationHistoryEntry(
        questionId: int.parse(json['question_id'].toString()),
        questionText: json['question_text'].toString(),
        answerText: json['answer_text'].toString(),
        selectedChoiceId: json['selected_choice_id'].toString(),
        attempt: int.parse(json['attempt'].toString()),
        choices: (json['choices'] as List? ?? const [])
            .map(
              (item) => AiClarificationChoice.fromJson(
                Map<String, dynamic>.from(item),
              ),
            )
            .toList(),
      );
}

class AiGuidance {
  final String summary;
  final List<String> assessmentOverview;
  final List<String> selfCare;
  final List<String> warningSigns;
  final List<({String action, String label})> nextSteps;
  final String disclaimer;
  final bool cached;
  final DateTime? generatedAt;

  const AiGuidance({
    required this.summary,
    required this.assessmentOverview,
    required this.selfCare,
    required this.warningSigns,
    required this.nextSteps,
    required this.disclaimer,
    required this.cached,
    this.generatedAt,
  });

  factory AiGuidance.fromJson(Map<String, dynamic> json) => AiGuidance(
    summary: json['summary'].toString(),
    assessmentOverview: (json['assessment_overview'] as List? ?? const [])
        .map((item) => item.toString())
        .toList(),
    selfCare: (json['self_care'] as List? ?? const [])
        .map((item) => item.toString())
        .toList(),
    warningSigns: (json['warning_signs'] as List? ?? const [])
        .map((item) => item.toString())
        .toList(),
    nextSteps: (json['next_steps'] as List? ?? const []).map((item) {
      final value = Map<String, dynamic>.from(item);
      return (
        action: value['action'].toString(),
        label: value['label'].toString(),
      );
    }).toList(),
    disclaimer: json['disclaimer'].toString(),
    cached: json['cached'] == true,
    generatedAt: DateTime.tryParse(json['generated_at']?.toString() ?? ''),
  );
}
