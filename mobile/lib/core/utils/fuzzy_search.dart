/// Lightweight, dependency-free fuzzy matching for cached/offline content.
bool fuzzyContains(String text, String query) {
  final normalizedText = text.trim().toLowerCase();
  final normalizedQuery = query.trim().toLowerCase();
  if (normalizedQuery.isEmpty || normalizedText.contains(normalizedQuery)) {
    return true;
  }

  final characters = normalizedQuery.runes.toList();
  if (characters.length < 3 || characters.length > 32) return false;

  for (var index = 0; index < characters.length; index++) {
    final left = String.fromCharCodes(characters.sublist(0, index));
    final right = String.fromCharCodes(characters.sublist(index + 1));
    final leftIndex = normalizedText.indexOf(left);
    if (leftIndex >= 0 &&
        normalizedText.indexOf(right, leftIndex + left.length) >= 0) {
      return true;
    }
  }

  return false;
}
