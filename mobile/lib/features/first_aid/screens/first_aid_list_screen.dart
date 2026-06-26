import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import 'first_aid_detail_screen.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class FirstAidListScreen extends StatefulWidget {
  const FirstAidListScreen({super.key});

  @override
  State<FirstAidListScreen> createState() => _FirstAidListScreenState();
}

class _FirstAidListScreenState extends State<FirstAidListScreen> {
  List<dynamic> _items = [];
  List<dynamic> _categories = [];
  String? _selectedCategoryId;
  bool _isLoading = true;
  String? _error;
  final _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadCategories();
    _load();
  }

  Future<void> _loadCategories() async {
    final res = await http.get(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.firstAidCategories}'),
      headers: {'Accept': 'application/json'},
    );
    if (res.statusCode == 200) {
      setState(() => _categories = jsonDecode(res.body)['data'] ?? []);
    }
  }

  Future<void> _load() async {
    setState(() { _isLoading = true; _error = null; });
    try {
      final uri = Uri.parse('${ApiConstants.baseUrl}${ApiConstants.firstAids}').replace(queryParameters: {
        if (_selectedCategoryId != null) 'first_aid_category_id': _selectedCategoryId!,
        if (_searchCtrl.text.isNotEmpty) 'search': _searchCtrl.text,
      });
      final res = await http.get(uri, headers: {'Accept': 'application/json'});
      if (res.statusCode == 200) {
        setState(() => _items = jsonDecode(res.body)['data'] ?? []);
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
      appBar: AppBar(title: const Text('ปฐมพยาบาล')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: TextField(
              controller: _searchCtrl,
              decoration: const InputDecoration(hintText: 'ค้นหา...', prefixIcon: Icon(Icons.search)),
              onSubmitted: (_) => _load(),
            ),
          ),
          if (_categories.isNotEmpty) SizedBox(
            height: 44,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              itemCount: _categories.length + 1,
              itemBuilder: (_, i) {
                if (i == 0) return _chip(null, 'ทั้งหมด');
                return _chip(_categories[i-1]['first_aid_category_id'], _categories[i-1]['category_name']);
              },
            ),
          ),
          Expanded(
            child: _isLoading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
                ? Center(child: Text(_error!))
                : _items.isEmpty
                  ? const Center(child: Text('ไม่พบข้อมูล'))
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: GridView.builder(
                        padding: const EdgeInsets.all(16),
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2, childAspectRatio: 0.85, crossAxisSpacing: 12, mainAxisSpacing: 12,
                        ),
                        itemCount: _items.length,
                        itemBuilder: (_, i) => _FirstAidCard(item: _items[i]),
                      ),
                    ),
          ),
        ],
      ),
    );
  }

  Widget _chip(String? id, String label) {
    final selected = _selectedCategoryId == id;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: FilterChip(
        label: Text(label, style: AppTextStyles.body3.copyWith(
          color: selected ? Colors.white : AppColors.textSecondary)),
        selected: selected,
        onSelected: (_) { setState(() => _selectedCategoryId = id); _load(); },
        backgroundColor: AppColors.surface,
        selectedColor: AppColors.primary,
        checkmarkColor: Colors.white,
        side: BorderSide(color: selected ? AppColors.primary : AppColors.border),
      ),
    );
  }
}

class _FirstAidCard extends StatelessWidget {
  final dynamic item;
  const _FirstAidCard({required this.item});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: () => Navigator.push(context, MaterialPageRoute(
        builder: (_) => FirstAidDetailScreen(firstAidId: item['first_aid_id']),
      )),
      child: Container(
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: AppColors.border),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: ClipRRect(
                borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
                child: item['thumbnail'] != null
                  ? Image.network(item['thumbnail'], width: double.infinity, fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => _placeholder())
                  : _placeholder(),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(item['title'], style: AppTextStyles.body2.copyWith(
                    fontWeight: FontWeight.w600, color: AppColors.textPrimary),
                    maxLines: 2, overflow: TextOverflow.ellipsis),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _placeholder() => Container(
    color: AppColors.primaryLight,
    child: const Center(child: Icon(Icons.medical_services_outlined, color: AppColors.primary, size: 40)),
  );
}
