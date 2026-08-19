import '../config/api_config.dart';
import 'showtime.dart';

class Movie {
  final int id;
  final String title;
  final int durationMins;
  final String synopsis;
  final String posterUrl;
  final String? youtubeId;

  /// มีเฉพาะตอนเรียกหน้ารายละเอียด (GET /movies/{id})
  final List<Showtime> showtimes;

  Movie({
    required this.id,
    required this.title,
    required this.durationMins,
    required this.synopsis,
    required this.posterUrl,
    this.youtubeId,
    this.showtimes = const [],
  });

  bool get hasTrailer => (youtubeId ?? '').isNotEmpty;

  factory Movie.fromJson(Map<String, dynamic> json) => Movie(
        id: json['id'] as int,
        title: json['title'] as String? ?? '',
        durationMins: json['duration_mins'] as int? ?? 0,
        synopsis: json['synopsis'] as String? ?? '',
        posterUrl: ApiConfig.resolveUrl(json['poster_url'] as String? ?? ''),
        youtubeId: json['youtube_id'] as String?,
        showtimes: (json['showtimes'] as List<dynamic>?)
                ?.map((e) => Showtime.fromJson(e as Map<String, dynamic>))
                .toList() ??
            const [],
      );
}
