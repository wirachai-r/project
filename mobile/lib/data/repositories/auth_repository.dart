import '../services/api_service.dart';
import '../services/auth_service.dart';
import '../models/user_model.dart';
import '../../core/constants/api_constants.dart';

class AuthRepository {
  final ApiService _api;
  final AuthService _authService;

  AuthRepository({required ApiService api, required AuthService authService})
    : _api = api,
      _authService = authService;

  String? get token => _authService.token;

  Future<({String token, UserModel user})> login({
    required String email,
    required String password,
  }) async {
    final data = await _api.post(
      ApiConstants.login,
      body: {'email': email, 'password': password},
    );

    final token = data['token'] as String;
    final user = UserModel.fromJson(data['user']);

    await _authService.saveToken(token);
    _api.setToken(token);

    return (token: token, user: user);
  }

  Future<({String token, UserModel user})> register({
    required String firstName,
    required String lastName,
    required String email,
    required String password,
    required String passwordConfirmation,
    String? phone,
    String? dateOfBirth,
    String? sex,
  }) async {
    final data = await _api.post(
      ApiConstants.register,
      body: {
        'first_name': firstName,
        'last_name': lastName,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        if (phone != null) 'phone': phone,
        if (dateOfBirth != null) 'date_of_birth': dateOfBirth,
        if (sex != null) 'sex': sex,
      },
    );

    final token = data['token'] as String;
    final user = UserModel.fromJson(data['user']);

    await _authService.saveToken(token);
    _api.setToken(token);

    return (token: token, user: user);
  }

  Future<void> logout() async {
    // 1. call API ก่อน (ตอนนี้ยังมี token อยู่)
    try {
      await _api.post(ApiConstants.logout);
    } catch (_) {}

    // 2. ค่อยล้าง local
    await _authService.clearToken();
    _api.clearToken();
  }

  Future<UserModel> me() async {
    final data = await _api.get(ApiConstants.me);
    return UserModel.fromJson(data['data'] ?? data);
  }

  Future<void> initToken() async {
    final token = await _authService.getToken();
    if (token != null) _api.setToken(token);
  }

  /// Clear an invalid local session without calling the protected logout API.
  Future<void> clearLocalSession() async {
    await _authService.clearToken();
    _api.clearToken();
  }
}
