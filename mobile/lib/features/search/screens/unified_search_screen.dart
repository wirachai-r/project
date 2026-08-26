import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../../../shared/widgets/app_feedback.dart';

import '../../../core/constants/api_constants.dart';
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
      for (final key in ['diseases', 'symptoms', 'articles', 'first_aids']) {
        items.addAll(
          (data[key] as List<dynamic>? ?? []).cast<Map<String, dynamic>>(),
        );
      }
      if (mounted && _controller.text.trim() == query)
        setState(() => _results = items);
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
    appBar: AppBar(title: const Text('ค้นหาข้อมูลสุขภาพ')),
    body: Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(16),
          child: SearchBar(
            controller: _controller,
            autoFocus: true,
            hintText: 'ค้นหาโรค อาการ บทความ หรือปฐมพยาบาล',
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
    if (_error != null) return Center(child: Text(_error!));
    if (_controller.text.trim().length < 2)
      return const Center(child: Text('พิมพ์คำค้นหาอย่างน้อย 2 ตัวอักษร'));
    if (!_loading && _results.isEmpty)
      return const Center(child: Text('ไม่พบข้อมูลที่ค้นหา'));
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
      itemCount: _results.length,
      separatorBuilder: (_, __) => const Divider(height: 1),
      itemBuilder: (context, index) {
        final item = _results[index];
        final info = _typeInfo(item['type']?.toString());
        return ListTile(
          contentPadding: const EdgeInsets.symmetric(
            vertical: 6,
            horizontal: 4,
          ),
          leading: CircleAvatar(
            backgroundColor: info.color.withValues(alpha: 0.12),
            child: Icon(info.icon, color: info.color),
          ),
          title: Text(item['title']?.toString() ?? '-'),
          subtitle: Text(
            '${info.label}${item['summary']?.toString().isNotEmpty == true ? ' · ${item['summary']}' : ''}',
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
