import 'package:flutter/material.dart';
import '../../../data/models/history_model.dart';
import '../../../data/repositories/history_repository.dart';

class HistoryDetailProvider extends ChangeNotifier {
  final HistoryRepository _repository;

  HistoryDetailProvider({required HistoryRepository repository})
    : _repository = repository;

  HistoryItemModel? _detail;
  bool _isLoading = false;
  String? _error;
  int _generation = 0;

  HistoryItemModel? get detail => _detail;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> load({required dynamic assessmentId}) async {
    final generation = _generation;
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final detail = await _repository.getDetail(assessmentId);
      if (generation != _generation) return;
      _detail = detail;
    } catch (e) {
      if (generation != _generation) return;
      _error = 'ผิดพลาด: ${e.toString()}';
    } finally {
      if (generation == _generation) {
        _isLoading = false;
        notifyListeners();
      }
    }
  }

  void reset() {
    _generation++;
    _detail = null;
    _isLoading = false;
    _error = null;
    notifyListeners();
  }
}
