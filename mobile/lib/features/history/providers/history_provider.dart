import 'package:flutter/material.dart';
import '../../../data/models/history_model.dart';
import '../../../data/repositories/history_repository.dart';

class HistoryProvider extends ChangeNotifier {
  final HistoryRepository _repository;

  HistoryProvider({required HistoryRepository repository})
    : _repository = repository;

  List<HistoryItemModel> _items = [];
  bool _isLoading = false;
  bool _isLoadingMore = false;
  String? _error;
  int _currentPage = 1;
  bool _hasMore = true;

  List<HistoryItemModel> get items => _items;
  bool get isLoading => _isLoading;
  bool get isLoadingMore => _isLoadingMore;
  String? get error => _error;
  bool get hasMore => _hasMore;
  bool get isEmpty => !_isLoading && _items.isEmpty && _error == null;

  Future<void> load({bool refresh = false}) async {
    if (!refresh && (_isLoadingMore || !_hasMore)) return;

    if (refresh) {
      _currentPage = 1;
      _hasMore = true;
      _isLoading = true;
      _error = null;
    } else {
      _isLoadingMore = true;
    }
    notifyListeners();

    try {
      final result = await _repository.getHistory(page: _currentPage);

      _items = refresh ? result.items : [..._items, ...result.items];
      _hasMore = result.currentPage < result.lastPage;
      _currentPage++;
    } catch (e) {
      _error = 'ไม่สามารถโหลดประวัติการประเมินได้';
    } finally {
      _isLoading = false;
      _isLoadingMore = false;
      notifyListeners();
    }
  }

  void reset() {
    _items = [];
    _isLoading = false;
    _isLoadingMore = false;
    _error = null;
    _currentPage = 1;
    _hasMore = true;
    notifyListeners();
  }
}
