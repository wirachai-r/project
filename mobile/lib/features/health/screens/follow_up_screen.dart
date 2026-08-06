import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../data/repositories/personal_health_repository.dart';

class FollowUpScreen extends StatefulWidget {
  final dynamic assessmentId; final String symptomName;
  const FollowUpScreen({super.key, required this.assessmentId, required this.symptomName});
  @override State<FollowUpScreen> createState() => _FollowUpScreenState();
}
class _FollowUpScreenState extends State<FollowUpScreen> {
  double severity = 5; final temp = TextEditingController(); final note = TextEditingController(); bool saving = false;
  @override void dispose() { temp.dispose(); note.dispose(); super.dispose(); }
  Future<void> _save() async {
    setState(() => saving = true);
    try { await context.read<PersonalHealthRepository>().addFollowUp(widget.assessmentId, severity: severity.round(), temperature: double.tryParse(temp.text), note: note.text); if (!mounted) return; Navigator.pop(context, true); }
    finally { if (mounted) setState(() => saving = false); }
  }
  @override Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('ติดตามอาการ')),
    body: ListView(padding: const EdgeInsets.all(20), children: [
      Text(widget.symptomName, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
      const Text('บันทึกสิ่งที่คุณรู้สึกในวันนี้ เพื่อดูแนวโน้มการเปลี่ยนแปลง'), const SizedBox(height: 28),
      Text('ความรุนแรง ${severity.round()}/10', style: const TextStyle(fontWeight: FontWeight.w700)),
      Slider(value: severity, min: 1, max: 10, divisions: 9, label: '${severity.round()}', onChanged: (v) => setState(() => severity = v)),
      TextField(controller: temp, keyboardType: const TextInputType.numberWithOptions(decimal: true), decoration: const InputDecoration(labelText: 'อุณหภูมิร่างกาย (°C) — ไม่บังคับ', prefixIcon: Icon(Icons.thermostat_outlined))),
      const SizedBox(height: 14),
      TextField(controller: note, minLines: 4, maxLines: 7, decoration: const InputDecoration(labelText: 'วันนี้เป็นอย่างไรบ้าง', hintText: 'เช่น ไอลดลง แต่ยังมีไข้ช่วงเย็น', alignLabelWithHint: true)),
      const SizedBox(height: 24),
      ElevatedButton.icon(onPressed: saving ? null : _save, icon: const Icon(Icons.check), label: Text(saving ? 'กำลังบันทึก...' : 'บันทึกอาการวันนี้')),
      const SizedBox(height: 12), const Text('หากอาการรุนแรงขึ้น หายใจลำบาก หรือหมดสติ ให้โทร 1669 ทันที', style: TextStyle(color: Colors.red)),
    ]),
  );
}
