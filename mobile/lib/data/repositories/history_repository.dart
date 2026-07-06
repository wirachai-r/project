import '../services/api_service.dart';
import '../models/history_model.dart';
import '../../core/constants/api_constants.dart';

class HistoryRepository {
  final ApiService _api;

  HistoryRepository({required ApiService api}) : _api = api;

  Future<({List<HistoryItemModel> items, int currentPage, int lastPage})>
  getHistory({required int page}) async {
    final data = await _api.get(
      ApiConstants.assessmentHistory,
      params: {'page': page.toString()},
    );

    final list = (data['data'] as List? ?? [])
        .map((e) => HistoryItemModel.fromJson(e))
        .toList();

    final int current =
        (data['meta']?['current_page'] ?? data['current_page'] ?? page) as int;
    final int last =
        (data['meta']?['last_page'] ?? data['last_page'] ?? page) as int;

    return (items: list, currentPage: current, lastPage: last);
  }

  Future<HistoryItemModel> getDetail(dynamic assessmentId) async {
    final data = await _api.get(ApiConstants.assessmentDetail(assessmentId));
    return HistoryItemModel.fromJson(data['data'] ?? data);
  }
}
