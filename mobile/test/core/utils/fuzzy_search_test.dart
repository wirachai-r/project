import 'package:flutter_test/flutter_test.dart';
import 'package:checkup/core/utils/fuzzy_search.dart';

void main() {
  test('matches exact and contains searches', () {
    expect(fuzzyContains('แผลไฟไหม้ น้ำร้อนลวก', 'ไฟไหม้'), isTrue);
  });

  test('tolerates one missing, inserted, or substituted character', () {
    expect(fuzzyContains('กระดูกหัก', 'กระดกหัก'), isTrue);
    expect(fuzzyContains('กระดูกหัก', 'กระดูกกหัก'), isTrue);
    expect(fuzzyContains('กระดูกหัก', 'กระดูกพัก'), isTrue);
  });

  test('matches a short Thai query with a missing vowel', () {
    expect(fuzzyContains('ถุง', 'ถง'), isTrue);
  });

  test('matches transposed characters', () {
    expect(fuzzyContains('Migraine', 'Migriane'), isTrue);
  });

  test('matches every space-separated term in any order', () {
    expect(fuzzyContains('high fever and headache', 'head fever'), isTrue);
    expect(fuzzyContains('high fever and headache', 'fever rash'), isFalse);
  });

  test('normalizes repeated whitespace and invisible characters', () {
    expect(fuzzyContains('high fever', '  high   fever  '), isTrue);
    expect(fuzzyContains('high fever', 'high\u200B fever'), isTrue);
  });

  test('keeps quoted text as one phrase', () {
    expect(fuzzyContains('severe high fever', '"high fever"'), isTrue);
    expect(fuzzyContains('high persistent fever', '"high fever"'), isFalse);
  });

  test('does not fuzzy match short or unrelated queries', () {
    expect(fuzzyContains('กระดูกหัก', 'กะ'), isFalse);
    expect(fuzzyContains('กระดูกหัก', 'ไข้หวัด'), isFalse);
  });
}
