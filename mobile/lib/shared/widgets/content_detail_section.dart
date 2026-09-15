import 'package:flutter/material.dart';

import '../../core/theme/app_text_styles.dart';

abstract final class ContentDetailSpacing {
  static const double horizontalPadding = 20;
  static const double headerTopPadding = 22;
}

class ContentDetailMetaItem {
  final IconData icon;
  final String label;

  const ContentDetailMetaItem({required this.icon, required this.label});
}

class ContentDetailMeta extends StatelessWidget {
  final List<ContentDetailMetaItem> items;

  const ContentDetailMeta({super.key, required this.items});

  @override
  Widget build(BuildContext context) {
    final color = Theme.of(context).colorScheme.onSurfaceVariant;

    return Wrap(
      spacing: 16,
      runSpacing: 8,
      children: items
          .map(
            (item) => Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(item.icon, size: 16, color: color),
                const SizedBox(width: 6),
                Text(
                  item.label,
                  style: AppTextStyles.body3.copyWith(color: color),
                ),
              ],
            ),
          )
          .toList(growable: false),
    );
  }
}

class ContentDetailBodyCard extends StatelessWidget {
  final Widget child;

  const ContentDetailBodyCard({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
      child: child,
    );
  }
}
