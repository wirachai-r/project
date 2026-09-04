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

    if (defaultTargetPlatform == TargetPlatform.android &&
        _serverClientId.isEmpty) {
      throw const GoogleLoginUnavailableException(
        'ยังไม่ได้ตั้งค่า GOOGLE_SERVER_CLIENT_ID สำหรับ Android',
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
      throw GoogleLoginUnavailableException(_messageFor(error));
    }
  }

  Future<void> signOut() => _googleSignIn.signOut();

  Future<void> _initialize() => _googleSignIn.initialize(
    clientId:
        defaultTargetPlatform == TargetPlatform.iOS && _clientId.isNotEmpty
        ? _clientId
        : null,
    serverClientId: _serverClientId.isEmpty ? null : _serverClientId,
  );

  String _messageFor(GoogleSignInException error) {
    if (error.code == GoogleSignInExceptionCode.clientConfigurationError) {
      return 'ตั้งค่า Google Login ไม่ตรงกับ package name หรือ SHA ของแอป';
    }
    return 'ไม่สามารถเชื่อมต่อ Google Login ได้ กรุณาลองใหม่';
  }
}
