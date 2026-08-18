import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/movie.dart';
import '../services/movie_service.dart';
import '../services/showtime_service.dart';
import '../theme/app_theme.dart';
import '../widgets/empty_state.dart';
import '../widgets/movie_card.dart';
import '../widgets/skeletons.dart';
import 'movie_detail_screen.dart';

/// หน้ารวมภาพยนตร์ — แท็บ "กำลังฉาย" (มีรอบฉาย) / "เร็ว ๆ นี้" (ยังไม่มีรอบ)
class MoviesScreen extends StatefulWidget {
  const MoviesScreen({super.key});

  @override
  State<MoviesScreen> createState() => _MoviesScreenState();
}

class _MoviesScreenState extends State<MoviesScreen> {
  _MoviesData? _data;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final movieService = context.read<MovieService>();
      final showtimeService = context.read<ShowtimeService>();
      final movies = await movieService.list();
      final showtimes = await showtimeService.list();
      final nowShowingIds =
          showtimes.map((s) => s.movieId).whereType<int>().toSet();
      if (!mounted) return;
      setState(() {
        _data = _MoviesData(
          movies.where((m) => nowShowingIds.contains(m.id)).toList(),
          movies.where((m) => !nowShowingIds.contains(m.id)).toList(),
        );
        _error = null;
      });
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('ภาพยนตร์'),
          // แถบแท็บใช้พื้นลาเวนเดอร์อ่อน ให้ต่างโทนกับแถบ title สีเข้มด้านบน
          bottom: PreferredSize(
            preferredSize: const Size.fromHeight(48),
            child: Container(
              color: AppColors.brand050,
              child: const TabBar(
                indicatorColor: AppColors.brand,
                indicatorWeight: 3,
                labelColor: AppColors.brand,
                unselectedLabelColor: AppColors.muted,
                labelStyle: TextStyle(fontWeight: FontWeight.w700),
                tabs: [
                  Tab(text: 'กำลังฉาย'),
                  Tab(text: 'เร็ว ๆ นี้'),
                ],
              ),
            ),
          ),
        ),
        body: _buildBody(),
      ),
    );
  }

  Widget _buildBody() {
    if (_error != null) {
      return ErrorState(message: '$_error', onRetry: _load);
    }
    if (_data == null) {
      return const MovieGridSkeleton();
    }
    return TabBarView(children: [
      _MovieGrid(
        movies: _data!.nowShowing,
        emptyTitle: 'ยังไม่มีหนังที่กำลังฉาย',
        emptySubtitle: 'ดึงลงเพื่อรีเฟรช',
        onRefresh: _load,
      ),
      _MovieGrid(
        movies: _data!.comingSoon,
        emptyTitle: 'ยังไม่มีหนังเข้าใหม่',
        emptySubtitle: 'ดึงลงเพื่อรีเฟรช',
        onRefresh: _load,
      ),
    ]);
  }
}

class _MoviesData {
  final List<Movie> nowShowing;
  final List<Movie> comingSoon;
  _MoviesData(this.nowShowing, this.comingSoon);
}

class _MovieGrid extends StatelessWidget {
  final List<Movie> movies;
  final String emptyTitle;
  final String emptySubtitle;
  final Future<void> Function() onRefresh;

  const _MovieGrid({
    required this.movies,
    required this.emptyTitle,
    required this.emptySubtitle,
    required this.onRefresh,
  });

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: onRefresh,
      child: movies.isEmpty
          ? EmptyState(
              icon: Icons.movie_creation_outlined,
              title: emptyTitle,
              subtitle: emptySubtitle,
            )
          : GridView.builder(
              padding: const EdgeInsets.all(12),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 3,
                childAspectRatio: 0.52,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
              ),
              itemCount: movies.length,
              itemBuilder: (_, i) => MovieCard(
                movie: movies[i],
                onTap: () => Navigator.of(context).push(MaterialPageRoute(
                    builder: (_) => MovieDetailScreen(movieId: movies[i].id))),
              ),
            ),
    );
  }
}
