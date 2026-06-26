import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class FacilityScreen extends StatefulWidget {
  const FacilityScreen({super.key});

  @override
  State<FacilityScreen> createState() => _FacilityScreenState();
}

class _FacilityScreenState extends State<FacilityScreen> {
  List<dynamic> _items = [];
  bool _isLoading = true;
  String? _error;
  String? _selectedType;
  final _searchCtrl = TextEditingController();

  final _types = [
    {'value': null, 'label': 'ทั้งหมด', 'icon': Icons.local_hospital},
    {'value': 'H',  'label': 'โรงพยาบาล', 'icon': Icons.local_hospital_outlined},
    {'value': 'C',  'label': 'คลินิก', 'icon': Icons.medical_services_outlined},
    {'value': 'P',  'label': 'ร้านยา', 'icon': Icons.local_pharmacy_outlined},
  ];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _isLoading = true; _error = null; });
    try {
      final uri = Uri.parse('${ApiConstants.baseUrl}${ApiConstants.facilities}').replace(queryParameters: {
        if (_selectedType != null) 'facility_type': _selectedType!,
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
      appBar: AppBar(title: const Text('สถานพยาบาล')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: TextField(
              controller: _searchCtrl,
              decoration: const InputDecoration(hintText: 'ค้นหาสถานพยาบาล...', prefixIcon: Icon(Icons.search)),
              onSubmitted: (_) => _load(),
            ),
          ),
          _buildTypeFilter(),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildTypeFilter() => SizedBox(
    height: 44,
    child: ListView.builder(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.symmetric(horizontal: 12),
      itemCount: _types.length,
      itemBuilder: (_, i) {
        final t = _types[i];
        final selected = _selectedType == t['value'];
        return Padding(
          padding: const EdgeInsets.only(right: 8),
          child: ChoiceChip(
            avatar: Icon(t['icon'] as IconData, size: 16,
              color: selected ? Colors.white : AppColors.textSecondary),
            label: Text(t['label'] as String, style: AppTextStyles.body3.copyWith(
              color: selected ? Colors.white : AppColors.textSecondary)),
            selected: selected,
            onSelected: (_) { setState(() => _selectedType = t['value'] as String?); _load(); },
            selectedColor: AppColors.primary,
            backgroundColor: AppColors.surface,
            side: BorderSide(color: selected ? AppColors.primary : AppColors.border),
          ),
        );
      },
    ),
  );

  Widget _buildBody() {
    if (_isLoading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return Center(child: Text(_error!));
    if (_items.isEmpty) return const Center(child: Text('ไม่พบสถานพยาบาล'));

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _items.length,
        itemBuilder: (_, i) => _FacilityCard(facility: _items[i]),
      ),
    );
  }
}

class _FacilityCard extends StatelessWidget {
  final dynamic facility;
  const _FacilityCard({required this.facility});

  IconData _iconFor(String? type) {
    switch (type) {
      case 'H': return Icons.local_hospital_outlined;
      case 'C': return Icons.medical_services_outlined;
      case 'P': return Icons.local_pharmacy_outlined;
      default:  return Icons.business_outlined;
    }
  }

  String _labelFor(String? type) {
    switch (type) {
      case 'H': return 'โรงพยาบาล';
      case 'C': return 'คลินิก';
      case 'P': return 'ร้านยา';
      default:  return 'สถานพยาบาล';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CircleAvatar(
              backgroundColor: AppColors.primaryLight,
              child: Icon(_iconFor(facility['facility_type']), color: AppColors.primary, size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(children: [
                    Expanded(child: Text(facility['facility_name'],
                      style: AppTextStyles.body1.copyWith(fontWeight: FontWeight.w600))),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: AppColors.primaryLight, borderRadius: BorderRadius.circular(4)),
                      child: Text(_labelFor(facility['facility_type']),
                        style: AppTextStyles.body3.copyWith(color: AppColors.primary)),
                    ),
                  ]),
                  if (facility['address'] != null) ...[
                    const SizedBox(height: 4),
                    Row(children: [
                      const Icon(Icons.location_on_outlined, size: 14, color: AppColors.textSecondary),
                      const SizedBox(width: 4),
                      Expanded(child: Text(
                        [facility['address'], facility['district'], facility['province']]
                          .where((s) => s != null).join(', '),
                        style: AppTextStyles.body3, maxLines: 2, overflow: TextOverflow.ellipsis,
                      )),
                    ]),
                  ],
                  if (facility['phone'] != null) ...[
                    const SizedBox(height: 4),
                    Row(children: [
                      const Icon(Icons.phone_outlined, size: 14, color: AppColors.textSecondary),
                      const SizedBox(width: 4),
                      Text(facility['phone'], style: AppTextStyles.body3),
                    ]),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
