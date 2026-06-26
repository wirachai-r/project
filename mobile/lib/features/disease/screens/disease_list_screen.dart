import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import 'disease_detail_screen.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class DiseaseListScreen extends StatefulWidget {
  const DiseaseListScreen({super.key});

  @override
  State<DiseaseListScreen> createState() => _DiseaseListScreenState();
}

class _DiseaseListScreenState extends State<DiseaseListScreen> {
  List<dynamic> _diseases = [];
  List<dynamic> _categories = [];
  String? _selectedCategoryId;
  bool _isLoading = true;
  String? _error;
  final _searchCtrl = TextEditingController();
  int _page = 1;
  bool _hasMore = true;
  bool _loadingMore = false;
  final _scrollCtrl = ScrollController();

  @override
  void initState() {
    super.initState();
    _loadCategories();
    _loadDiseases(refresh: true);
    _scrollCtrl.addListener(_onScroll);
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollCtrl.position.pixels >= _scrollCtrl.position.maxScrollExtent - 200) {
      if (!_loadingMore && _hasMore) _loadDiseases();
    }
  }

  Future<void> _loadCategories() async {
    final res = await http.get(
      Uri.parse('${ApiConstants.baseUrl}${ApiConstants.diseaseCategories}'),
      headers: {'Accept': 'application/json'},
    );
    if (res.statusCode == 200) {
      setState(() => _categories = jsonDecode(res.body)['data'] ?? []);
    }
  }

  Future<void> _loadDiseases({bool refresh = false}) async {
    if (refresh) {
      setState(() { _page = 1; _hasMore = true; _isLoading = true; _error = null; });
    } else {
      setState(() => _loadingMore = true);
    }

    try {
      final uri = Uri.parse('${ApiConstants.baseUrl}${ApiConstants.diseases}').replace(queryParameters: {
        'page': '$_page',
        if (_selectedCategoryId != null) 'disease_category_id': _selectedCategoryId!,
        if (_searchCtrl.text.isNotEmpty) 'search': _searchCtrl.text,
      });

      final res = await http.get(uri, headers: {'Accept': 'application/json'});
      final data = jsonDecode(res.body);
      final items = data['data'] as List? ?? [];

      setState(() {
        if (refresh) _diseases = items; else _diseases.addAll(items);
        _hasMore = data['meta']?['current_page'] < (data['meta']?['last_page'] ?? 1);
        _page++;
      });
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      setState(() { _isLoading = false; _loadingMore = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('ฐานข้อมูลโรค')),
      body: Column(
        children: [
          _buildSearchBar(),
          _buildCategories(),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildSearchBar() => Padding(
    padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
    child: TextField(
      controller: _searchCtrl,
      decoration: const InputDecoration(
        hintText: 'ค้นหาโรค...',
        prefixIcon: Icon(Icons.search),
      ),
      onSubmitted: (_) => _loadDiseases(refresh: true),
    ),
  );

  Widget _buildCategories() {
    if (_categories.isEmpty) return const SizedBox.shrink();
    return SizedBox(
      height: 44,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        itemCount: _categories.length + 1,
        itemBuilder: (_, i) {
          if (i == 0) return _catChip(null, 'ทั้งหมด');
          final cat = _categories[i - 1];
          return _catChip(cat['disease_category_id'], cat['category_name']);
        },
      ),
    );
  }

  Widget _catChip(String? id, String label) {
    final selected = _selectedCategoryId == id;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: FilterChip(
        label: Text(label, style: AppTextStyles.body3.copyWith(
          color: selected ? Colors.white : AppColors.textSecondary,
        )),
        selected: selected,
        onSelected: (_) {
          setState(() => _selectedCategoryId = id);
          _loadDiseases(refresh: true);
        },
        backgroundColor: AppColors.surface,
        selectedColor: AppColors.primary,
        checkmarkColor: Colors.white,
        side: BorderSide(color: selected ? AppColors.primary : AppColors.border),
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return Center(child: Text(_error!, style: AppTextStyles.body2));
    if (_diseases.isEmpty) return const Center(child: Text('ไม่พบข้อมูลโรค'));

    return RefreshIndicator(
      onRefresh: () => _loadDiseases(refresh: true),
      child: ListView.builder(
        controller: _scrollCtrl,
        padding: const EdgeInsets.all(16),
        itemCount: _diseases.length + (_loadingMore ? 1 : 0),
        itemBuilder: (_, i) {
          if (i == _diseases.length) return const Center(child: Padding(
            padding: EdgeInsets.all(16), child: CircularProgressIndicator(),
          ));
          return _DiseaseCard(disease: _diseases[i]);
        },
      ),
    );
  }
}

class _DiseaseCard extends StatelessWidget {
  final dynamic disease;
  const _DiseaseCard({required this.disease});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: AppColors.primaryLight,
          child: disease['disease_image'] != null
            ? ClipOval(child: Image.network(disease['disease_image'], fit: BoxFit.cover))
            : const Icon(Icons.medical_information_outlined, color: AppColors.primary, size: 20),
        ),
        title: Text(disease['disease_name'], style: AppTextStyles.body1.copyWith(fontWeight: FontWeight.w600)),
        subtitle: disease['category'] != null
          ? Text(disease['category']['category_name'], style: AppTextStyles.body3)
          : null,
        trailing: const Icon(Icons.chevron_right, color: AppColors.textSecondary),
        onTap: () => Navigator.push(context, MaterialPageRoute(
          builder: (_) => DiseaseDetailScreen(diseaseId: disease['disease_id']),
        )),
      ),
    );
  }
}
