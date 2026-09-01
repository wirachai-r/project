import '../services/api_service.dart';
import '../models/assessment_model.dart';
import '../../core/constants/api_constants.dart';
import '../services/auth_service.dart';
import '../models/ai_assistance_model.dart';

class AssessmentRepository {
  final ApiService _api;
  final AuthService _authService;

  AssessmentRepository({
    required ApiService api,
    required AuthService authService,
  }) : _api = api,
       _authService = authService;

  /// เริ่ม assessment — คืน assessment_id + first_box
  Future<({dynamic assessmentId, String diagramId, QuestionBoxModel firstBox})>
  start({required String symptomId, String? diagramId}) async {
    final data = await _api.post(
      ApiConstants.assessmentStart,
      body: {
        'symptom_id': symptomId,
        if (diagramId != null) 'diagram_id': diagramId,
      },
    );

    final sessionToken = data['session_token'] as String?;
    if (sessionToken != null) {
      await _authService.saveSessionToken(sessionToken);
      _api.setSessionToken(sessionToken);
    }

    return (
      assessmentId: data['assessment_id'],
      diagramId: data['diagram_id'] as String,
      firstBox: QuestionBoxModel.fromJson(data['first_box']),
    );
  }

  /// ส่งคำตอบ — คืน next_box หรือ results ถ้าจบ
  Future<
    ({
      String status,
      QuestionBoxModel? nextBox,
      List<AssessmentResultModel>? results,
    })
  >
  answer({
    required dynamic assessmentId,
    required List<({String boxId, String choiceId})> answers,
    String? boxId,
    bool noneSelected = false,
  }) async {
    final data = await _api.post(
      ApiConstants.assessmentAnswer(assessmentId),
      body: {
        'answers': answers
            .map((a) => {'box_id': a.boxId, 'choice_id': a.choiceId})
            .toList(),
        if (boxId != null) 'box_id': boxId,
        if (noneSelected) 'none_selected': true,
      },
    );

    final status = data['status'] as String;

    return (
      status: status,
      nextBox: data['next_box'] != null
          ? QuestionBoxModel.fromJson(data['next_box'])
          : null,
      results: status == 'completed'
          ? (data['results'] as List)
                .map((r) => AssessmentResultModel.fromJson(r))
                .toList()
          : null,
    );
  }

  Future<({dynamic assessmentId, String diagramId, QuestionBoxModel firstBox})>
  continueAssessment({
    required dynamic assessmentId,
    required String diagramId,
    String? targetBoxId,
  }) async {
    final data = await _api.post(
      ApiConstants.assessmentContinue(assessmentId),
      body: {
        'diagram_id': diagramId,
        if (targetBoxId != null) 'target_box_id': targetBoxId,
      },
    );

    return (
      assessmentId: data['assessment_id'],
      diagramId: data['diagram_id'] as String,
      firstBox: QuestionBoxModel.fromJson(data['first_box']),
    );
  }

  Future<
    ({
      dynamic assessmentId,
      DateTime completedAt,
      List<AssessmentResultModel> results,
    })
  >
  getResult(dynamic assessmentId) async {
    final data = await _api.get(ApiConstants.assessmentResult(assessmentId));
    return (
      assessmentId: data['assessment_id'],
      completedAt: DateTime.parse(data['completed_at']),
      results: (data['results'] as List)
          .map((r) => AssessmentResultModel.fromJson(r))
          .toList(),
    );
  }

  Future<void> saveResult(dynamic assessmentId) async {
    await _api.post(ApiConstants.assessmentSave(assessmentId));
  }

  Future<List<AssessmentModel>> getHistory() async {
    final data = await _api.get(ApiConstants.assessmentHistory);
    final list = data['data'] as List? ?? data as List;
    return list.map((e) => AssessmentModel.fromJson(e)).toList();
  }

  Future<AssessmentModel> getDetail(dynamic assessmentId) async {
    final data = await _api.get(ApiConstants.assessmentDetail(assessmentId));
    return AssessmentModel.fromJson(data['data'] ?? data);
  }

  Future<AiQuestionClarification> clarifyQuestion({
    required dynamic assessmentId,
    required String boxId,
  }) async {
    final response = await _api.post(
      ApiConstants.aiClarifyQuestion(assessmentId),
      body: {'box_id': boxId},
    );
    return AiQuestionClarification.fromJson(
      Map<String, dynamic>.from(response['data']),
    );
  }

  Future<AiClarificationAnswerResult> answerClarificationQuestion({
    required int questionId,
    required int choiceId,
  }) async {
    final response = await _api.post(
      ApiConstants.aiAnswerClarificationQuestion(questionId),
      body: {'choice_id': choiceId},
    );
    return AiClarificationAnswerResult.fromJson(
      Map<String, dynamic>.from(response['data']),
    );
  }

  Future<void> markClarificationUnresolved(int sessionId) async {
    await _api.post(ApiConstants.aiMarkClarificationUnresolved(sessionId));
  }

  Future<AiGuidance> getAiGuidance(dynamic assessmentId) async {
    final response = await _api.post(
      ApiConstants.aiAssessmentGuidance(assessmentId),
    );
    return AiGuidance.fromJson(Map<String, dynamic>.from(response['data']));
  }
}
