import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/features/assessment/widgets/assessment_progress.dart';

void main() {
  testWidgets('shows the current assessment step and context', (tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: AssessmentProgress(
            currentStep: 2,
            title: 'เลือกอาการหลัก',
            description: 'เลือกหนึ่งอาการที่ต้องการประเมิน',
          ),
        ),
      ),
    );

    expect(find.text('ขั้นตอนที่ 2 จาก 3 · เลือกอาการ'), findsOneWidget);
    expect(find.text('เลือกอาการหลัก'), findsOneWidget);
    expect(find.text('เลือกหนึ่งอาการที่ต้องการประเมิน'), findsOneWidget);
  });
}
