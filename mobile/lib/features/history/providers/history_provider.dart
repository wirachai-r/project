import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class HistoryProvider extends ChangeNotifier {
  List<dynamic> items = [];
  bool isLoading = false;
  String? error;
  int currentPage = 1;
  bool hasMore = true;

  Future<void> load(String token, {bool refresh = false}) async {
    if (refresh) {
      currentPage = 1;
      hasMore = true;
    }
    isLoading = true;
    error = null;
    notifyListeners();

    try {
      final res = await http.get(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.assessments}/history?page=$currentPage',
        ),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );
      final data = jsonDecode(res.body);
      final newItems = data['data'] as List? ?? [];
      if (refresh)
        items = newItems;
      else
        items.addAll(newItems);
      hasMore =
          data['meta']?['current_page'] < (data['meta']?['last_page'] ?? 1);
      currentPage++;
    } catch (e) {
      error = e.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }
}
