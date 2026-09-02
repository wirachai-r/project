import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:mobile/data/services/central_http_client.dart' as http;
import '../../../shared/widgets/app_feedback.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../article/screens/article_detail_screen.dart';
import '../../disease/screens/disease_detail_screen.dart';
import '../../first_aid/screens/first_aid_detail_screen.dart';

class UnifiedSearchScreen extends StatefulWidget {
  const UnifiedSearchScreen({super.key});

  @override
  State<UnifiedSearchScreen> createState() => _UnifiedSearchScreenState();
}

class _UnifiedSearchScreenState extends State<UnifiedSearchScreen> {
  final _controller = TextEditingController();
  Timer? _debounce;
  bool _loading = false;
  String? _error;
  List<Map<String, dynamic>> _results = [];

  @override
  void dispose() {
    _debounce?.cancel();
    _controller.dispose();
    super.dispose();
  }

  void _changed(String value) {
    _debounce?.cancel();
    final query = value.trim();
    if (query.length < 2) {
      setState(() {
        _results = [];
        _error = null;
        _loading = false;
      });
      return;
    }
    _debounce = Timer(const Duration(milliseconds: 350), () => _search(query));
  }

  Future<void> _search(String query) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final uri = Uri.parse(
        '${ApiConstants.baseUrl}${ApiConstants.unifiedSearch}',
      ).replace(queryParameters: {'q': query, 'limit': '10'});
      final response = await http.get(
        uri,
        headers: {'Accept': 'application/json'},
      );
      if (response.statusCode != 200) throw Exception();
      final data = jsonDecode(response.body)['data'] as Map<String, dynamic>;
      final items = <Map<String, dynamic>>[];
      for (final key in ['diseases', 'articles', 'first_aids']) {
        items.addAll(
          (data[key] as List<dynamic>? ?? []).cast<Map<String, dynamic>>(),
        );
      }
      if (mounted && _controller.text.trim() == query) {
        setState(() => _results = items);
      }
    } catch (_) {
      if (mounted) setState(() => _error = 'ค้นหาไม่สำเร็จ กรุณาลองใหม่');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  ({String label, IconData icon, Color color}) _typeInfo(String? type) =>
      switch (type) {
        'disease' => (
          label: 'โรค',
          icon: Icons.medical_information_outlined,
          color: Colors.red,
        ),
        'symptom' => (
          label: 'อาการ',
          icon: Icons.sick_outlined,
          color: Colors.orange,
        ),
        'article' => (
          label: 'บทความ',
          icon: Icons.article_outlined,
          color: Colors.blue,
        ),
        'first_aid' => (
          label: 'ปฐมพยาบาล',
          icon: Icons.health_and_safety_outlined,
          color: Colors.green,
        ),
        _ => (label: 'ข้อมูล', icon: Icons.search, color: Colors.blueGrey),
      };

  String? _thumbnailUrl(Map<String, dynamic> item) {
    final value = item['thumbnail']?.toString().trim();
    if (value == null || value.isEmpty) return null;
    final uri = Uri.tryParse(value);
    if (uri != null && uri.hasScheme) return value;

    final apiUri = Uri.parse(ApiConstants.baseUrl);
    final origin = apiUri.replace(path: '', query: null, fragment: null);
    return origin
        .resolve(value.startsWith('/') ? value : '/api/media/$value')
        .toString();
  }

  void _open(Map<String, dynamic> item) {
    final id = item['id'].toString();
    final screen = switch (item['type']) {
      'disease' => DiseaseDetailScreen(diseaseId: id),
      'article' => ArticleDetailScreen(articleId: id),
      'first_aid' => FirstAidDetailScreen(firstAidId: id),
      _ => null,
    };
    if (screen != null) {
      Navigator.push(context, MaterialPageRoute(builder: (_) => screen));
      return;
    }
    showDialog<void>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text(item['title']?.toString() ?? 'อาการ'),
        content: Text(
          item['summary']?.toString().isNotEmpty == true
              ? item['summary'].toString()
              : 'ไม่มีรายละเอียดเพิ่มเติม',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('ปิด'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text('ค้นหาข้อมูลสุขภาพ', style: AppTextStyles.h4)),
    body: Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(16),
          child: SearchBar(
            controller: _controller,
            autoFocus: true,
            hintText: 'ค้นหาโรค บทความ หรือปฐมพยาบาล',
            textStyle: WidgetStatePropertyAll(AppTextStyles.body1),
            leading: const Icon(Icons.search),
            trailing: [
              if (_controller.text.isNotEmpty)
                IconButton(
                  tooltip: 'ล้างคำค้นหา',
                  onPressed: () {
                    _controller.clear();
                    _changed('');
                  },
                  icon: const Icon(Icons.close),
                ),
            ],
            onChanged: (value) {
              setState(() {});
              _changed(value);
            },
            onSubmitted: (value) {
              _debounce?.cancel();
              if (value.trim().length >= 2) _search(value.trim());
            },
          ),
        ),
        if (_loading)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 8),
            child: AppLoadingSpinner(),
          ),
        Expanded(child: _buildResults()),
      ],
    ),
  );

  Widget _buildResults() {
    if (_error != null) {
      return AppMessageView.error(
        message: _error!,
        onAction: () => _search(_controller.text.trim()),
      );
    }
    if (_controller.text.trim().length < 2) {
      return const AppMessageView(
        icon: Icons.manage_search_rounded,
        title: 'ค้นหาข้อมูลสุขภาพ',
        message: 'พิมพ์คำค้นหาอย่างน้อย 2 ตัวอักษร',
      );
    }
    if (!_loading && _results.isEmpty) {
      return const AppMessageView.empty(
        title: 'ไม่พบข้อมูลที่ค้นหา',
        message: 'ลองใช้คำที่สั้นลงหรือเปลี่ยนคำค้นหา',
      );
    }
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
      itemCount: _results.length,
      separatorBuilder: (_, _) => const Divider(height: 1),
      itemBuilder: (context, index) {
        final item = _results[index];
        final info = _typeInfo(item['type']?.toString());
        final thumbnailUrl = _thumbnailUrl(item);
        return ListTile(
          contentPadding: const EdgeInsets.symmetric(
            vertical: 6,
            horizontal: 4,
          ),
          leading: _SearchResultThumbnail(
            imageUrl: thumbnailUrl,
            icon: info.icon,
            color: info.color,
          ),
          title: Text(
            item['title']?.toString() ?? '-',
            style: AppTextStyles.body1Bold,
          ),
          subtitle: Text(
            '${info.label}${item['summary']?.toString().isNotEmpty == true ? ' · ${item['summary']}' : ''}',
            style: AppTextStyles.body2,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          trailing: const Icon(Icons.chevron_right),
          onTap: () => _open(item),
        );
      },
    );
  }
}

class _SearchResultThumbnail extends StatelessWidget {
  const _SearchResultThumbnail({
    required this.imageUrl,
    required this.icon,
    required this.color,
  });

  final String? imageUrl;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final fallback = ColoredBox(
      color: color.withValues(alpha: 0.12),
      child: Center(child: Icon(icon, color: color)),
    );

    return ClipRRect(
      borderRadius: BorderRadius.circular(10),
      child: SizedBox(
        width: 56,
        height: 56,
        child: imageUrl == null
            ? fallback
            : Image.network(
                imageUrl!,
                fit: BoxFit.cover,
                errorBuilder: (_, _, _) => fallback,
              ),
      ),
    );
  }
}
