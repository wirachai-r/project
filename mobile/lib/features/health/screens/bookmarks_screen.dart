import 'package:flutter/material.dart';
import '../../../shared/widgets/app_feedback.dart';
import '../../../shared/widgets/app_layout.dart';
import 'package:provider/provider.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/utils/responsive.dart';
import '../../../data/repositories/personal_health_repository.dart';
import '../../article/screens/article_detail_screen.dart';
import '../../disease/screens/disease_detail_screen.dart';
import '../../first_aid/screens/first_aid_detail_screen.dart';

class BookmarksScreen extends StatefulWidget {
  const BookmarksScreen({super.key});

  @override
  State<BookmarksScreen> createState() => _BookmarksScreenState();
}

class _BookmarksScreenState extends State<BookmarksScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;
  List<dynamic>? _items;
  String? _error;

  static const _diseaseType = 'App\\Models\\Disease';
  static const _articleType = 'App\\Models\\Article';
  static const _firstAidType = 'App\\Models\\FirstAid';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _load();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final value = await context.read<PersonalHealthRepository>().bookmarks();
      if (!mounted) return;
      setState(() {
        _items = value;
        _error = null;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _items ??= [];
        _error = 'ไม่สามารถโหลดรายการโปรดได้';
      });
    }
  }

  List<dynamic> _itemsOfType(String type) => (_items ?? [])
      .where((item) => item['bookmarkable_type'] == type)
      .toList();

  String _title(dynamic item) {
    final data = item['bookmarkable'] as Map? ?? const {};
    return (data['disease_name'] ??
            data['article_title'] ??
            data['first_aid_title'] ??
            data['title'] ??
            'รายการที่บันทึก')
        .toString();
  }

  String? _description(dynamic item) {
    final data = item['bookmarkable'] as Map? ?? const {};
    final value =
        data['short_description'] ??
        data['description'] ??
        data['summary'] ??
        data['symptom_description'];
    final text = value?.toString().replaceAll(RegExp(r'<[^>]*>'), '').trim();
    return text == null || text.isEmpty ? null : text;
  }

  String? _thumbnail(dynamic item) {
    final data = item['bookmarkable'] as Map? ?? const {};
    final value =
        data['thumbnail'] ??
        data['disease_image'] ??
        data['image_url'] ??
        data['image'];
    final path = value?.toString().trim();
    if (path == null || path.isEmpty) return null;
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    return '${ApiConstants.baseUrl}/media/${path.replaceFirst(RegExp(r'^/+'), '')}';
  }

  Future<void> _openItem(dynamic item) async {
    final id = item['bookmarkable_id']?.toString();
    if (id == null || id.isEmpty) return;
    final type = item['bookmarkable_type'];
    Widget? screen;
    if (type == _diseaseType) {
      screen = DiseaseDetailScreen(diseaseId: id);
    } else if (type == _articleType) {
      screen = ArticleDetailScreen(articleId: id);
    } else if (type == _firstAidType) {
      screen = FirstAidDetailScreen(firstAidId: id);
    }
    if (screen == null || !mounted) return;
    await Navigator.push(context, MaterialPageRoute(builder: (_) => screen!));
    await _load();
  }

  Future<void> _remove(dynamic item) async {
    try {
      await context.read<PersonalHealthRepository>().removeBookmark(item['id']);
      if (!mounted) return;
      setState(() => _items?.removeWhere((value) => value['id'] == item['id']));
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('นำออกจากรายการโปรดแล้ว')));
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('ไม่สามารถลบรายการโปรดได้'),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    Responsive.init(context);
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        centerTitle: true,
        title: Text('รายการโปรด', style: AppTextStyles.h4),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(61),
          child: Column(
            children: [
              Divider(
                height: 1,
                thickness: 1,
                color: Theme.of(context).colorScheme.outlineVariant,
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 6, 16, 6),
                child: Container(
                  height: 48,
                  padding: const EdgeInsets.all(3),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.surfaceContainer,
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: TabBar(
                    controller: _tabController,
                    labelColor: AppColors.primary,
                    unselectedLabelColor: Theme.of(
                      context,
                    ).colorScheme.onSurface,
                    labelStyle: AppTextStyles.body2Bold,
                    unselectedLabelStyle: AppTextStyles.body2,
                    indicatorSize: TabBarIndicatorSize.tab,
                    indicator: BoxDecoration(
                      color: AppColors.primaryLight,
                      borderRadius: BorderRadius.circular(13),
                    ),
                    dividerColor: Colors.transparent,
                    splashBorderRadius: BorderRadius.circular(13),
                    tabs: [
                      Tab(text: 'โรค ${_itemsOfType(_diseaseType).length}'),
                      Tab(text: 'บทความ ${_itemsOfType(_articleType).length}'),
                      Tab(
                        text: 'ปฐมพยาบาล ${_itemsOfType(_firstAidType).length}',
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_items == null) {
      return const AppLoadingView();
    }
    if (_error != null && _items!.isEmpty) {
      return AppMessageView.error(message: _error!, onAction: _load);
    }
    return AppContentWidth(
      child: TabBarView(
        controller: _tabController,
        children: [
          _BookmarkList(
            items: _itemsOfType(_diseaseType),
            emptyLabel: 'ยังไม่มีโรคในรายการโปรด',
            icon: Icons.medical_information_outlined,
            onRefresh: _load,
            onTap: _openItem,
            onRemove: _remove,
            titleOf: _title,
            descriptionOf: _description,
            thumbnailOf: _thumbnail,
          ),
          _BookmarkList(
            items: _itemsOfType(_articleType),
            emptyLabel: 'ยังไม่มีบทความในรายการโปรด',
            icon: Icons.article_outlined,
            onRefresh: _load,
            onTap: _openItem,
            onRemove: _remove,
            titleOf: _title,
            descriptionOf: _description,
            thumbnailOf: _thumbnail,
          ),
          _BookmarkList(
            items: _itemsOfType(_firstAidType),
            emptyLabel: 'ยังไม่มีปฐมพยาบาลในรายการโปรด',
            icon: Icons.health_and_safety_outlined,
            onRefresh: _load,
            onTap: _openItem,
            onRemove: _remove,
            titleOf: _title,
            descriptionOf: _description,
            thumbnailOf: _thumbnail,
          ),
        ],
      ),
    );
  }
}

class _BookmarkList extends StatelessWidget {
  final List<dynamic> items;
  final String emptyLabel;
  final IconData icon;
  final Future<void> Function() onRefresh;
  final Future<void> Function(dynamic item) onTap;
  final Future<void> Function(dynamic item) onRemove;
  final String Function(dynamic item) titleOf;
  final String? Function(dynamic item) descriptionOf;
  final String? Function(dynamic item) thumbnailOf;

  const _BookmarkList({
    required this.items,
    required this.emptyLabel,
    required this.icon,
    required this.onRefresh,
    required this.onTap,
    required this.onRemove,
    required this.titleOf,
    required this.descriptionOf,
    required this.thumbnailOf,
  });

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return LayoutBuilder(
        builder: (context, constraints) => RefreshIndicator(
          onRefresh: onRefresh,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: [
              SizedBox(
                height: constraints.maxHeight,
                child: Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 76,
                        height: 76,
                        decoration: const BoxDecoration(
                          color: AppColors.primaryLight,
                          shape: BoxShape.circle,
                        ),
                        child: Icon(icon, size: 36, color: AppColors.primary),
                      ),
                      const SizedBox(height: 18),
                      Text(
                        emptyLabel,
                        textAlign: TextAlign.center,
                        style: AppTextStyles.body1Bold,
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'กดไอคอนบันทึกบนเนื้อหาที่สนใจ แล้วกลับมาดูได้ที่นี่',
                        textAlign: TextAlign.center,
                        style: AppTextStyles.body2.copyWith(
                          color: Theme.of(
                            context,
                          ).colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }
    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: onRefresh,
      child: ListView.separated(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.symmetric(
          horizontal: Responsive.horizontalPadding,
          vertical: Responsive.dp(16),
        ),
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (context, index) {
          final item = items[index];
          final description = descriptionOf(item);
          final thumbnail = thumbnailOf(item);
          return Material(
            color: Theme.of(context).colorScheme.surface,
            elevation: 0,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(18),
              side: BorderSide(
                color: Theme.of(context).colorScheme.outlineVariant,
              ),
            ),
            child: InkWell(
              onTap: () => onTap(item),
              borderRadius: BorderRadius.circular(18),
              child: Padding(
                padding: const EdgeInsets.fromLTRB(10, 10, 6, 10),
                child: Row(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: SizedBox(
                        width: 72,
                        height: 72,
                        child: thumbnail == null
                            ? ColoredBox(
                                color: AppColors.primaryLight,
                                child: Icon(
                                  icon,
                                  color: AppColors.primary,
                                  size: 28,
                                ),
                              )
                            : Image.network(
                                thumbnail,
                                fit: BoxFit.cover,
                                errorBuilder: (_, _, _) => ColoredBox(
                                  color: AppColors.primaryLight,
                                  child: Icon(
                                    icon,
                                    color: AppColors.primary,
                                    size: 28,
                                  ),
                                ),
                              ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            titleOf(item),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: AppTextStyles.body1Bold,
                          ),
                          if (description != null) ...[
                            const SizedBox(height: 3),
                            Text(
                              description,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: AppTextStyles.body2.copyWith(
                                color: Theme.of(
                                  context,
                                ).colorScheme.onSurfaceVariant,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                    IconButton(
                      tooltip: 'นำออกจากรายการโปรด',
                      onPressed: () => onRemove(item),
                      icon: const Icon(
                        Icons.bookmark_remove_outlined,
                        color: AppColors.danger,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
