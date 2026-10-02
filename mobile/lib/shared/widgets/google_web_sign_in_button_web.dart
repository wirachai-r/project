import 'dart:async';

import 'package:flutter/material.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:google_sign_in_web/web_only.dart' as google_web;

class GoogleWebSignInButton extends StatefulWidget {
  final Future<void> Function(String accessToken) onAccessToken;

  const GoogleWebSignInButton({super.key, required this.onAccessToken});

  @override
  State<GoogleWebSignInButton> createState() => _GoogleWebSignInButtonState();
}

class _GoogleWebSignInButtonState extends State<GoogleWebSignInButton> {
  static const _clientId = String.fromEnvironment('GOOGLE_CLIENT_ID');
  static const _scopes = [
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/userinfo.profile',
  ];

  late final GoogleSignIn _googleSignIn;
  StreamSubscription<GoogleSignInAccount?>? _subscription;
  bool _ready = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _initialize();
  }

  void _initialize() {
    if (_clientId.isEmpty) {
      setState(() => _error = 'ยังไม่ได้ตั้งค่า GOOGLE_CLIENT_ID สำหรับ Web');
      return;
    }
    try {
      _googleSignIn = GoogleSignIn(clientId: _clientId, scopes: _scopes);
      _subscription = _googleSignIn.onCurrentUserChanged.listen(_onSignIn);
      // Do not call signInSilently here. Reopening the login screen after a
      // logout must wait for an explicit button click instead of immediately
      // authenticating the previously selected Google account again.
      if (mounted) setState(() => _ready = true);
    } on Object {
      if (mounted) setState(() => _error = 'ไม่สามารถเตรียม Google Login ได้');
    }
  }

  Future<void> _onSignIn(GoogleSignInAccount? account) async {
    if (account == null) return;
    try {
      final authentication = await account.authentication;
      final accessToken = authentication.accessToken;
      if (accessToken == null || accessToken.isEmpty) {
        throw StateError('Google did not return an access token');
      }
      await widget.onAccessToken(accessToken);
    } catch (_) {
      if (mounted) setState(() => _error = 'ไม่สามารถเข้าสู่ระบบด้วย Google ได้');
    }
  }

  @override
  void dispose() {
    _subscription?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_error != null) {
      return Text(
        _error!,
        style: TextStyle(color: Theme.of(context).colorScheme.error),
      );
    }
    if (!_ready) {
      return const SizedBox(
        height: 52,
        child: Center(child: CircularProgressIndicator()),
      );
    }
    return SizedBox(
      width: double.infinity,
      height: 52,
      child: LayoutBuilder(
        builder: (context, constraints) {
          // Google Identity Services limits its branded button to 400 px.
          final buttonWidth = constraints.maxWidth.clamp(0.0, 400.0).toDouble();
          return Center(
            child: SizedBox(
              width: buttonWidth,
              child: google_web.renderButton(
                configuration: google_web.GSIButtonConfiguration(
                  type: google_web.GSIButtonType.standard,
                  theme: google_web.GSIButtonTheme.outline,
                  size: google_web.GSIButtonSize.large,
                  shape: google_web.GSIButtonShape.pill,
                  logoAlignment: google_web.GSIButtonLogoAlignment.left,
                  minimumWidth: buttonWidth,
                  locale: 'th',
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
