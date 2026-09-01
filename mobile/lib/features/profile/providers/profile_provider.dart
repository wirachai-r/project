import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import 'dart:convert';
import 'package:mobile/data/services/central_http_client.dart' as http;

class ProfileProvider extends ChangeNotifier {
  Map<String, dynamic>? user;
  bool isLoading = false;
  String? error;
  bool isSaving = false;

  Future<void> load(String token) async {
    isLoading = true;
    error = null;
    notifyListeners();
    try {
      final res = await http.get(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.profile}'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );
      if (res.statusCode == 200) user = jsonDecode(res.body)['data'];
    } catch (e) {
      error = e.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> update(String token, Map<String, dynamic> data) async {
    isSaving = true;
    notifyListeners();
    try {
      final res = await http.put(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.profile}'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode(data),
      );
      if (res.statusCode == 200) {
        user = jsonDecode(res.body)['data'];
        isSaving = false;
        notifyListeners();
        return true;
      }
    } catch (_) {}
    isSaving = false;
    notifyListeners();
    return false;
  }
}
