import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import 'dart:convert';
import 'package:checkup/data/services/central_http_client.dart' as http;

class FacilityProvider extends ChangeNotifier {
  List<dynamic> items = [];
  bool isLoading = false;
  String? error;
  String? selectedType;
  String search = '';

  Future<void> load() async {
    isLoading = true;
    error = null;
    notifyListeners();
    try {
      final uri = Uri.parse('${ApiConstants.baseUrl}${ApiConstants.facilities}')
          .replace(
            queryParameters: {
              if (selectedType != null) 'facility_type': selectedType!,
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
