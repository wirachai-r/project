import 'package:flutter/foundation.dart';
import 'package:google_sign_in/google_sign_in.dart';

class GoogleLoginCanceledException implements Exception {
  const GoogleLoginCanceledException();
}

class GoogleLoginUnavailableException implements Exception {
  final String message;

  const GoogleLoginUnavailableException(this.message);

  @override
  String toString() => message;
}

class GoogleAuthService {
  static const _clientId = String.fromEnvironment('GOOGLE_CLIENT_ID');
  static const _serverClientId = String.fromEnvironment(
    'GOOGLE_SERVER_CLIENT_ID',
  );
  static const _scopes = [
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/userinfo.profile',
  ];

  final GoogleSignIn _googleSignIn = GoogleSignIn.instance;
  Future<void>? _initialization;

  Future<String> signInAccessToken() async {
    if (kIsWeb ||
        (defaultTargetPlatform != TargetPlatform.android &&
            defaultTargetPlatform != TargetPlatform.iOS)) {
      throw const GoogleLoginUnavailableException(
        'Google Login ในแอปนี้รองรับเฉพาะ Android และ iOS',
      );
    }

    try {
      await (_initialization ??= _initialize());
    } on UnimplementedError {
      _initialization = null;
      throw const GoogleLoginUnavailableException(
        'กรุณาปิดแอปแล้วเปิดใหม่หลังติดตั้ง Google Login',
      );
    }
    if (!_googleSignIn.supportsAuthenticate()) {
      throw const GoogleLoginUnavailableException(
        'อุปกรณ์นี้ไม่รองรับการเข้าสู่ระบบด้วย Google',
      );
    }

    try {
      final account = await _googleSignIn.authenticate(scopeHint: _scopes);
      final authorization =
          await account.authorizationClient.authorizationForScopes(_scopes) ??
          await account.authorizationClient.authorizeScopes(_scopes);
      return authorization.accessToken;
    } on GoogleSignInException catch (error) {
      if (error.code == GoogleSignInExceptionCode.canceled) {
        throw const GoogleLoginCanceledException();
      }
      rethrow;
    }
  }

  Future<void> signOut() => _googleSignIn.signOut();

  Future<void> _initialize() => _googleSignIn.initialize(
    clientId: _clientId.isEmpty ? null : _clientId,
    serverClientId: _serverClientId.isEmpty ? null : _serverClientId,
  );
}
