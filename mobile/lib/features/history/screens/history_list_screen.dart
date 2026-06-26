import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import 'history_detail_screen.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class HistoryListScreen extends StatefulWidget {
  final String token;
  const HistoryListScreen({super.key, required this.token});

  @override
  State<HistoryListScreen> createState() => _HistoryListScreenState();
}

class _HistoryListScreenState extends State<HistoryListScreen> {
  List<dynamic> _items = [];
  bool _isLoading = true;
  String? _error;
  int _page = 1;
  bool _hasMore = true;
  bool _loadingMore = false;
  final _scrollCtrl = ScrollController();

  @override
  void initState() {
    super.initState();
    _load(refresh: true);
    _scrollCtrl.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollCtrl.position.pixels >=
        _scrollCtrl.position.maxScrollExtent - 200) {
      if (!_loadingMore && _hasMore) _load();
    }
  }

  Future<void> _load({bool refresh = false}) async {
    if (refresh) {
      setState(() {
        _page = 1;
        _hasMore = true;
        _isLoading = true;
        _error = null;
      });
    } else {
      setState(() => _loadingMore = true);
    }

    try {
      final res = await http.get(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.assessments}/history?page=$_page',
        ),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer ${widget.token}',
        },
      );
      final data = jsonDecode(res.body);
      final items = data['data'] as List? ?? [];
      setState(() {
        if (refresh)
          _items = items;
        else
          _items.addAll(items);
        _hasMore =
            data['meta']?['current_page'] < (data['meta']?['last_page'] ?? 1);
        _page++;
      });
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      setState(() {
        _isLoading = false;
        _loadingMore = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('ประวัติการประเมิน')),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(child: Text(_error!))
          : _items.isEmpty
          ? const Center(child: Text('ยังไม่มีประวัติการประเมิน'))
          : RefreshIndicator(
              onRefresh: () => _load(refresh: true),
              child: ListView.builder(
                controller: _scrollCtrl,
                padding: const EdgeInsets.all(16),
                itemCount: _items.length + (_loadingMore ? 1 : 0),
                itemBuilder: (_, i) {
                  if (i == _items.length)
                    return const Center(
                      child: Padding(
                        padding: EdgeInsets.all(16),
                        child: CircularProgressIndicator(),
                      ),
                    );
                  return _HistoryCard(item: _items[i], token: widget.token);
                },
              ),
            ),
    );
  }
}

class _HistoryCard extends StatelessWidget {
  final dynamic item;
  final String token;
  const _HistoryCard({required this.item, required this.token});

  @override
  Widget build(BuildContext context) {
    final status = item['assessment_status'];
    final topResult = (item['results'] as List? ?? []).isNotEmpty
        ? item['results'][0]
        : null;
    final urgencyLevel = topResult?['urgency_level'];
    final urgencyColor = urgencyLevel != null
        ? AppColors.urgencyColor(urgencyLevel)
        : AppColors.textSecondary;

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: urgencyColor.withOpacity(0.15),
          child: Icon(Icons.assignment_outlined, color: urgencyColor, size: 20),
        ),
        title: Text(
          item['symptom']?['symptom_name'] ?? 'ไม่ทราบอาการ',
          style: AppTextStyles.body1.copyWith(fontWeight: FontWeight.w600),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (urgencyLevel != null)
              Text(
                AppColors.urgencyLabel(urgencyLevel),
                style: AppTextStyles.body3.copyWith(color: urgencyColor),
              ),
            Text(item['created_at'] ?? '', style: AppTextStyles.body3),
          ],
        ),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
          decoration: BoxDecoration(
            color: status == 'C'
                ? AppColors.success.withOpacity(0.1)
                : AppColors.warning.withOpacity(0.1),
            borderRadius: BorderRadius.circular(20),
          ),
          child: Text(
            status == 'C' ? 'เสร็จสิ้น' : 'กำลังดำเนินการ',
            style: AppTextStyles.body3.copyWith(
              color: status == 'C' ? AppColors.success : AppColors.warning,
            ),
          ),
        ),
        onTap: () => Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) =>
                HistoryDetailScreen(assessmentId: item['id'], token: token),
          ),
        ),
      ),
    );
  }
}
