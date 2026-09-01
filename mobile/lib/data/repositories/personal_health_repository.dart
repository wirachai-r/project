import '../../core/constants/api_constants.dart';
import '../services/api_service.dart';
import '../models/daily_health_record_model.dart';
import '../models/health_episode_model.dart';

class PersonalHealthRepository {
  final ApiService api;
  PersonalHealthRepository({required this.api});

  Future<Map<String, dynamic>> dashboard({
    int days = 30,
    String? from,
    String? to,
  }) async => Map<String, dynamic>.from(
    await api.get(
      ApiConstants.healthDashboard,
      params: {
        if (from != null && to != null) ...{
          'from': from,
          'to': to,
        } else
          'days': days,
      },
    ),
  );

  Future<List<DailyHealthRecordModel>> dailyRecords({
    required String from,
    required String to,
  }) async {
    final json = await api.get(
      ApiConstants.dailyHealthRecords,
      params: {'from': from, 'to': to},
    );
    return List<Map<String, dynamic>>.from(
      json['data'] ?? const [],
    ).map(DailyHealthRecordModel.fromJson).toList();
  }

  Future<DailyHealthRecordModel> saveDailyRecord({
    required String recordedOn,
    required String status,
    String? note,
    List<String> symptomIds = const [],
  }) async {
    final json = await api.post(
      ApiConstants.dailyHealthRecords,
      body: {
        'recorded_on': recordedOn,
        'status': status,
        'symptom_ids': status == 'unwell' ? symptomIds : <String>[],
        if (note?.trim().isNotEmpty == true) 'note': note!.trim(),
      },
    );
    return DailyHealthRecordModel.fromJson(
      Map<String, dynamic>.from(json['data']),
    );
  }

  Future<List<dynamic>> bookmarks() async {
    final json = await api.get(ApiConstants.bookmarks);
    return List<dynamic>.from(json['data'] ?? []);
  }

  Future<void> addBookmark(String type, String id) async {
    await api.post(
      ApiConstants.bookmarks,
      body: {'bookmarkable_type': type, 'bookmarkable_id': id},
    );
  }

  Future<void> removeBookmark(dynamic bookmarkId) async {
    await api.delete(ApiConstants.bookmarkDelete(bookmarkId));
  }

  Future<List<dynamic>> followUps(dynamic assessmentId) async {
    final json = await api.get(ApiConstants.followUps(assessmentId));
    return List<dynamic>.from(json['data'] ?? []);
  }

  Future<void> addFollowUp(
    dynamic assessmentId, {
    required int severity,
    double? temperature,
    String? note,
    List<Map<String, dynamic>> answers = const [],
  }) async {
    await api.post(
      ApiConstants.followUps(assessmentId),
      body: {
        'severity': severity,
        if (temperature != null) 'temperature': temperature,
        if (note?.trim().isNotEmpty == true) 'note': note!.trim(),
        if (answers.isNotEmpty) 'answers': answers,
      },
    );
  }

  Future<Map<String, dynamic>> aiTrendSummary({
    int days = 30,
    String? from,
    String? to,
  }) async {
    final json = await api.post(
      ApiConstants.aiHealthTrendSummary,
      body: {
        if (from != null && to != null) ...{
          'from': from,
          'to': to,
        } else
          'days': days,
      },
    );
    return Map<String, dynamic>.from(json['data']);
  }

  Future<HealthEpisodeModel> startHealthEpisode(dynamic assessmentId) async {
    final json = await api.post(
      ApiConstants.assessmentHealthEpisode(assessmentId),
    );
    return HealthEpisodeModel.fromJson(Map<String, dynamic>.from(json['data']));
  }

  Future<HealthEpisodeModel> healthEpisode(dynamic episodeId) async {
    final json = await api.get(
      ApiConstants.healthEpisode(episodeId),
      forceRefresh: true,
    );
    return HealthEpisodeModel.fromJson(Map<String, dynamic>.from(json['data']));
  }

  Future<EpisodeSymptomModel> addEpisodeSymptom(
    dynamic episodeId, {
    String? symptomId,
    String? customSymptomText,
  }) async {
    final json = await api.post(
      ApiConstants.healthEpisodeSymptoms(episodeId),
      body: {
        if (symptomId != null) 'symptom_id': symptomId,
        if (customSymptomText?.trim().isNotEmpty == true)
          'custom_symptom_text': customSymptomText!.trim(),
      },
    );
    return EpisodeSymptomModel.fromJson(
      Map<String, dynamic>.from(json['data']),
    );
  }

  Future<void> addEpisodeFollowUp(
    dynamic episodeSymptomId, {
    required int severity,
    double? temperature,
    String? note,
    List<Map<String, dynamic>> answers = const [],
  }) async {
    await api.post(
      ApiConstants.episodeSymptomFollowUps(episodeSymptomId),
      body: {
        'severity': severity,
        if (temperature != null) 'temperature': temperature,
        if (note?.trim().isNotEmpty == true) 'note': note!.trim(),
        'answers': answers,
      },
    );
  }
}
