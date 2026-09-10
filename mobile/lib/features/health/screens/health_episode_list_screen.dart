import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/thai_date_formatter.dart';
import '../../../data/models/health_episode_model.dart';
import '../../../data/repositories/personal_health_repository.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../core/utils/responsive.dart';
import '../../../shared/widgets/app_layout.dart';
import 'follow_up_screen.dart';

class HealthEpisodeListScreen extends StatefulWidget {
  const HealthEpisodeListScreen({super.key});

  @override
  State<HealthEpisodeListScreen> createState() =>
      _HealthEpisodeListScreenState();
}

class _HealthEpisodeListScreenState extends State<HealthEpisodeListScreen> {
  List<HealthEpisodeModel> _episodes = const [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final episodes = await context
          .read<PersonalHealthRepository>()
          .healthEpisodes();
      if (!mounted) return;
      setState(() => _episodes = episodes);
    } catch (_) {
      if (mounted) setState(() => _error = 'ไม่สามารถโหลดรายการติดตามได้');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: Theme.of(context).scaffoldBackgroundColor,
    appBar: AppBar(
      centerTitle: true,
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      surfaceTintColor: Colors.transparent,
      title: Text('การติดตามอาการทั้งหมด', style: AppTextStyles.h4),
      bottom: PreferredSize(
        preferredSize: Size.fromHeight(1),
        child: Divider(
          height: 1,
          color: Theme.of(context).colorScheme.outlineVariant,
        ),
      ),
    ),
    body: _loading
        ? const AppLoadingView(label: 'กำลังโหลดการติดตาม...')
        : _error != null
        ? AppMessageView.error(message: _error!, onAction: _load)
        : _episodes.isEmpty
        ? const AppMessageView.empty(
            title: 'ยังไม่มีการติดตามอาการ',
            message:
                'เริ่มติดตามได้จากผลประเมินที่บันทึกไว้ในหน้าประวัติ แล้วกลับมาดูรายการทั้งหมดที่หน้านี้',
          )
        : RefreshIndicator(
            onRefresh: _load,
            child: ResponsiveBuilder(
              builder: (context) => AppContentWidth(
                child: ListView(
                  padding: EdgeInsets.fromLTRB(
                    Responsive.horizontalPadding,
                    12,
                    Responsive.horizontalPadding,
                    32,
                  ),
                  children: [
                    _section('กำลังติดตาม', 'A'),
                    _section('หยุดชั่วคราว', 'P'),
                    _section('สิ้นสุดแล้ว', 'E'),
                  ],
                ),
              ),
            ),
          ),
  );

  Widget _section(String title, String status) {
    final items = _episodes.where((item) => item.status == status).toList();
    if (items.isEmpty) return const SizedBox.shrink();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(2, 8, 2, 10),
          child: Text('$title (${items.length})', style: AppTextStyles.h3),
        ),
        ...items.map(_episodeCard),
        const SizedBox(height: 14),
      ],
    );
  }

  Widget _episodeCard(HealthEpisodeModel episode) {
    final primary = episode.symptoms.where((item) => item.isPrimary);
    final name = primary.isNotEmpty
        ? primary.first.symptomName
        : episode.symptoms.map((item) => item.symptomName).join(', ');
    final entries = episode.symptoms.expand((item) => item.entries).toList()
      ..sort((a, b) => b.recordedAt.compareTo(a.recordedAt));
    final startedAt = episode.startedAt.toLocal();
    final latest = entries.isEmpty ? null : entries.first.recordedAt.toLocal();
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 54,
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surfaceContainerLow,
            borderRadius: BorderRadius.circular(16),
          ),
          child: Column(
            children: [
              Text(
                startedAt.day.toString().padLeft(2, '0'),
                style: AppTextStyles.body1Bold.copyWith(
                  color: AppColors.primary,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                thaiAbbreviatedMonths[startedAt.month - 1],
                style: AppTextStyles.body3Bold.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Container(
            margin: const EdgeInsets.only(bottom: 10),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(
                color: Theme.of(context).colorScheme.outlineVariant,
              ),
            ),
            child: Material(
              color: Colors.transparent,
              child: InkWell(
                borderRadius: BorderRadius.circular(18),
                onTap: () async {
                        await Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => FollowUpScreen(
                              episodeId: episode.id,
                              symptomName: name,
                            ),
                          ),
                        );
                        if (mounted) _load();
                      },
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              name.isEmpty ? 'อาการที่ติดตาม' : name,
                              style: AppTextStyles.body1Bold,
                            ),
                          ),
                          const Icon(Icons.chevron_right_rounded),
                        ],
                      ),
                      const SizedBox(height: 14),
                      Row(
                        children: [
                          Icon(
                            Icons.schedule_rounded,
                            size: 14,
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurfaceVariant,
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              latest == null
                                  ? _trackingDayLabel(episode)
                                  : 'ล่าสุด ${formatThaiDateTime(latest)} · ${_trackingDayLabel(episode)}',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: AppTextStyles.body3.copyWith(
                                color: Theme.of(
                                  context,
                                ).colorScheme.onSurfaceVariant,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }

  String _trackingDayLabel(HealthEpisodeModel episode) {
    final end = episode.endedAt ?? DateTime.now();
    final startDate = DateTime(
      episode.startedAt.toLocal().year,
      episode.startedAt.toLocal().month,
      episode.startedAt.toLocal().day,
    );
    final endDate = DateTime(
      end.toLocal().year,
      end.toLocal().month,
      end.toLocal().day,
    );
    return 'วันที่ ${endDate.difference(startDate).inDays + 1}';
  }
}
