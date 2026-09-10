import 'package:flutter/foundation.dart';

import '../services/api_service.dart';
import '../services/auth_service.dart';
import '../services/google_auth_service.dart';
import '../models/user_model.dart';
import '../../core/constants/api_constants.dart';

class AuthRepository {
  final ApiService _api;
  final AuthService _authService;
  final GoogleAuthService _googleAuthService;

  AuthRepository({
    required ApiService api,
    required AuthService authService,
    required GoogleAuthService googleAuthService,
  }) : _api = api,
       _authService = authService,
       _googleAuthService = googleAuthService;

  String? get token => _authService.token;

  Future<({String token, UserModel user})> login({
    required String email,
    required String password,
  }) async {
    final data = await _api.post(
      ApiConstants.login,
      body: {'email': email, 'password': password, ..._deviceMetadata},
    );

    final token = data['token'] as String;
    final user = UserModel.fromJson(data['user']);

    await _authService.saveToken(token);
    _api.setToken(token);

    return (token: token, user: user);
  }

  Future<({String token, UserModel user})> loginWithGoogle() async {
    final googleAccessToken = await _googleAuthService.signInAccessToken();
    final data = await _api.post(
      ApiConstants.googleLogin,
      body: {'token': googleAccessToken, ..._deviceMetadata},
    );

    final token = data['token'] as String;
    final user = UserModel.fromJson(data['user']);
    await _authService.saveToken(token);
    _api.setToken(token);
    return (token: token, user: user);
  }

  Future<OtpTiming> register({
    required String firstName,
    required String lastName,
    required String email,
    required String password,
    required String passwordConfirmation,
    String? phone,
    String? dateOfBirth,
    String? sex,
  }) async {
    final body = <String, String>{
      'first_name': firstName,
      'last_name': lastName,
      'email': email,
      'password': password,
      'password_confirmation': passwordConfirmation,
      ..._deviceMetadata,
    };
    if (phone != null) body['phone'] = phone;
    if (dateOfBirth != null) body['date_of_birth'] = dateOfBirth;
    if (sex != null) body['sex'] = sex;

    final data = await _api.post(ApiConstants.register, body: body);
    return OtpTiming.fromJson(data);
  }

  Future<({String token, UserModel user})> verifyRegistrationOtp({
    required String email,
    required String otp,
  }) async {
    final data = await _api.post(
      ApiConstants.verifyRegistrationOtp,
      body: {'email': email, 'otp': otp, ..._deviceMetadata},
    );
    final token = data['token'] as String;
    final user = UserModel.fromJson(data['user']);
    await _authService.saveToken(token);
    _api.setToken(token);
    return (token: token, user: user);
  }

  Future<OtpTiming> resendRegistrationOtp(String email) async {
    final data = await _api.post(
      ApiConstants.resendRegistrationOtp,
      body: {'email': email},
    );
    return OtpTiming.fromJson(data);
  }

  Future<void> logout() async {
    final currentToken = _authService.token;
    Future<dynamic>? remoteLogout;
    if (currentToken?.trim().isNotEmpty == true) {
      // Start the authenticated request while the API client still has the
      // token, but never make the UI wait for the network before signing out.
      remoteLogout = _api.post(ApiConstants.logout).catchError((_) => null);
    }

    await _authService.clearToken();
    _api.clearToken();
    try {
      await remoteLogout;
    } catch (_) {}
    await _googleAuthService.signOut();
  }

  Future<OtpTiming> forgotPassword(String email) async {
    final data = await _api.post(
      ApiConstants.forgotPassword,
      body: {'email': email},
    );
    return OtpTiming.fromJson(data);
  }

  Future<String> verifyPasswordOtp({
    required String email,
    required String otp,
  }) async {
    final data = await _api.post(
      ApiConstants.verifyPasswordOtp,
      body: {'email': email, 'otp': otp},
    );
    return data['reset_token'] as String;
  }

  Future<void> resetPassword({
    required String email,
    required String resetToken,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _api.post(
      ApiConstants.resetPassword,
      body: {
        'email': email,
        'reset_token': resetToken,
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
    );
  }

  Future<void> changePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _api.put(
      ApiConstants.changePassword,
      body: {
        'current_password': currentPassword,
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
    );
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

  Map<String, String> get _deviceMetadata {
    final type = kIsWeb
        ? 'web'
        : switch (defaultTargetPlatform) {
            TargetPlatform.android => 'android',
            TargetPlatform.iOS => 'ios',
            TargetPlatform.windows => 'windows',
            TargetPlatform.macOS => 'macos',
            TargetPlatform.linux => 'linux',
            TargetPlatform.fuchsia => 'unknown',
          };

    final name = switch (type) {
      'android' => 'โทรศัพท์ Android',
      'ios' => 'iPhone หรือ iPad',
      'web' => 'เว็บเบราว์เซอร์',
      'windows' => 'คอมพิวเตอร์ Windows',
      'macos' => 'คอมพิวเตอร์ Mac',
      'linux' => 'คอมพิวเตอร์ Linux',
      _ => 'อุปกรณ์ของฉัน',
    };

    return {'device_name': name, 'device_type': type};
  }
}

class OtpTiming {
  final int expiresIn;
  final int resendAvailableIn;

  const OtpTiming({required this.expiresIn, required this.resendAvailableIn});

  factory OtpTiming.fromJson(Map<String, dynamic> json) => OtpTiming(
    expiresIn: (json['expires_in'] as num?)?.toInt() ?? 300,
    resendAvailableIn:
        (json['resend_available_in'] as num?)?.toInt() ?? 0,
  );
}
