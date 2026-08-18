import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:youtube_player_iframe/youtube_player_iframe.dart';

import '../models/movie.dart';
import '../models/showtime.dart';
import '../services/movie_service.dart';
import '../theme/app_theme.dart';
import '../widgets/empty_state.dart';
import 'seat_booking_screen.dart';

class MovieDetailScreen extends StatefulWidget {
  final int movieId;
  const MovieDetailScreen({super.key, required this.movieId});

  @override
  State<MovieDetailScreen> createState() => _MovieDetailScreenState();
}

class _MovieDetailScreenState extends State<MovieDetailScreen> {
  Movie? _movie;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final m = await context.read<MovieService>().detail(widget.movieId);
      if (mounted) setState(() { _movie = m; _error = null; });
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('รายละเอียด')),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_error != null) {
      return ErrorState(message: '$_error', onRetry: _load);
    }
    if (_movie == null) {
      return const Center(child: CircularProgressIndicator());
    }
    final movie = _movie!;
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: Image.network(
                movie.posterUrl,
                width: 130,
                height: 195,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => Container(
                  width: 130,
                  height: 195,
                  color: const Color(0xFF2A2640),
                  child: const Icon(Icons.movie, color: Colors.white38),
                ),
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(movie.title,
                      style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: AppColors.ink)),
                  const SizedBox(height: 8),
                  Row(children: [
                    const Icon(Icons.schedule, size: 16, color: AppColors.muted),
                    const SizedBox(width: 4),
                    Text('${movie.durationMins} นาที',
                        style: const TextStyle(color: AppColors.muted)),
                  ]),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 20),
        _sectionTitle('เรื่องย่อ'),
        const SizedBox(height: 6),
        Text(movie.synopsis.isEmpty ? '-' : movie.synopsis,
            style: const TextStyle(height: 1.6, color: AppColors.ink)),
        const SizedBox(height: 24),
        _sectionTitle('รอบฉาย'),
        const SizedBox(height: 8),
        if (movie.showtimes.isEmpty)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 16),
            child: Text('ยังไม่มีรอบฉาย',
                style: TextStyle(color: AppColors.muted)),
          )
        else
          ..._groupByCinema(movie.showtimes).entries.map(
                (e) => _CinemaShowtimes(
                    cinemaName: e.key,
                    showtimes: e.value,
                    movieTitle: movie.title),
              ),
        if (movie.hasTrailer) ...[
          const SizedBox(height: 24),
          _sectionTitle('ตัวอย่างหนัง'),
          const SizedBox(height: 8),
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: _TrailerPlayer(videoId: movie.youtubeId!),
          ),
        ],
      ],
    );
  }

  Widget _sectionTitle(String text) => Text(text,
      style: const TextStyle(
          fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.ink));

  Map<String, List<Showtime>> _groupByCinema(List<Showtime> list) {
    final map = <String, List<Showtime>>{};
    for (final s in list) {
      final name = s.cinema?.name ?? 'โรงภาพยนตร์';
      map.putIfAbsent(name, () => []).add(s);
    }
    return map;
  }
}

/// ตัวเล่นตัวอย่างหนัง YouTube — บน web เรนเดอร์เป็น <iframe> จริง
class _TrailerPlayer extends StatefulWidget {
  final String videoId;
  const _TrailerPlayer({required this.videoId});

  @override
  State<_TrailerPlayer> createState() => _TrailerPlayerState();
}

class _TrailerPlayerState extends State<_TrailerPlayer> {
  late final YoutubePlayerController _controller;

  @override
  void initState() {
    super.initState();
    _controller = YoutubePlayerController.fromVideoId(
      videoId: widget.videoId,
      autoPlay: false,
      params: const YoutubePlayerParams(
        showControls: true,
        showFullscreenButton: true,
      ),
    );
  }

  @override
  void dispose() {
    _controller.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return YoutubePlayer(controller: _controller, aspectRatio: 16 / 9);
  }
}

class _CinemaShowtimes extends StatelessWidget {
  final String cinemaName;
  final List<Showtime> showtimes;
  final String movieTitle;

  const _CinemaShowtimes({
    required this.cinemaName,
    required this.showtimes,
    required this.movieTitle,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(children: [
              const Icon(Icons.location_on_outlined,
                  size: 18, color: AppColors.brand),
              const SizedBox(width: 6),
              Text(cinemaName,
                  style: const TextStyle(
                      fontWeight: FontWeight.bold, color: AppColors.ink)),
            ]),
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: showtimes.map((s) {
                return ActionChip(
                  avatar: const Icon(Icons.event_seat,
                      size: 16, color: AppColors.brand),
                  label: Text('${s.showTime}  (ว่าง ${s.availableSeats})'),
                  backgroundColor: AppColors.brand050,
                  side: BorderSide.none,
                  onPressed: () => Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => SeatBookingScreen(
                          showtime: s, movieTitle: movieTitle),
                    ),
                  ),
                );
              }).toList(),
            ),
          ],
        ),
      ),
    );
  }
}
