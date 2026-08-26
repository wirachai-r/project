import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class FirstAidProvider extends ChangeNotifier {
  List<dynamic> items = [];
  List<dynamic> categories = [];
  bool isLoading = false;
  String? error;
  String? selectedCategoryId;
  String search = '';

  Future<void> loadCategories() async {
    final res = await http.get(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.firstAidCategories}'),
      headers: {'Accept': 'application/json'},
    );
    if (res.statusCode == 200) {
      categories = jsonDecode(res.body)['data'] ?? [];
      notifyListeners();
    }
  }

  Future<void> loadItems() async {
    isLoading = true;
    error = null;
    notifyListeners();
    try {
      final uri = Uri.parse('${ApiConstants.baseUrl}${ApiConstants.firstAids}')
          .replace(
            queryParameters: {
              if (selectedCategoryId != null)
                'first_aid_category_id': selectedCategoryId!,
              if (search.isNotEmpty) 'search': search,
            },
          );
      final res = await http.get(uri, headers: {'Accept': 'application/json'});
      items = jsonDecode(res.body)['data'] ?? [];
    } catch (e) {
      error = e.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }
}
