import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:checkup/app.dart';
import 'package:checkup/data/services/api_service.dart';
import 'package:checkup/data/services/auth_service.dart';

void main() {
  testWidgets('App smoke test - renders without crashing', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(
      CheckupApp(apiService: ApiService(), authService: AuthService()),
    );
    await tester.pump(const Duration(milliseconds: 1600));
    await tester.pump();

    expect(find.byType(MaterialApp), findsOneWidget);
  });
}
