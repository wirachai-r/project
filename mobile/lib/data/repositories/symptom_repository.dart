import '../services/api_service.dart';
import '../models/symptom_model.dart';
import '../../core/constants/api_constants.dart';

class SymptomRepository {
  final ApiService _api;

  SymptomRepository({required ApiService api}) : _api = api;

  Future<List<SymptomCategoryModel>> getCategories() async {
    final data = await _api.get(ApiConstants.symptomCategories);
    final list = data['data'] as List? ?? data as List;
    return list.map((e) => SymptomCategoryModel.fromJson(e)).toList();
  }

  Future<List<SymptomModel>> getSymptoms({
    String? categoryId,
    String? search,
    String? status,
  }) async {
    final data = await _api.get(ApiConstants.symptoms, params: {
      if (categoryId != null) 'symptom_category_id': categoryId,
      if (search != null) 'search': search,
      if (status != null) 'status': status,
    });
    final list = data['data'] as List? ?? data as List;
    return list.map((e) => SymptomModel.fromJson(e)).toList();
  }

  Future<SymptomModel> getSymptom(String symptomId) async {
    final data = await _api.get(ApiConstants.symptomDetail(symptomId));
    return SymptomModel.fromJson(data['data'] ?? data);
  }
}
