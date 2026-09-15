import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import 'dart:convert';
import 'package:checkup/data/services/central_http_client.dart' as http;

class ArticleProvider extends ChangeNotifier {
  List<dynamic> articles = [];
  List<dynamic> categories = [];
  bool isLoading = false;
  String? error;
  int currentPage = 1;
  bool hasMore = true;
  String? selectedCategoryId;
  String search = '';
  String sort = 'latest';

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
    if (refresh) {
      currentPage = 1;
      hasMore = true;
    }
    isLoading = true;
    error = null;
    notifyListeners();

    try {
      final queryParameters = <String, String>{
        'page': '$currentPage',
        'sort': sort,
      };
      if (selectedCategoryId != null) {
        queryParameters['article_category_id'] = selectedCategoryId!;
      }
      if (search.isNotEmpty) {
        queryParameters['search'] = search;
      }
      final uri = Uri.parse(
        '${ApiConstants.baseUrl}${ApiConstants.articles}',
      ).replace(queryParameters: queryParameters);
      final res = await http.get(uri, headers: {'Accept': 'application/json'});
      final data = jsonDecode(res.body);
      final items = data['data'] as List? ?? [];

      if (refresh) {
        articles = items;
      } else {
        articles.addAll(items);
      }
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
