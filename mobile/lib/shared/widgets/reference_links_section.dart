import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';

class ReferenceLinksSection extends StatelessWidget {
  const ReferenceLinksSection({super.key, required this.links});

  final List<String> links;

  static List<String> fromJson(dynamic value) => value is List
      ? value
            .map((item) => item.toString().trim())
            .where((item) => item.isNotEmpty)
            .toList()
      : const [];

  static Uri? _webUri(String value) {
    final uri = Uri.tryParse(value.trim());
    return uri != null && (uri.scheme == 'http' || uri.scheme == 'https')
        ? uri
        : null;
  }

  @override
  Widget build(BuildContext context) {
    if (links.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Divider(height: 40),
        Text('แหล่งอ้างอิง', style: AppTextStyles.h4),
        const SizedBox(height: 10),
        for (final reference in links)
          Builder(
            builder: (context) {
              final uri = _webUri(reference);

              return ListTile(
                contentPadding: EdgeInsets.zero,
                minVerticalPadding: 4,
                leading: Icon(
                  uri == null ? Icons.menu_book_outlined : Icons.link_rounded,
                  color: AppColors.primary,
                ),
                title: Text(
                  reference,
                  style: AppTextStyles.body2.copyWith(
                    color: uri == null ? null : AppColors.primary,
                  ),
                ),
                onTap: uri == null
                    ? null
                    : () => launchUrl(
                        uri,
                        mode: LaunchMode.externalApplication,
                      ),
              );
            },
          ),
      ],
    );
  }
}
