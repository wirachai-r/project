import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import 'package:provider/provider.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../data/models/assessment_model.dart';
import '../../../data/repositories/assessment_repository.dart';
import '../../assessment/screens/assessment_result_screen.dart';

class HistoryDetailScreen extends StatefulWidget {
  final dynamic assessmentId;

  const HistoryDetailScreen({super.key, required this.assessmentId});

  @override
  State<HistoryDetailScreen> createState() => _HistoryDetailScreenState();
}

class _HistoryDetailScreenState extends State<HistoryDetailScreen> {
  AssessmentModel? _assessment;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (mounted) {
      setState(() {
        _isLoading = true;
        _error = null;
      });
    }

    try {
      final assessment = await context.read<AssessmentRepository>().getDetail(
        widget.assessmentId,
      );
      if (!mounted) return;
      setState(() => _assessment = assessment);
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = 'ไม่สามารถโหลดผลการประเมินได้');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        backgroundColor: AppColors.background,
        body: const AppLoadingView(),
      );
    }

    final assessment = _assessment;
    if (_error != null || assessment == null) {
      return Scaffold(
        backgroundColor: AppColors.background,
        appBar: AppBar(
          backgroundColor: AppColors.white,
          elevation: 0,
          surfaceTintColor: Colors.transparent,
          bottom: PreferredSize(
            preferredSize: const Size.fromHeight(0.5),
            child: Divider(
              height: 0.5,
              thickness: 0.5,
              color: AppColors.border,
            ),
          ),
        ),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text(
              _error ?? 'ไม่พบข้อมูลการประเมิน',
              style: AppTextStyles.body1,
              textAlign: TextAlign.center,
            ),
          ),
        ),
      );
    }

    return AssessmentResultScreen(
      assessmentId: assessment.id,
      results: assessment.results,
      symptomName: assessment.symptomName ?? '',
      isHistory: true,
    );
  }
}
