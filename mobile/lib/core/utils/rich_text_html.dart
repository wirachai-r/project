import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';

import '../constants/api_constants.dart';

class RichTextHtml {
  const RichTextHtml._();

  static final RegExp _mediaSourcePattern = RegExp(
    r'''src\s*=\s*(["'])(?:https?://[^/"']+)?/(?:storage|api/media)/([^"']+)\1''',
    caseSensitive: false,
  );

  static String resolveMediaUrls(String html) {
    final apiUri = Uri.tryParse(ApiConstants.baseUrl);
    if (apiUri == null || !apiUri.hasScheme || apiUri.host.isEmpty) {
      return html;
    }

    final origin = Uri(
      scheme: apiUri.scheme,
      host: apiUri.host,
      port: apiUri.hasPort ? apiUri.port : null,
    ).toString().replaceFirst(RegExp(r'/$'), '');

    return html.replaceAllMapped(_mediaSourcePattern, (match) {
      final quote = match.group(1)!;
      final path = match.group(2)!;
      return 'src=$quote$origin/api/media/$path$quote';
    });
  }

  static List<HtmlExtension> get imageExtensions => [
    ImageExtension(
      builder: (context) {
        final source = context.attributes['src'];
        if (source == null || source.isEmpty) {
          return const SizedBox.shrink();
        }

        final widthPercent = _imageWidthPercent(context.attributes);
        final alignment = switch (context.attributes['data-align']) {
          'left' => Alignment.centerLeft,
          'right' => Alignment.centerRight,
          _ => Alignment.center,
        };

        return Align(
          alignment: alignment,
          child: FractionallySizedBox(
            widthFactor: widthPercent / 100,
            child: Image.network(
              source,
              fit: BoxFit.contain,
              webHtmlElementStrategy: WebHtmlElementStrategy.prefer,
              loadingBuilder: (context, child, progress) => progress == null
                  ? child
                  : const Center(child: CircularProgressIndicator()),
              errorBuilder: (context, error, stackTrace) => const Center(
                child: Icon(Icons.broken_image_outlined, size: 36),
              ),
            ),
          ),
        );
      },
    ),
  ];

  static double _imageWidthPercent(Map<String, String> attributes) {
    final dataWidth = double.tryParse(
      attributes['data-width-percent'] ?? '',
    );
    if (dataWidth != null) return dataWidth.clamp(1, 100).toDouble();

    final style = attributes['style'] ?? '';
    final match = RegExp(r'width\s*:\s*([\d.]+)%').firstMatch(style);
    final styleWidth = double.tryParse(match?.group(1) ?? '');
    return (styleWidth ?? 100).clamp(1, 100).toDouble();
  }
}
