import 'package:flutter/material.dart';
import '../models/movie.dart';

/// การ์ดโปสเตอร์หนังในกริด
class MovieCard extends StatelessWidget {
  final Movie movie;
  final VoidCallback onTap;

  const MovieCard({super.key, required this.movie, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: Stack(
                fit: StackFit.expand,
                children: [
                  Image.network(
                    movie.posterUrl,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => Container(
                      color: const Color(0xFF1a1a2e),
                      child: const Icon(Icons.movie,
                          color: Colors.white24, size: 40),
                    ),
                  ),
                  if (movie.hasTrailer)
                    const Positioned(
                      right: 6,
                      top: 6,
                      child: Icon(Icons.play_circle_fill,
                          color: Colors.white70, size: 26),
                    ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            movie.title,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontWeight: FontWeight.w600),
          ),
          Text('${movie.durationMins} นาที',
              style: const TextStyle(fontSize: 12, color: Colors.white54)),
        ],
      ),
    );
  }
}
