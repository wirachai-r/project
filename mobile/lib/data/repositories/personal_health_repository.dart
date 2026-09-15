import '../../core/constants/api_constants.dart';
import '../services/api_service.dart';
import '../models/daily_health_record_model.dart';
import '../models/health_episode_model.dart';

class PersonalHealthRepository {
  final ApiService api;
  final Map<String, Map<String, dynamic>> _aiTrendSummaryCache = {};

  PersonalHealthRepository({required this.api});

  String _aiTrendCacheKey({int days = 30, String? from, String? to}) =>
      from != null && to != null ? '$from:$to' : 'days:$days';

  Map<String, dynamic>? cachedAiTrendSummary({
    int days = 30,
    String? from,
    String? to,
  }) {
    final value =
        _aiTrendSummaryCache[_aiTrendCacheKey(days: days, from: from, to: to)];
    return value == null ? null : Map<String, dynamic>.from(value);
  }

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
    dynamic recordId,
    required String recordedOn,
    required String status,
    String? note,
    List<String> symptomIds = const [],
    List<dynamic> healthEpisodeIds = const [],
  }) async {
    final body = {
      'recorded_on': recordedOn,
      'status': status,
      'symptom_ids': status == 'unwell' ? symptomIds : <String>[],
      'health_episode_ids': healthEpisodeIds,
      if (note?.trim().isNotEmpty == true) 'note': note!.trim(),
    };
    final json = recordId == null
        ? await api.post(ApiConstants.dailyHealthRecords, body: body)
        : await api.patch(ApiConstants.dailyHealthRecord(recordId), body: body);
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
      timeout: const Duration(seconds: 75),
      body: {
        if (from != null && to != null) ...{
          'from': from,
          'to': to,
        } else
          'days': days,
      },
    );
    final result = Map<String, dynamic>.from(json['data']);
    _aiTrendSummaryCache[_aiTrendCacheKey(days: days, from: from, to: to)] =
        result;
    return Map<String, dynamic>.from(result);
  }

  Future<HealthEpisodeModel> startHealthEpisode(
    dynamic assessmentId, {
    dynamic healthEpisodeId,
  }) async {
    final json = await api.post(
      ApiConstants.assessmentHealthEpisode(assessmentId),
      body: {if (healthEpisodeId != null) 'health_episode_id': healthEpisodeId},
    );
    return HealthEpisodeModel.fromJson(Map<String, dynamic>.from(json['data']));
  }

  Future<HealthEpisodeModel> startHealthEpisodeFromDailyRecord(
    dynamic recordId, {
    required List<String> symptomIds,
    dynamic healthEpisodeId,
  }) async {
    final json = await api.post(
      ApiConstants.dailyHealthRecordHealthEpisode(recordId),
      body: {
        'symptom_ids': symptomIds,
        if (healthEpisodeId != null) 'health_episode_id': healthEpisodeId,
      },
    );
    return HealthEpisodeModel.fromJson(Map<String, dynamic>.from(json['data']));
  }

  Future<Map<String, dynamic>> createFollowUpReminder({
    required dynamic healthEpisodeId,
    required String title,
    required String timeOfDay,
  }) async {
    final json = await api.post(
      ApiConstants.healthReminders,
      body: {
        'health_episode_id': healthEpisodeId,
        'title': title,
        'reminder_type': 'follow_up',
        'frequency': 'daily',
        'time_of_day': timeOfDay,
        'timezone': 'Asia/Bangkok',
        'is_enabled': true,
      },
    );
    return Map<String, dynamic>.from(json['data']);
  }

  Future<HealthEpisodeModel> updateHealthEpisodeStatus(
    dynamic episodeId, {
    required String status,
    String? endReason,
    String? endNote,
  }) async {
    final json = await api.patch(
      ApiConstants.healthEpisodeStatus(episodeId),
      body: {
        'status': status,
        if (endReason != null) 'end_reason': endReason,
        if (endNote?.trim().isNotEmpty == true) 'end_note': endNote!.trim(),
      },
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

  Future<List<HealthEpisodeModel>> healthEpisodes({
    String? from,
    String? to,
  }) async {
    final json = await api.get(
      ApiConstants.healthEpisodes,
      params: {if (from != null) 'from': from, if (to != null) 'to': to},
      forceRefresh: true,
    );
    return List<Map<String, dynamic>>.from(
      json['data'] ?? const [],
    ).map(HealthEpisodeModel.fromJson).toList();
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
    int? severity,
    double? temperature,
    String? note,
    DateTime? recordedAt,
    List<Map<String, dynamic>> answers = const [],
  }) async {
    await api.post(
      ApiConstants.episodeSymptomFollowUps(episodeSymptomId),
      body: {
        'severity': severity,
        if (temperature != null) 'temperature': temperature,
        if (note?.trim().isNotEmpty == true) 'note': note!.trim(),
        if (recordedAt != null)
          'recorded_at': recordedAt.toUtc().toIso8601String(),
        'answers': answers,
      },
    );
  }

  Future<void> updateEpisodeFollowUp(
    dynamic entryId, {
    int? severity,
    double? temperature,
    String? note,
    List<Map<String, dynamic>> answers = const [],
  }) async {
    await api.patch(
      ApiConstants.followUpEntry(entryId),
      body: {
        'severity': severity,
        if (temperature != null) 'temperature': temperature,
        'note': note?.trim() ?? '',
        'answers': answers,
      },
    );
  }
}
