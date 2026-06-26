import 'package:flutter/material.dart';
import 'data/services/api_service.dart';
import 'data/services/auth_service.dart';
import 'app.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();

  final apiService = ApiService();
  final authService = AuthService();

  runApp(CheckupApp(apiService: apiService, authService: authService));
}
