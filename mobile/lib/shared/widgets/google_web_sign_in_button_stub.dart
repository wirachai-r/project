import 'package:flutter/widgets.dart';

class GoogleWebSignInButton extends StatelessWidget {
  final Future<void> Function(String accessToken) onAccessToken;

  const GoogleWebSignInButton({super.key, required this.onAccessToken});

  @override
  Widget build(BuildContext context) => const SizedBox.shrink();
}
