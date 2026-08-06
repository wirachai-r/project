import '../../core/constants/api_constants.dart';
import '../services/api_service.dart';

class PersonalHealthRepository {
  final ApiService api;
  PersonalHealthRepository({required this.api});

  Future<Map<String, dynamic>> dashboard() async =>
      Map<String, dynamic>.from(await api.get(ApiConstants.healthDashboard));

  Future<List<dynamic>> bookmarks() async {
    final json = await api.get(ApiConstants.bookmarks);
    return List<dynamic>.from(json['data'] ?? []);
  }

  Future<void> addBookmark(String type, String id) async {
    await api.post(ApiConstants.bookmarks, body: {
      'bookmarkable_type': type,
      'bookmarkable_id': id,
    });
  }

  Future<void> removeBookmark(dynamic bookmarkId) async {
    await api.delete(ApiConstants.bookmarkDelete(bookmarkId));
  }

  Future<List<dynamic>> followUps(dynamic assessmentId) async {
    final json = await api.get(ApiConstants.followUps(assessmentId));
    return List<dynamic>.from(json['data'] ?? []);
  }

  Future<void> addFollowUp(dynamic assessmentId, {
    required int severity,
    double? temperature,
    String? note,
  }) async {
    await api.post(ApiConstants.followUps(assessmentId), body: {
      'severity': severity,
      if (temperature != null) 'temperature': temperature,
      if (note?.trim().isNotEmpty == true) 'note': note!.trim(),
    });
  }
}
