import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../data/repositories/personal_health_repository.dart';
import '../../features/auth/providers/auth_provider.dart';
import '../../core/theme/app_colors.dart';

class BookmarkButton extends StatefulWidget {
  final String type;
  final String itemId;
  final Color? selectedColor;
  final bool compact;
  final String? label;
  final TextStyle? labelStyle;
  const BookmarkButton({
    super.key,
    required this.type,
    required this.itemId,
    this.selectedColor,
    this.compact = false,
    this.label,
    this.labelStyle,
  });
  @override
  State<BookmarkButton> createState() => _BookmarkButtonState();
}

class _BookmarkButtonState extends State<BookmarkButton> {
  dynamic bookmarkId;
  bool busy = false;
  bool checking = false;
  bool _requestedCheck = false;

  Future<void> _check() async {
    // The callback is scheduled after a frame. Authentication may have become
    // invalid before it runs, so re-check it before calling a protected API.
    if (!mounted || !context.read<AuthProvider>().isAuthenticated) {
      if (mounted)
        setState(() {
          busy = false;
          checking = false;
        });
      return;
    }

    try {
      final items = await context.read<PersonalHealthRepository>().bookmarks();
      final found = items
          .where(
            (e) =>
                e['bookmarkable_type'] == widget.type &&
                '${e['bookmarkable_id']}' == widget.itemId,
          )
          .toList();
      if (mounted)
        setState(() {
          bookmarkId = found.isEmpty ? null : found.first['id'];
          busy = false;
          checking = false;
        });
    } catch (_) {
      if (mounted)
        setState(() {
          busy = false;
          checking = false;
        });
    }
  }

  Future<void> _toggle() async {
    if (!context.read<AuthProvider>().isAuthenticated) return;
    setState(() => busy = true);
    try {
      if (bookmarkId == null) {
        await context.read<PersonalHealthRepository>().addBookmark(
          widget.type,
          widget.itemId,
        );
      } else {
        await context.read<PersonalHealthRepository>().removeBookmark(
          bookmarkId,
        );
      }
      await _check();
      if (mounted)
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              bookmarkId == null
                  ? 'นำออกจากรายการโปรดแล้ว'
                  : 'บันทึกในรายการโปรดแล้ว',
            ),
          ),
        );
    } catch (_) {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isAuthenticated = context.watch<AuthProvider>().isAuthenticated;

    if (isAuthenticated && !_requestedCheck) {
      _requestedCheck = true;
      checking = true;
      WidgetsBinding.instance.addPostFrameCallback((_) => _check());
    } else if (!isAuthenticated) {
      _requestedCheck = false;
      bookmarkId = null;
      busy = false;
      checking = false;
    }

    final tooltip = bookmarkId == null
        ? 'บันทึกรายการโปรด'
        : 'นำออกจากรายการโปรด';
    final onPressed = busy || checking
        ? null
        : isAuthenticated
        ? _toggle
        : () => ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('กรุณาเข้าสู่ระบบเพื่อบันทึกรายการโปรด'),
            ),
          );
    final icon = busy
        ? const SizedBox.square(
            dimension: 18,
            child: CircularProgressIndicator(strokeWidth: 2),
          )
        : Icon(
            bookmarkId == null
                ? Icons.bookmark_border_rounded
                : Icons.bookmark_rounded,
            color: bookmarkId == null ? null : widget.selectedColor,
          );

    if (widget.compact && widget.label != null) {
      return Tooltip(
        message: tooltip,
        child: Material(
          color: bookmarkId == null
              ? Colors.transparent
              : (widget.selectedColor ?? AppColors.primary).withValues(
                  alpha: 0.1,
                ),
          borderRadius: BorderRadius.circular(14),
          child: InkWell(
            onTap: onPressed,
            borderRadius: BorderRadius.circular(14),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  SizedBox.square(dimension: 24, child: Center(child: icon)),
                  const SizedBox(height: 4),
                  Text(widget.label!, style: widget.labelStyle),
                ],
              ),
            ),
          ),
        ),
      );
    }

    return IconButton(
      tooltip: tooltip,
      onPressed: onPressed,
      padding: widget.compact ? EdgeInsets.zero : null,
      constraints: widget.compact
          ? const BoxConstraints.tightFor(width: 24, height: 24)
          : null,
      visualDensity: widget.compact ? VisualDensity.compact : null,
      style: widget.compact
          ? IconButton.styleFrom(
              minimumSize: const Size(24, 24),
              maximumSize: const Size(24, 24),
              padding: EdgeInsets.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
            )
          : null,
      icon: icon,
    );
  }
}
