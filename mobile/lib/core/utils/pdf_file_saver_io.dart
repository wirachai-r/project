import 'dart:io';

import 'package:path_provider/path_provider.dart';

Future<void> savePdfFile(String filename, List<int> bytes) async {
  try {
    final downloads = await getDownloadsDirectory();
    if (downloads != null) {
      final file = File(
        '${downloads.path}${Platform.pathSeparator}$filename',
      );
      await file.writeAsBytes(bytes, flush: true);
      return;
    }
  } catch (_) {
    // Some mobile platforms do not expose a writable Downloads directory.
  }

  final documents = await getApplicationDocumentsDirectory();
  final file = File('${documents.path}${Platform.pathSeparator}$filename');
  await file.writeAsBytes(bytes, flush: true);
}
