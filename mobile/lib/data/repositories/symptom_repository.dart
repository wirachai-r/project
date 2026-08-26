import '../services/api_service.dart';
import '../models/symptom_model.dart';
import '../../core/constants/api_constants.dart';
import '../models/body_area_group_model.dart';

class SymptomRepository {
  final ApiService _api;

  SymptomRepository({required ApiService api}) : _api = api;

  Future<List<SymptomCategoryModel>> getCategories() async {
    final data = await _api.get(ApiConstants.symptomCategories);
    final list = data['data'] as List? ?? data as List;
    return list.map((e) => SymptomCategoryModel.fromJson(e)).toList();
  }

  Future<List<BodyAreaGroupModel>> getBodyAreaGroups() async {
    final data = await _api.get(ApiConstants.bodyAreaGroups);
    final list = data['data'] as List? ?? [];
    return list.map((e) => BodyAreaGroupModel.fromJson(e)).toList();
  }

  Future<List<SymptomModel>> getBodyAreaSymptoms(int groupId) async {
    final data = await _api.get(ApiConstants.bodyAreaGroupSymptoms(groupId));
    final list = data['data'] as List? ?? [];
    return list.map((e) => SymptomModel.fromJson(e)).toList();
  }

  Future<List<SymptomModel>> getSymptoms({
    String? categoryId,
    String? search,
    String? status,
  }) async {
    List<SymptomModel> allSymptoms = [];
    int page = 1;
    bool hasMore = true;

    while (hasMore) {
      final data = await _api.get(
        ApiConstants.symptoms,
        params: {
          if (categoryId != null) 'symptom_category_id': categoryId,
          if (search != null && search.isNotEmpty) 'search': search,
          if (status != null) 'status': status,
          'page': page.toString(),
        },
      );

      final list = data['data'] as List? ?? [];
      allSymptoms.addAll(list.map((e) => SymptomModel.fromJson(e)));

      // เช็คว่ามีหน้าถัดไปไหม
      final meta = data['meta'] as Map<String, dynamic>?;
      final lastPage = meta?['last_page'] ?? data['last_page'] ?? 1;
      hasMore = page < lastPage;
      page++;
    }

    return allSymptoms;
  }

  Future<SymptomModel> getSymptom(String symptomId) async {
    final data = await _api.get(ApiConstants.symptomDetail(symptomId));
    return SymptomModel.fromJson(data['data'] ?? data);
  }
}
