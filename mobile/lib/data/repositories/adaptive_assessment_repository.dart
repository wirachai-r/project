import '../../core/constants/api_constants.dart';
import '../models/adaptive_assessment_model.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';

class AdaptiveAssessmentRepository {
  final ApiService _api;
  final AuthService _auth;
  AdaptiveAssessmentRepository({required ApiService api, required AuthService authService}) : _api = api, _auth = authService;

  Future<({dynamic id, dynamic historyAssessmentId, AdaptiveQuestionModel? question, List<AdaptiveDiseaseResultModel> results})> start(String symptomId) async {
    final data = await _api.post(ApiConstants.adaptiveAssessmentStart, body: {'symptom_id': symptomId});
    final token = data['session_token'] as String?;
    if (token != null) { await _auth.saveSessionToken(token); _api.setSessionToken(token); }
    return _parse(data, data['assessment_id']);
  }

  Future<({dynamic id, dynamic historyAssessmentId, AdaptiveQuestionModel? question, List<AdaptiveDiseaseResultModel> results})> answer(dynamic id, String symptomId, String answer) async {
    final data = await _api.post(ApiConstants.adaptiveAssessmentAnswer(id), body: {'symptom_id': symptomId, 'answer': answer});
    return _parse(data, id);
  }

  Future<AdaptiveQuestionModel> back(dynamic id) async {
    final data = await _api.post(ApiConstants.adaptiveAssessmentBack(id));
    return AdaptiveQuestionModel.fromJson(
      Map<String, dynamic>.from(data['question']),
    );
  }

  Future<void> abandon(dynamic id) async {
    await _api.post(ApiConstants.adaptiveAssessmentAbandon(id));
  }

  ({dynamic id, dynamic historyAssessmentId, AdaptiveQuestionModel? question, List<AdaptiveDiseaseResultModel> results}) _parse(dynamic data, dynamic id) => (
    id: id,
    historyAssessmentId: data['history_assessment_id'],
    question: data['question'] == null ? null : AdaptiveQuestionModel.fromJson(Map<String, dynamic>.from(data['question'])),
    results: (data['results'] as List? ?? const []).map((e) => AdaptiveDiseaseResultModel.fromJson(Map<String, dynamic>.from(e))).toList(),
  );
}
