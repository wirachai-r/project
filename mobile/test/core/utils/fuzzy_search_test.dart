import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/core/utils/fuzzy_search.dart';

void main() {
  test('matches exact and contains searches', () {
    expect(fuzzyContains('แผลไฟไหม้ น้ำร้อนลวก', 'ไฟไหม้'), isTrue);
  });

  test('tolerates one missing, inserted, or substituted character', () {
    expect(fuzzyContains('กระดูกหัก', 'กระดกหัก'), isTrue);
    expect(fuzzyContains('กระดูกหัก', 'กระดูกกหัก'), isTrue);
    expect(fuzzyContains('กระดูกหัก', 'กระดูกพัก'), isTrue);
  });

  test('does not fuzzy match short or unrelated queries', () {
    expect(fuzzyContains('กระดูกหัก', 'กะ'), isFalse);
    expect(fuzzyContains('กระดูกหัก', 'ไข้หวัด'), isFalse);
  });
}
