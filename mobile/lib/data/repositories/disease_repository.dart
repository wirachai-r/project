import '../services/api_service.dart';
import '../models/disease_model.dart';
import '../models/disease_category_model.dart';
import '../../core/constants/api_constants.dart';

class DiseaseRepository {
  final ApiService _api;

  DiseaseRepository({required ApiService api}) : _api = api;

  Future<List<DiseaseCategoryModel>> getCategories() async {
    final data = await _api.get(ApiConstants.diseaseCategories);
    final list = data['data'] as List? ?? data as List;
    return list.map((e) => DiseaseCategoryModel.fromJson(e)).toList();
  }

  /// index() ฝั่ง backend ไม่ paginate แล้ว (ใช้ ->get())
  /// เรียกครั้งเดียวได้ข้อมูลทั้งหมดเลย ไม่ต้อง loop page
  Future<List<DiseaseModel>> getDiseases({
    String? categoryId,
    String? search,
  }) async {
    final data = await _api.get(
      ApiConstants.diseases,
      params: {
        if (categoryId != null) 'disease_category_id': categoryId,
        if (search != null && search.isNotEmpty) 'search': search,
      },
    );

    final list = data['data'] as List? ?? [];
    return list.map((e) => DiseaseModel.fromJson(e)).toList();
  }

  /// alias เดิมไว้เผื่อโค้ดที่อื่นยังเรียก getAllDiseases() อยู่
  Future<List<DiseaseModel>> getAllDiseases({
    String? categoryId,
    String? search,
  }) => getDiseases(categoryId: categoryId, search: search);

  Future<DiseaseModel> getDiseaseDetail(String diseaseId) async {
    final data = await _api.get(ApiConstants.diseaseDetail(diseaseId));
    return DiseaseModel.fromJson(data['data'] ?? data);
  }
}
