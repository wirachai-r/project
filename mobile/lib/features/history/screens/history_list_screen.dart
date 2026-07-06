import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../data/models/history_model.dart';
import '../providers/history_provider.dart';
import 'history_detail_screen.dart';

class HistoryListScreen extends StatefulWidget {
  const HistoryListScreen({super.key});

  @override
  State<HistoryListScreen> createState() => _HistoryListScreenState();
}

class _HistoryListScreenState extends State<HistoryListScreen> {
  final _scrollCtrl = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<HistoryProvider>().load(refresh: true);
    });
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
      context.read<HistoryProvider>().load();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.white,
      appBar: AppBar(
        backgroundColor: AppColors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        // ✅ 1. แก้ไขให้ใช้ Font AppTextStyles.h4 และจัดกึ่งกลาง
        title: Text('ประวัติการประเมิน', style: AppTextStyles.h4),
        centerTitle: true,
        // ✅ 2. เพิ่มเส้น Divider ที่ด้านบนใต้ AppBar ตามที่คุณต้องการ
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(0.5),
          child: Divider(height: 0.5, thickness: 0.5, color: AppColors.border),
        ),
      ),
      body: Consumer<HistoryProvider>(
        builder: (context, provider, _) {
          if (provider.isLoading && provider.items.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }
          if (provider.error != null && provider.items.isEmpty) {
            return Center(
              child: Text(provider.error!, style: AppTextStyles.body1),
            );
          }
          if (provider.isEmpty) {
            return Center(
              child: Text(
                'ยังไม่มีประวัติการประเมิน',
                style: AppTextStyles.body1,
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () =>
                context.read<HistoryProvider>().load(refresh: true),
            child: ListView.builder(
              controller: _scrollCtrl,
              padding: const EdgeInsets.all(16),
              itemCount:
                  provider.items.length + (provider.isLoadingMore ? 1 : 0),
              itemBuilder: (_, i) {
                if (i == provider.items.length) {
                  return const Center(
                    child: Padding(
                      padding: EdgeInsets.all(16),
                      child: CircularProgressIndicator(),
                    ),
                  );
                }
                return _HistoryCard(item: provider.items[i]);
              },
            ),
          );
        },
      ),
    );
  }
}

class _HistoryCard extends StatelessWidget {
  final HistoryItemModel item;
  const _HistoryCard({required this.item});

  @override
  Widget build(BuildContext context) {
    final topResult = item.topResult;
    final urgencyColor = topResult != null
        ? AppColors.urgencyColor(topResult.urgencyLevel)
        : AppColors.textSecondary;

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      elevation: 0, // ปรับให้เข้ากับธีมแบนเรียบแบบมีเส้นแบ่ง
      shape: RoundedRectangleBorder(
        side: BorderSide(color: AppColors.border, width: 0.5),
        borderRadius: BorderRadius.circular(12),
      ),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: urgencyColor.withOpacity(0.15),
          child: Icon(Icons.assignment_outlined, color: urgencyColor, size: 20),
        ),
        title: Text(
          item.symptomName,
          style: AppTextStyles.body1Bold, // ✅ ปรับใช้ฟอนต์แบบหนา (Prompt)
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (topResult != null) ...[
              const SizedBox(height: 4),
              Text(
                AppColors.urgencyLabel(topResult.urgencyLevel),
                style: AppTextStyles.body3Bold.copyWith(
                  color: urgencyColor,
                ), // ✅ ปรับใช้ตัวหนา
              ),
            ],
            const SizedBox(height: 2),
            Text(
              item.createdAt,
              style: AppTextStyles.body3.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          decoration: BoxDecoration(
            color: item.isCompleted
                ? AppColors.success.withOpacity(0.1)
                : AppColors.warning.withOpacity(0.1),
            borderRadius: BorderRadius.circular(20),
          ),
          child: Text(
            item.isCompleted ? 'เสร็จสิ้น' : 'กำลังดำเนินการ',
            style: AppTextStyles.body3Bold.copyWith(
              // ✅ ปรับฟอนต์สถานะให้คมชัดขึ้น
              color: item.isCompleted ? AppColors.success : AppColors.warning,
            ),
          ),
        ),
        onTap: () => Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => HistoryDetailScreen(assessmentId: item.id),
          ),
        ),
      ),
    );
  }
}
