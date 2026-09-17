import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
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

  late final GoogleSignIn _googleSignIn = GoogleSignIn(
    scopes: _scopes,
    clientId:
        (kIsWeb || defaultTargetPlatform == TargetPlatform.iOS) &&
            _clientId.isNotEmpty
        ? _clientId
        : null,
    serverClientId: kIsWeb || _serverClientId.isEmpty ? null : _serverClientId,
  );

  Future<String> signInAccessToken() async {
    if (!kIsWeb &&
        defaultTargetPlatform != TargetPlatform.android &&
        defaultTargetPlatform != TargetPlatform.iOS) {
      throw const GoogleLoginUnavailableException(
        'Google Login ในแอปนี้รองรับเฉพาะ Android, iOS และ Web',
      );
    }

    try {
      final account = await _googleSignIn.signIn();
      if (account == null) throw const GoogleLoginCanceledException();

      final authentication = await account.authentication;
      final accessToken = authentication.accessToken;
      if (accessToken == null || accessToken.isEmpty) {
        throw const GoogleLoginUnavailableException(
          'ไม่ได้รับ Google Access Token กรุณาลองใหม่',
        );
      }
      return accessToken;
    } on PlatformException catch (error) {
      if (kDebugMode) {
        debugPrint(
          'Google Sign-In exception: code=${error.code}, '
          'message=${error.message}, details=${error.details}',
        );
      }
      if (_isAccountReauthenticationFailure(error)) {
        throw const GoogleLoginUnavailableException(
          'บัญชี Google ในเครื่องยืนยันตัวตนไม่สำเร็จ '
          'กรุณาเข้าสู่ระบบบัญชี Google ในการตั้งค่าโทรศัพท์อีกครั้ง '
          'แล้วลองใหม่',
        );
      }
      if (error.code == 'sign_in_canceled') {
        throw const GoogleLoginCanceledException();
      }
      throw GoogleLoginUnavailableException(_messageFor(error));
    }
  }

  Future<void> signOut() => _googleSignIn.signOut();

  bool _isAccountReauthenticationFailure(PlatformException error) =>
      error.message?.toLowerCase().contains('account reauth failed') == true;

  String _messageFor(PlatformException error) {
    if (error.code == 'sign_in_failed') {
      return 'ตั้งค่า Google Login ไม่ครบ กรุณาตรวจ package name, SHA-1/SHA-256 '
          'และ google-services.json ใน Firebase';
    }
    return 'ไม่สามารถเชื่อมต่อ Google Login ได้ กรุณาลองใหม่';
  }
}
