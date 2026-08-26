/// Lightweight, dependency-free fuzzy matching for cached/offline content.
bool fuzzyContains(String text, String query) {
  final normalizedText = text.trim().toLowerCase();
  final normalizedQuery = query.trim().toLowerCase();
  if (normalizedQuery.isEmpty || normalizedText.contains(normalizedQuery)) {
    return true;
  }

  final queryRunes = normalizedQuery.runes.toList();
  if (queryRunes.length < 3) return false;

  return normalizedText.split(RegExp(r'\s+')).any((word) {
    final wordRunes = word.runes.toList();
    final lengthDifference = (wordRunes.length - queryRunes.length).abs();
    if (lengthDifference > 1) return false;
    return _editDistanceWithinOne(wordRunes, queryRunes);
  });
}

bool _editDistanceWithinOne(List<int> left, List<int> right) {
  var leftIndex = 0;
  var rightIndex = 0;
  var edits = 0;

  while (leftIndex < left.length && rightIndex < right.length) {
    if (left[leftIndex] == right[rightIndex]) {
      leftIndex++;
      rightIndex++;
      continue;
    }

    if (++edits > 1) return false;
    if (left.length > right.length) {
      leftIndex++;
    } else if (right.length > left.length) {
      rightIndex++;
    } else {
      leftIndex++;
      rightIndex++;
    }
  }

  edits += (left.length - leftIndex) + (right.length - rightIndex);
  return edits <= 1;
}
