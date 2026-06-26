import 'package:shared_preferences/shared_preferences.dart';

class AuthService {
  static const _tokenKey = 'auth_token';
  static const _sessionKey = 'session_token';

  String? _token; // เพิ่ม
  String? get token => _token; // เพิ่ม

  Future<void> saveToken(String token) async {
    _token = token; // เพิ่ม
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(_tokenKey); // เพิ่ม
    return _token;
  }

  Future<void> clearToken() async {
    _token = null; // เพิ่ม
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  Future<void> saveSessionToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_sessionKey, token);
  }

  Future<String?> getSessionToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_sessionKey);
  }

  Future<bool> isLoggedIn() async => (await getToken()) != null;
}
