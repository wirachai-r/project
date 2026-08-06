import 'package:flutter/material.dart';
import '../../../data/repositories/auth_repository.dart';
import '../../../data/models/user_model.dart';
import '../../../core/errors/app_exception.dart';

enum AuthStatus { initial, loading, authenticated, unauthenticated, error }

class AuthProvider extends ChangeNotifier {
  final AuthRepository _repo;

  AuthProvider({required AuthRepository repo}) : _repo = repo;

  AuthStatus _status = AuthStatus.initial;
  UserModel? _user;
  String? _errorMessage;
  Map<String, List<String>> _validationErrors = {};

  AuthStatus get status => _status;
  UserModel? get user => _user;
  String? get errorMessage => _errorMessage;
  Map<String, List<String>> get validationErrors => _validationErrors;
  bool get isAuthenticated => _status == AuthStatus.authenticated;
  String? get token => _repo.token;

  /// เรียกตอนแอปเปิด
  Future<void> init() async {
    await _repo.initToken();

    // Guest users do not have a persisted token. Avoid calling the protected
    // /auth/me endpoint because a 401 is expected in that case and is reported
    // as a failed request by Flutter Web's browser console.
    final savedToken = _repo.token;
    if (savedToken == null || savedToken.trim().isEmpty) {
      if (savedToken != null) await _repo.clearLocalSession();
      _user = null;
      _status = AuthStatus.unauthenticated;
      notifyListeners();
      return;
    }

    try {
      _user = await _repo.me();
      _status = AuthStatus.authenticated;
    } on UnauthorizedException {
      // A persisted token can become invalid after it expires or is revoked.
      // Remove it so protected requests are not sent with a stale credential.
      await _repo.clearLocalSession();
      _user = null;
      _status = AuthStatus.unauthenticated;
    } catch (_) {
      _status = AuthStatus.unauthenticated;
    }
    notifyListeners();
  }

  Future<bool> login({required String email, required String password}) async {
    _setLoading();
    try {
      final result = await _repo.login(email: email, password: password);
      _user = result.user;
      _status = AuthStatus.authenticated;
      notifyListeners();
      return true;
    } catch (e) {
      _setError(e);
      return false;
    }
  }

  Future<bool> register({
    required String firstName,
    required String lastName,
    required String email,
    required String password,
    required String passwordConfirmation,
    String? phone,
    String? dateOfBirth,
    String? sex,
  }) async {
    _setLoading();
    try {
      final result = await _repo.register(
        firstName: firstName,
        lastName: lastName,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
        phone: phone,
        dateOfBirth: dateOfBirth,
        sex: sex,
      );
      _user = result.user;
      _status = AuthStatus.authenticated;
      notifyListeners();
      return true;
    } catch (e) {
      _setError(e);
      return false;
    }
  }

  Future<void> logout() async {
    try {
      await _repo.logout(); // call API + ล้าง local
    } catch (_) {}

    _user = null;
    _status = AuthStatus.unauthenticated;
    notifyListeners();
  }

  void _setLoading() {
    _status = AuthStatus.loading;
    _errorMessage = null;
    _validationErrors = {};
    notifyListeners();
  }

  void _setError(Object e) {
    _status = AuthStatus.unauthenticated;
    _errorMessage = e.toString();
    notifyListeners();
  }
}
