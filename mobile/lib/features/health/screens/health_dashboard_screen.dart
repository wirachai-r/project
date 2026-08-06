import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../data/repositories/personal_health_repository.dart';
import 'bookmarks_screen.dart';

class HealthDashboardScreen extends StatefulWidget {
  const HealthDashboardScreen({super.key});
  @override
  State<HealthDashboardScreen> createState() => _HealthDashboardScreenState();
}

class _HealthDashboardScreenState extends State<HealthDashboardScreen> {
  Map<String, dynamic>? data;
  String? error;
  @override
  void initState() { super.initState(); _load(); }
  Future<void> _load() async {
    try {
      final result = await context.read<PersonalHealthRepository>().dashboard();
      if (mounted) setState(() { data = result; error = null; });
    } catch (_) { if (mounted) setState(() => error = 'โหลดข้อมูลสุขภาพไม่สำเร็จ'); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F8F8),
      appBar: AppBar(title: const Text('สุขภาพของฉัน')),
      body: data == null
          ? Center(child: error == null ? const CircularProgressIndicator() : TextButton(onPressed: _load, child: Text(error!)))
          : RefreshIndicator(onRefresh: _load, child: _content()),
    );
  }

  Widget _content() {
    final summary = Map<String, dynamic>.from(data!['summary'] ?? {});
    final symptoms = List<dynamic>.from(data!['top_symptoms'] ?? []);
    final trend = List<dynamic>.from(data!['severity_trend'] ?? []);
    return ListView(padding: const EdgeInsets.all(16), children: [
      Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          gradient: const LinearGradient(colors: [Color(0xFF168F8B), Color(0xFF54BDB4)]),
          borderRadius: BorderRadius.circular(24),
        ),
        child: const Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Icon(Icons.favorite_rounded, color: Colors.white), SizedBox(height: 12),
          Text('ภาพรวมสุขภาพของคุณ', style: TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w700)),
          Text('สรุปจากข้อมูลที่คุณบันทึกไว้ในแอป', style: TextStyle(color: Colors.white70)),
        ]),
      ),
      const SizedBox(height: 14),
      Row(children: [
        _metric('ประเมินแล้ว', summary['assessment_count'] ?? 0, Icons.fact_check_outlined),
        const SizedBox(width: 10),
        _metric('ติดตามอาการ', summary['follow_up_count'] ?? 0, Icons.timeline_rounded),
      ]),
      const SizedBox(height: 10),
      Row(children: [
        _metric('ควรเฝ้าระวัง', summary['urgent_count'] ?? 0, Icons.health_and_safety_outlined),
        const SizedBox(width: 10),
        _metric('รายการโปรด', summary['bookmark_count'] ?? 0, Icons.bookmark_outline),
      ]),
      const SizedBox(height: 20),
      _heading('อาการที่พบเป็นประจำ'),
      if (symptoms.isEmpty) const _Empty(text: 'ยังไม่มีประวัติการประเมิน') else
        ...symptoms.map((item) => Card(child: ListTile(
          leading: const CircleAvatar(backgroundColor: Color(0xFFE2F4F2), child: Icon(Icons.monitor_heart_outlined, color: AppColors.primary)),
          title: Text(item['symptom_name'] ?? 'ไม่ระบุอาการ'), trailing: Text('${item['count']} ครั้ง'),
        ))),
      const SizedBox(height: 16),
      _heading('แนวโน้มความรุนแรงล่าสุด'),
      Card(child: Padding(padding: const EdgeInsets.all(16), child: trend.isEmpty
        ? const Text('เมื่อเริ่มบันทึกติดตามอาการ แนวโน้มจะแสดงที่นี่')
        : Row(crossAxisAlignment: CrossAxisAlignment.end, children: trend.map<Widget>((e) {
            final value = (e['severity'] as num?)?.toDouble() ?? 1;
            return Expanded(child: Padding(padding: const EdgeInsets.symmetric(horizontal: 2), child: Container(
              height: 12 + value * 8, decoration: BoxDecoration(color: value >= 7 ? Colors.orange : AppColors.primary, borderRadius: BorderRadius.circular(6)),
            )));
          }).toList()),
      )),
      const SizedBox(height: 14),
      OutlinedButton.icon(
        onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const BookmarksScreen())),
        icon: const Icon(Icons.bookmarks_outlined), label: const Text('เปิดรายการโปรด'),
      ),
    ]);
  }

  Widget _metric(String label, dynamic value, IconData icon) => Expanded(child: Container(
    padding: const EdgeInsets.all(16), decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18)),
    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Icon(icon, color: AppColors.primary), const SizedBox(height: 10), Text('$value', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold)), Text(label)]),
  ));
  Widget _heading(String text) => Padding(padding: const EdgeInsets.only(bottom: 8), child: Text(text, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)));
}

class _Empty extends StatelessWidget { final String text; const _Empty({required this.text}); @override Widget build(BuildContext context) => Card(child: Padding(padding: const EdgeInsets.all(20), child: Center(child: Text(text)))); }
