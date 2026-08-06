import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../data/repositories/personal_health_repository.dart';
import '../../features/auth/providers/auth_provider.dart';

class BookmarkButton extends StatefulWidget {
  final String type; final String itemId;
  const BookmarkButton({super.key, required this.type, required this.itemId});
  @override State<BookmarkButton> createState() => _BookmarkButtonState();
}
class _BookmarkButtonState extends State<BookmarkButton> {
  dynamic bookmarkId;
  bool busy = false;
  bool _requestedCheck = false;

  Future<void> _check() async {
    // The callback is scheduled after a frame. Authentication may have become
    // invalid before it runs, so re-check it before calling a protected API.
    if (!mounted || !context.read<AuthProvider>().isAuthenticated) {
      if (mounted) setState(() => busy = false);
      return;
    }

    try {
      final items = await context.read<PersonalHealthRepository>().bookmarks();
      final found = items.where((e) => e['bookmarkable_type'] == widget.type && '${e['bookmarkable_id']}' == widget.itemId).toList();
      if (mounted) setState(() { bookmarkId = found.isEmpty ? null : found.first['id']; busy = false; });
    } catch (_) { if (mounted) setState(() => busy = false); }
  }
  Future<void> _toggle() async {
    if (!context.read<AuthProvider>().isAuthenticated) return;
    setState(() => busy = true);
    try {
      if (bookmarkId == null) { await context.read<PersonalHealthRepository>().addBookmark(widget.type, widget.itemId); }
      else { await context.read<PersonalHealthRepository>().removeBookmark(bookmarkId); }
      await _check();
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(bookmarkId == null ? 'นำออกจากรายการโปรดแล้ว' : 'บันทึกในรายการโปรดแล้ว')));
    } catch (_) { if (mounted) setState(() => busy = false); }
  }
  @override
  Widget build(BuildContext context) {
    final isAuthenticated = context.watch<AuthProvider>().isAuthenticated;

    if (isAuthenticated && !_requestedCheck) {
      _requestedCheck = true;
      busy = true;
      WidgetsBinding.instance.addPostFrameCallback((_) => _check());
    } else if (!isAuthenticated) {
      _requestedCheck = false;
      bookmarkId = null;
      busy = false;
    }

    return IconButton(
      tooltip: bookmarkId == null ? 'บันทึกรายการโปรด' : 'นำออกจากรายการโปรด',
      onPressed: busy
          ? null
          : isAuthenticated
              ? _toggle
              : () => ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('กรุณาเข้าสู่ระบบเพื่อบันทึกรายการโปรด'),
                    ),
                  ),
      icon: busy
          ? const SizedBox.square(
              dimension: 18,
              child: CircularProgressIndicator(strokeWidth: 2),
            )
          : Icon(
              bookmarkId == null
                  ? Icons.bookmark_border_rounded
                  : Icons.bookmark_rounded,
            ),
    );
  }
}
