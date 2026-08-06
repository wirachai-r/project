import 'package:flutter/material.dart';
import '../../../data/models/disease_model.dart';
import '../../../data/repositories/disease_repository.dart';

class DiseaseDetailProvider extends ChangeNotifier {
  final DiseaseRepository _repository;

  DiseaseDetailProvider({required DiseaseRepository repository})
    : _repository = repository;

  DiseaseModel? _detail;
  bool _isLoading = false;
  String? _error;

  DiseaseModel? get detail => _detail;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> load(String diseaseId, {bool trackView = true}) async {
    _isLoading = true;
    _error = null;
    _detail = null;
    notifyListeners();

    try {
      _detail = await _repository.getDiseaseDetail(
        diseaseId,
        trackView: trackView,
      );
    } catch (e) {
      _error = 'ผิดพลาด: ${e.toString()}';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  void reset() {
    _detail = null;
    _isLoading = false;
    _error = null;
    notifyListeners();
  }
}
