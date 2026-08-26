import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/app.dart';
import 'package:mobile/data/services/api_service.dart';
import 'package:mobile/data/services/auth_service.dart';

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
