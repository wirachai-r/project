import 'package:flutter_test/flutter_test.dart';
import 'package:checkup/core/utils/thai_date_formatter.dart';

void main() {
  test('formats Thai abbreviated month and Buddhist year', () {
    expect(formatThaiDate(DateTime(2026, 7, 8)), '8 ก.ค. 2569');
    expect(formatThaiDate(DateTime(2026, 8, 18)), '18 ส.ค. 2569');
  });

  test('formats time with a two-digit clock', () {
    expect(
      formatThaiDateTime(DateTime(2026, 8, 18, 9, 5)),
      '18 ส.ค. 2569 09:05 น.',
    );
  });
}
