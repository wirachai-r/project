import 'package:flutter/material.dart';
import '../../../data/models/disease_model.dart';
import '../../../data/models/disease_category_model.dart';
import '../../../data/repositories/disease_repository.dart';

class DiseaseProvider extends ChangeNotifier {
  final DiseaseRepository _repository;

  DiseaseProvider({required DiseaseRepository repository})
    : _repository = repository;

  List<DiseaseModel> _diseases = [];
  List<DiseaseCategoryModel> _categories = [];
  String? _selectedCategoryId;
  String _searchKeyword = '';

  bool _isLoading = false;
  String? _error;
  int _requestVersion = 0;

  List<DiseaseModel> get diseases => _diseases;
  List<DiseaseCategoryModel> get categories => _categories;
  String? get selectedCategoryId => _selectedCategoryId;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get isEmpty => !_isLoading && _diseases.isEmpty && _error == null;

  Future<void> loadCategories() async {
    try {
      _categories = await _repository.getCategories();
      notifyListeners();
    } catch (_) {
      // เงียบไว้ ไม่บล็อกหน้าจอหลัก ถ้าหมวดหมู่โหลดไม่สำเร็จ
    }
  }

  /// index() ฝั่ง backend ไม่ paginate แล้ว (->get())
  /// เรียกครั้งเดียวได้ครบทุกโรค ไม่มี "โหลดเพิ่ม" อีกต่อไป
  Future<void> loadDiseases({bool refresh = false}) async {
    final requestVersion = ++_requestVersion;
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final diseases = await _repository.getDiseases(
        categoryId: _selectedCategoryId,
        search: _searchKeyword,
      );
      if (requestVersion != _requestVersion) return;
      _diseases = diseases;
    } catch (e) {
      if (requestVersion != _requestVersion) return;
      _error = e.toString();
    } finally {
      if (requestVersion != _requestVersion) return;
      _isLoading = false;
      notifyListeners();
    }
  }

  void selectCategory(String? categoryId) {
    _selectedCategoryId = categoryId;
    loadDiseases(refresh: true);
  }

  void search(String keyword) {
    _searchKeyword = keyword;
    loadDiseases(refresh: true);
  }

  void reset() {
    _diseases = [];
    _selectedCategoryId = null;
    _searchKeyword = '';
    _isLoading = false;
    _error = null;
    notifyListeners();
  }
}
