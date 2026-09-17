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

  StreamSubscription<GoogleSignInAuthenticationEvent>? _subscription;
  bool _ready = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _initialize();
  }

  Future<void> _initialize() async {
    if (_clientId.isEmpty) {
      setState(() => _error = 'ยังไม่ได้ตั้งค่า GOOGLE_CLIENT_ID สำหรับ Web');
      return;
    }
    try {
      await GoogleSignIn.instance.initialize(clientId: _clientId);
      _subscription = GoogleSignIn.instance.authenticationEvents.listen(
        _onAuthenticationEvent,
      );
      if (mounted) setState(() => _ready = true);
    } on Object {
      if (mounted) setState(() => _error = 'ไม่สามารถเตรียม Google Login ได้');
    }
  }

  Future<void> _onAuthenticationEvent(
    GoogleSignInAuthenticationEvent event,
  ) async {
    if (event is! GoogleSignInAuthenticationEventSignIn) return;
    try {
      final authorization =
          await event.user.authorizationClient.authorizationForScopes(_scopes) ??
          await event.user.authorizationClient.authorizeScopes(_scopes);
      await widget.onAccessToken(authorization.accessToken);
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
