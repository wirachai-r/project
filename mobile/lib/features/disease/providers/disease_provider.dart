import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class DiseaseProvider extends ChangeNotifier {
  List<dynamic> diseases = [];
  List<dynamic> categories = [];
  bool isLoading = false;
  String? error;
  String? selectedCategoryId;
  String search = '';

  Future<void> loadCategories() async {
    final res = await http.get(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.diseaseCategories}'),
      headers: {'Accept': 'application/json'},
    );
    if (res.statusCode == 200) {
      categories = jsonDecode(res.body)['data'] ?? [];
      notifyListeners();
    }
  }

  Future<void> loadDiseases({bool refresh = false}) async {
    isLoading = true; error = null; notifyListeners();
    try {
      final uri = Uri.parse('${ApiConstants.baseUrl}${ApiConstants.diseases}')
        .replace(queryParameters: {
          if (selectedCategoryId != null) 'disease_category_id': selectedCategoryId!,
          if (search.isNotEmpty) 'search': search,
        });
      final res = await http.get(uri, headers: {'Accept': 'application/json'});
      diseases = jsonDecode(res.body)['data'] ?? [];
    } catch (e) {
      error = e.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }
}
