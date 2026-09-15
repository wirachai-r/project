import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:checkup/core/utils/buddhist_calendar_delegate.dart';

void main() {
  const delegate = BuddhistCalendarDelegate();
  const localizations = DefaultMaterialLocalizations();

  test('shows Buddhist year without changing DateTime values', () {
    final date = DateTime(2026, 9, 8);

    expect(delegate.formatYear(date.year, localizations), '2569');
    expect(delegate.formatMonthYear(date, localizations), contains('2569'));
    expect(delegate.dateOnly(date).year, 2026);
  });

  test('parses Buddhist compact year back to Gregorian DateTime', () {
    final date = DateTime(2026, 9, 8);
    final text = delegate.formatCompactDate(date, localizations);
    final parsed = delegate.parseCompactDate(text, localizations);

    expect(parsed, DateTime(2026, 9, 8));
  });
}
