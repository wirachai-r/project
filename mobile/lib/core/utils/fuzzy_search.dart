import 'package:characters/characters.dart';

const _protectedShortWords = <String>{'ไม่'};

String normalizeSearchText(String value) => value
    .replaceAll(RegExp(r'[\u200B-\u200D\u2060\uFEFF]', unicode: true), '')
    .trim()
    .replaceAll(RegExp(r'\s+', unicode: true), ' ')
    .toLowerCase();

int _threshold(int length) {
  if (length <= 1) return 0;
  if (length <= 5) return 1;
  if (length <= 10) return 2;
  return (length * 0.2).floor().clamp(2, length).toInt();
}

int _damerauLevenshtein(List<String> left, List<String> right, int maximum) {
  if ((left.length - right.length).abs() > maximum) return maximum + 1;
  List<int>? previousPrevious;
  var previous = List<int>.generate(right.length + 1, (index) => index);
  for (var i = 1; i <= left.length; i++) {
    final current = <int>[i];
    var rowMinimum = i;
    for (var j = 1; j <= right.length; j++) {
      final cost = left[i - 1] == right[j - 1] ? 0 : 1;
      current.add(
        <int>[
          current[j - 1] + 1,
          previous[j] + 1,
          previous[j - 1] + cost,
        ].reduce((a, b) => a < b ? a : b),
      );
      if (previousPrevious != null &&
          i > 1 &&
          j > 1 &&
          left[i - 1] == right[j - 2] &&
          left[i - 2] == right[j - 1]) {
        final transposed = previousPrevious[j - 2] + 1;
        if (transposed < current[j]) current[j] = transposed;
      }
      if (current[j] < rowMinimum) rowMinimum = current[j];
    }
    if (rowMinimum > maximum) return maximum + 1;
    previousPrevious = previous;
    previous = current;
  }
  return previous[right.length];
}

bool _approximatelyContains(String value, String query) {
  final haystack = normalizeSearchText(value).characters.toList();
  final needle = normalizeSearchText(query).characters.toList();
  final maximum = _threshold(needle.length);
  if (needle.length == 1) {
    return haystack.any(
      (candidate) => candidate.runes.first == needle.first.runes.first,
    );
  }
  if (needle.length > 64) return false;
  final minimumLength = (needle.length - maximum)
      .clamp(1, needle.length)
      .toInt();
  final maximumLength = needle.length + maximum;
  for (var length = minimumLength; length <= maximumLength; length++) {
    for (var start = 0; start + length <= haystack.length; start++) {
      if (needle.length == 2 &&
          (length != needle.length ||
              _protectedShortWords.contains(
                haystack.sublist(start, start + length).join(),
              ))) {
        continue;
      }
      if (_damerauLevenshtein(
            haystack.sublist(start, start + length),
            needle,
            maximum,
          ) <=
          maximum) {
        return true;
      }
    }
  }
  return false;
}

List<String> _searchTerms(String query) {
  final terms = <String>[];
  final pattern = RegExp(r'''["“”']([^"“”']+)["“”']|([^\s]+)''', unicode: true);

  for (final match in pattern.allMatches(query)) {
    final term = normalizeSearchText(match.group(1) ?? match.group(2) ?? '');
    if (term.isNotEmpty) terms.add(term);
  }

  return terms;
}

bool _matchesTerm(String text, List<String> tokens, String term) {
  if (text.contains(term)) return true;
  if (term.characters.length <= 1) return false;

  return tokens.any((token) => _approximatelyContains(token, term)) ||
      _approximatelyContains(text, term);
}

/// Thai-aware local fallback for cached/offline content.
bool fuzzyContains(String text, String query) {
  final normalizedText = normalizeSearchText(text);
  final normalizedQuery = normalizeSearchText(query);
  if (normalizedQuery.isEmpty || normalizedText.contains(normalizedQuery))
    return true;

  final tokens = normalizedText
      .split(' ')
      .where((token) => token.isNotEmpty)
      .toList();
  final isThaiWithSpaces = RegExp(
    r'^[\u0E00-\u0E7F\s]+$',
    unicode: true,
  ).hasMatch(normalizedQuery);
  final compactThaiQuery = isThaiWithSpaces
      ? normalizedQuery.replaceAll(RegExp(r'\s+', unicode: true), '')
      : normalizedQuery;
  if (compactThaiQuery != normalizedQuery &&
      (normalizedText.contains(compactThaiQuery) ||
          _matchesTerm(normalizedText, tokens, compactThaiQuery))) {
    return true;
  }
  final terms = _searchTerms(normalizedQuery);

  return terms.isNotEmpty &&
      terms.every((term) => _matchesTerm(normalizedText, tokens, term));
}
