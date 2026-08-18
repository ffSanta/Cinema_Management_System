import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:youtube_player_iframe/youtube_player_iframe.dart';

import '../models/movie.dart';
import '../models/showtime.dart';
import '../services/movie_service.dart';

class MovieDetailScreen extends StatefulWidget {
  final int movieId;
  const MovieDetailScreen({super.key, required this.movieId});

  @override
  State<MovieDetailScreen> createState() => _MovieDetailScreenState();
}

class _MovieDetailScreenState extends State<MovieDetailScreen> {
  late Future<Movie> _future;

  @override
  void initState() {
    super.initState();
    _future = context.read<MovieService>().detail(widget.movieId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('รายละเอียด')),
      body: FutureBuilder<Movie>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return Center(child: Text('${snap.error}'));
          }
          final movie = snap.data!;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Image.network(
                      movie.posterUrl,
                      width: 130,
                      height: 195,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        width: 130,
                        height: 195,
                        color: const Color(0xFF1a1a2e),
                        child:
                            const Icon(Icons.movie, color: Colors.white24),
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
                                fontSize: 20, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 8),
                        Row(children: [
                          const Icon(Icons.schedule,
                              size: 16, color: Colors.white54),
                          const SizedBox(width: 4),
                          Text('${movie.durationMins} นาที',
                              style: const TextStyle(color: Colors.white70)),
                        ]),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              const Text('เรื่องย่อ',
                  style:
                      TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 6),
              Text(movie.synopsis.isEmpty ? '-' : movie.synopsis,
                  style: const TextStyle(height: 1.5, color: Colors.white70)),
              const SizedBox(height: 24),
              const Text('รอบฉาย',
                  style:
                      TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              if (movie.showtimes.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 16),
                  child: Text('ยังไม่มีรอบฉาย',
                      style: TextStyle(color: Colors.white54)),
                )
              else
                ..._groupByCinema(movie.showtimes).entries.map(
                      (e) => _CinemaShowtimes(
                          cinemaName: e.key, showtimes: e.value),
                    ),
              if (movie.hasTrailer) ...[
                const SizedBox(height: 24),
                const Text('ตัวอย่างหนัง',
                    style:
                        TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: _TrailerPlayer(videoId: movie.youtubeId!),
                ),
              ],
            ],
          );
        },
      ),
    );
  }

  /// จัดรอบฉายเป็นกลุ่มตามโรง
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
/// จัดการ controller เอง (สร้าง/ปิด) เพื่อไม่ให้เล่นค้างหลังออกจากหน้า
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
      autoPlay: false, // ให้ผู้ใช้กดเล่นเอง
      params: const YoutubePlayerParams(
        showControls: true,
        showFullscreenButton: true,
      ),
    );
  }

  @override
  void dispose() {
    _controller.close(); // หยุด + ปล่อย resource
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

  const _CinemaShowtimes({required this.cinemaName, required this.showtimes});

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
              const Icon(Icons.location_on_outlined, size: 18),
              const SizedBox(width: 6),
              Text(cinemaName,
                  style: const TextStyle(fontWeight: FontWeight.bold)),
            ]),
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: showtimes.map((s) {
                return Chip(
                  avatar: const Icon(Icons.event_seat, size: 16),
                  label: Text('${s.showTime}  (ว่าง ${s.availableSeats})'),
                  visualDensity: VisualDensity.compact,
                );
              }).toList(),
            ),
          ],
        ),
      ),
    );
  }
}
