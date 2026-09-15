import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import 'dart:convert';
import 'package:checkup/data/services/central_http_client.dart' as http;

class NotificationProvider extends ChangeNotifier {
  List<dynamic> items = [];
  int unreadCount = 0;
  bool isLoading = false;

  Future<void> load(String token) async {
    isLoading = true;
    notifyListeners();
    final res = await http.get(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.notifications}'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );
    if (res.statusCode == 200) {
      items = jsonDecode(res.body)['data'] ?? [];
      unreadCount = items.where((i) => i['is_read'] == 'N').length;
    }
    isLoading = false;
    notifyListeners();
  }

  Future<void> markAllRead(String token) async {
    await http.post(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.notificationsReadAll}'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );
    for (var item in items) item['is_read'] = 'Y';
    unreadCount = 0;
    notifyListeners();
  }
}
