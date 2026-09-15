// ignore: deprecated_member_use
import 'dart:html' as html;

Future<String?> savePdfFile(String filename, List<int> bytes) async {
  final blob = html.Blob([bytes], 'application/pdf');
  final url = html.Url.createObjectUrlFromBlob(blob);

  try {
    html.AnchorElement(href: url)
      ..download = filename
      ..click();
  } finally {
    html.Url.revokeObjectUrl(url);
  }
  return null;
}
