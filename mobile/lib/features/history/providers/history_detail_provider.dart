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

  HistoryItemModel? get detail => _detail;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> load({required dynamic assessmentId}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      _detail = await _repository.getDetail(assessmentId);
    } catch (e) {
      _error = 'ผิดพลาด: ${e.toString()}';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}
