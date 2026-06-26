import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class ArticleProvider extends ChangeNotifier {
  List<dynamic> articles = [];
  List<dynamic> categories = [];
  bool isLoading = false;
  String? error;
  int currentPage = 1;
  bool hasMore = true;
  String? selectedCategoryId;
  String search = '';

  Future<void> loadCategories() async {
    final res = await http.get(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.articleCategories}'),
      headers: {'Accept': 'application/json'},
    );
    if (res.statusCode == 200) {
      categories = jsonDecode(res.body)['data'] ?? [];
      notifyListeners();
    }
  }

  Future<void> loadArticles({bool refresh = false}) async {
    if (refresh) { currentPage = 1; hasMore = true; }
    isLoading = true; error = null; notifyListeners();

    try {
      final uri = Uri.parse('${ApiConstants.baseUrl}${ApiConstants.articles}')
        .replace(queryParameters: {
          'page': '$currentPage',
          if (selectedCategoryId != null) 'article_category_id': selectedCategoryId!,
          if (search.isNotEmpty) 'search': search,
        });
      final res = await http.get(uri, headers: {'Accept': 'application/json'});
      final data = jsonDecode(res.body);
      final items = data['data'] as List? ?? [];

      if (refresh) articles = items; else articles.addAll(items);
      hasMore = data['meta']?['current_page'] < (data['meta']?['last_page'] ?? 1);
      currentPage++;
    } catch (e) {
      error = e.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }
}
