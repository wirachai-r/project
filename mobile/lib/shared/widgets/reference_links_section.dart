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

              return Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Material(
                  color: Theme.of(context).colorScheme.surface,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                    side: BorderSide(
                      color: Theme.of(context).colorScheme.outlineVariant,
                    ),
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: ListTile(
                    minTileHeight: 56,
                    leading: Container(
                      width: 38,
                      height: 38,
                      decoration: BoxDecoration(
                        color: AppColors.primaryLight,
                        borderRadius: BorderRadius.circular(11),
                      ),
                      child: Icon(
                        uri == null
                            ? Icons.menu_book_outlined
                            : Icons.link_rounded,
                        color: AppColors.primary,
                        size: 20,
                      ),
                    ),
                    title: Text(
                      reference,
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                      style: AppTextStyles.body2.copyWith(
                        color: uri == null ? null : AppColors.primary,
                      ),
                    ),
                    trailing: uri == null
                        ? null
                        : const Icon(Icons.open_in_new_rounded, size: 18),
                    onTap: uri == null
                        ? null
                        : () async {
                            final opened = await launchUrl(
                              uri,
                              mode: LaunchMode.externalApplication,
                            );
                            if (!opened && context.mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('ไม่สามารถเปิดลิงก์นี้ได้'),
                                ),
                              );
                            }
                          },
                  ),
                ),
              );
            },
          ),
      ],
    );
  }
}
