import 'dart:convert';

import 'central_http_client.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../../core/constants/api_constants.dart';

class FirstAidOfflineService {
  static const _bundleKey = 'first_aid_offline_bundle_v1';
  static const _savedAtKey = 'first_aid_offline_saved_at_v1';

  Future<List<Map<String, dynamic>>> download() async {
    final response = await http.get(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.firstAidsOffline}'),
      headers: {'Accept': 'application/json'},
    );
    if (response.statusCode != 200) {
      throw Exception('Unable to download first aid content');
    }
    final decoded = jsonDecode(response.body) as Map<String, dynamic>;
    final items = (decoded['data'] as List<dynamic>? ?? [])
        .cast<Map<String, dynamic>>();
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_bundleKey, jsonEncode(items));
    await prefs.setString(
      _savedAtKey,
      DateTime.now().toUtc().toIso8601String(),
    );
    return items;
  }

  Future<List<Map<String, dynamic>>> readAll() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_bundleKey);
    if (raw == null) return [];
    try {
      return (jsonDecode(raw) as List<dynamic>).cast<Map<String, dynamic>>();
    } catch (_) {
      return [];
    }
  }

  Future<Map<String, dynamic>?> read(String id) async {
    final items = await readAll();
    for (final item in items) {
      if (item['first_aid_id']?.toString() == id) return item;
    }
    return null;
  }

  Future<DateTime?> savedAt() async {
    final prefs = await SharedPreferences.getInstance();
    return DateTime.tryParse(prefs.getString(_savedAtKey) ?? '')?.toLocal();
  }
}
