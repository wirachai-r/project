import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class FirstAidDetailScreen extends StatefulWidget {
  final String firstAidId;
  const FirstAidDetailScreen({super.key, required this.firstAidId});

  @override
  State<FirstAidDetailScreen> createState() => _FirstAidDetailScreenState();
}

class _FirstAidDetailScreenState extends State<FirstAidDetailScreen> {
  Map<String, dynamic>? _item;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await http.get(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.firstAids}/${widget.firstAidId}',
        ),
        headers: {'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        setState(() => _item = jsonDecode(res.body)['data']);
      } else {
        setState(() => _error = 'ไม่พบข้อมูล');
      }
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Scaffold(
              appBar: AppBar(),
              body: Center(child: Text(_error!)),
            )
          : _buildContent(),
    );
  }

  Widget _buildContent() {
    final item = _item!;
    return CustomScrollView(
      slivers: [
        SliverAppBar(
          expandedHeight: item['thumbnail'] != null ? 220 : 0,
          pinned: true,
          flexibleSpace: item['thumbnail'] != null
              ? FlexibleSpaceBar(
                  background: Image.network(
                    item['thumbnail'],
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) =>
                        Container(color: AppColors.surface),
                  ),
                )
              : null,
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (item['category'] != null)
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFEDD5),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      item['category']['category_name'],
                      style: AppTextStyles.body3.copyWith(
                        color: const Color(0xFFEA580C),
                      ),
                    ),
                  ),
                const SizedBox(height: 12),
                Text(item['title'], style: AppTextStyles.h2),
                const Divider(height: 24),
                Text(
                  item['content'] ?? '',
                  style: AppTextStyles.body1.copyWith(height: 1.8),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
