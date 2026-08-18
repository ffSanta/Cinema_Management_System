import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/movie.dart';
import '../models/showtime.dart';
import '../services/movie_service.dart';
import '../services/showtime_service.dart';
import '../theme/app_theme.dart';
import '../widgets/empty_state.dart';
import '../widgets/movie_card.dart';
import '../widgets/skeletons.dart';
import 'movie_detail_screen.dart';

/// หน้ารวมภาพยนตร์ — แท็บ กำลังฉาย/เร็ว ๆ นี้ + ค้นหาตามชื่อ + กรองตามโรงภาพยนตร์
class MoviesScreen extends StatefulWidget {
  const MoviesScreen({super.key});

  @override
  State<MoviesScreen> createState() => _MoviesScreenState();
}

class _MoviesScreenState extends State<MoviesScreen> {
  List<Movie>? _movies;
  List<Showtime>? _showtimes;
  Object? _error;

  String _search = '';
  int? _cinemaId; // null = ทุกโรง

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
      if (!mounted) return;
      setState(() {
        _movies = movies;
        _showtimes = showtimes;
        _error = null;
      });
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  // ===== ข้อมูลช่วยกรอง =====
  Set<int> get _nowShowingIds =>
      (_showtimes ?? []).map((s) => s.movieId).whereType<int>().toSet();

  /// รายชื่อโรง (id -> name) จากรอบฉายที่มี
  List<MapEntry<int, String>> get _cinemas {
    final map = <int, String>{};
    for (final s in _showtimes ?? []) {
      if (s.cinema != null) map[s.cinema!.id] = s.cinema!.name;
    }
    final list = map.entries.toList()
      ..sort((a, b) => a.value.compareTo(b.value));
    return list;
  }

  Set<int> _movieIdsAtCinema(int cinemaId) {
    final ids = <int>{};
    for (final s in _showtimes ?? []) {
      if (s.cinema?.id == cinemaId && s.movieId != null) ids.add(s.movieId!);
    }
    return ids;
  }

  bool _matchSearch(Movie m) =>
      _search.isEmpty ||
      m.title.toLowerCase().contains(_search.toLowerCase());

  List<Movie> get _nowShowing {
    var list = _movies!.where((m) => _nowShowingIds.contains(m.id));
    list = list.where(_matchSearch);
    if (_cinemaId != null) {
      final ids = _movieIdsAtCinema(_cinemaId!);
      list = list.where((m) => ids.contains(m.id));
    }
    return list.toList();
  }

  List<Movie> get _comingSoon {
    // เลือกโรงอยู่ → "เร็ว ๆ นี้" ไม่มีรอบฉายจึงไม่เข้าเงื่อนไขโรง
    if (_cinemaId != null) return [];
    return _movies!
        .where((m) => !_nowShowingIds.contains(m.id))
        .where(_matchSearch)
        .toList();
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('ภาพยนตร์'),
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
    if (_error != null) return ErrorState(message: '$_error', onRetry: _load);
    if (_movies == null) return const MovieGridSkeleton();
    return Column(
      children: [
        _filterBar(),
        Expanded(
          child: TabBarView(children: [
            _MovieGrid(
              movies: _nowShowing,
              emptyTitle: _emptyTitle('ยังไม่มีหนังที่กำลังฉาย'),
              onRefresh: _load,
            ),
            _MovieGrid(
              movies: _comingSoon,
              emptyTitle: _emptyTitle('ยังไม่มีหนังเข้าใหม่'),
              onRefresh: _load,
            ),
          ]),
        ),
      ],
    );
  }

  String _emptyTitle(String base) =>
      (_search.isNotEmpty || _cinemaId != null) ? 'ไม่พบภาพยนตร์ที่ค้นหา' : base;

  /// แถบค้นหา + ชิปกรองโรง
  Widget _filterBar() {
    final cinemas = _cinemas;
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 8),
      child: Column(
        children: [
          TextField(
            onChanged: (v) => setState(() => _search = v),
            textInputAction: TextInputAction.search,
            decoration: InputDecoration(
              hintText: 'ค้นหาชื่อภาพยนตร์',
              prefixIcon: const Icon(Icons.search),
              suffixIcon: _search.isEmpty
                  ? null
                  : IconButton(
                      icon: const Icon(Icons.clear),
                      onPressed: () => setState(() => _search = ''),
                    ),
              isDense: true,
            ),
          ),
          if (cinemas.isNotEmpty) ...[
            const SizedBox(height: 10),
            SizedBox(
              height: 36,
              child: ListView(
                scrollDirection: Axis.horizontal,
                children: [
                  _cinemaChip(null, 'ทุกโรง'),
                  ...cinemas.map((c) => _cinemaChip(c.key, c.value)),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _cinemaChip(int? id, String label) {
    final selected = _cinemaId == id;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(label),
        selected: selected,
        onSelected: (_) => setState(() => _cinemaId = id),
        showCheckmark: false,
        labelStyle: TextStyle(
          color: selected ? Colors.white : AppColors.ink,
          fontWeight: FontWeight.w600,
        ),
        selectedColor: AppColors.brand,
        backgroundColor: AppColors.brand050,
        side: BorderSide.none,
      ),
    );
  }
}

class _MovieGrid extends StatelessWidget {
  final List<Movie> movies;
  final String emptyTitle;
  final Future<void> Function() onRefresh;

  const _MovieGrid({
    required this.movies,
    required this.emptyTitle,
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
              subtitle: 'ดึงลงเพื่อรีเฟรช',
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
