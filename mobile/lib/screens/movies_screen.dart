import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/movie.dart';
import '../services/movie_service.dart';
import '../services/showtime_service.dart';
import '../widgets/movie_card.dart';
import 'movie_detail_screen.dart';

/// หน้ารวมภาพยนตร์ — แท็บ "กำลังฉาย" (มีรอบฉาย) / "เร็ว ๆ นี้" (ยังไม่มีรอบ)
class MoviesScreen extends StatefulWidget {
  const MoviesScreen({super.key});

  @override
  State<MoviesScreen> createState() => _MoviesScreenState();
}

class _MoviesScreenState extends State<MoviesScreen> {
  late Future<_MoviesData> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<_MoviesData> _load() async {
    final movieService = context.read<MovieService>();
    final showtimeService = context.read<ShowtimeService>();

    final movies = await movieService.list();
    final showtimes = await showtimeService.list();
    final nowShowingIds =
        showtimes.map((s) => s.movieId).whereType<int>().toSet();

    return _MoviesData(
      movies.where((m) => nowShowingIds.contains(m.id)).toList(),
      movies.where((m) => !nowShowingIds.contains(m.id)).toList(),
    );
  }

  Future<void> _refresh() async {
    final data = await _load();
    if (mounted) setState(() => _future = Future.value(data));
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('ภาพยนตร์'),
          bottom: const TabBar(tabs: [
            Tab(text: 'กำลังฉาย'),
            Tab(text: 'เร็ว ๆ นี้'),
          ]),
        ),
        body: FutureBuilder<_MoviesData>(
          future: _future,
          builder: (context, snap) {
            if (snap.connectionState == ConnectionState.waiting) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snap.hasError) {
              return _ErrorView(message: '${snap.error}', onRetry: _refresh);
            }
            final data = snap.data!;
            return TabBarView(children: [
              _MovieGrid(
                  movies: data.nowShowing,
                  emptyText: 'ยังไม่มีหนังที่กำลังฉาย',
                  onRefresh: _refresh),
              _MovieGrid(
                  movies: data.comingSoon,
                  emptyText: 'ยังไม่มีหนังเข้าใหม่',
                  onRefresh: _refresh),
            ]);
          },
        ),
      ),
    );
  }
}

class _MoviesData {
  final List<Movie> nowShowing;
  final List<Movie> comingSoon;
  _MoviesData(this.nowShowing, this.comingSoon);
}

class _MovieGrid extends StatelessWidget {
  final List<Movie> movies;
  final String emptyText;
  final Future<void> Function() onRefresh;

  const _MovieGrid({
    required this.movies,
    required this.emptyText,
    required this.onRefresh,
  });

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: onRefresh,
      child: movies.isEmpty
          ? ListView(children: [
              const SizedBox(height: 120),
              Center(
                  child: Text(emptyText,
                      style: const TextStyle(color: Colors.white54))),
            ])
          : GridView.builder(
              padding: const EdgeInsets.all(12),
              gridDelegate:
                  const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 3,
                childAspectRatio: 0.52,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
              ),
              itemCount: movies.length,
              itemBuilder: (_, i) => MovieCard(
                movie: movies[i],
                onTap: () => Navigator.of(context).push(MaterialPageRoute(
                    builder: (_) =>
                        MovieDetailScreen(movieId: movies[i].id))),
              ),
            ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final String message;
  final Future<void> Function() onRetry;
  const _ErrorView({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.cloud_off, size: 48, color: Colors.white38),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 32),
            child: Text(message, textAlign: TextAlign.center),
          ),
          const SizedBox(height: 12),
          FilledButton(onPressed: onRetry, child: const Text('ลองใหม่')),
        ],
      ),
    );
  }
}
