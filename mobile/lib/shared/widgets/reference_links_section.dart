import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';

class ReferenceLinksSection extends StatelessWidget {
  const ReferenceLinksSection({super.key, required this.links});

  final List<String> links;

  static List<String> fromJson(dynamic value) => value is List
      ? value.map((item) => item.toString()).where((item) => item.isNotEmpty).toList()
      : const [];

  @override
  Widget build(BuildContext context) {
    if (links.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Divider(height: 40),
        Text('แหล่งอ้างอิง', style: AppTextStyles.h4),
        const SizedBox(height: 10),
        for (var index = 0; index < links.length; index++)
          ListTile(
            contentPadding: EdgeInsets.zero,
            minVerticalPadding: 4,
            leading: const Icon(Icons.link_rounded, color: AppColors.primary),
            title: Text(
              links[index],
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: AppTextStyles.body2.copyWith(color: AppColors.primary),
            ),
            onTap: () => launchUrl(
              Uri.parse(links[index]),
              mode: LaunchMode.externalApplication,
            ),
          ),
      ],
    );
  }
}
