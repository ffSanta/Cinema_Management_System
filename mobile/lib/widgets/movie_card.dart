import 'package:flutter/material.dart';
import '../models/movie.dart';
import '../theme/app_theme.dart';

/// การ์ดโปสเตอร์หนังในกริด — มีกรอบพื้นขาว/เงา ให้ต่างจากพื้นหลังและแท็บ
class MovieCard extends StatelessWidget {
  final Movie movie;
  final VoidCallback onTap;

  const MovieCard({super.key, required this.movie, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.surface,
      borderRadius: BorderRadius.circular(14),
      elevation: 2,
      shadowColor: AppColors.ink.withValues(alpha: 0.12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: const Color(0xFFE7E4F2)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // โปสเตอร์ — มุมโค้งเฉพาะด้านบน
              Expanded(
                child: ClipRRect(
                  borderRadius: const BorderRadius.vertical(
                      top: Radius.circular(13)),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      Image.network(
                        movie.posterUrl,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => Container(
                          color: const Color(0xFF2A2640),
                          child: const Icon(Icons.movie,
                              color: Colors.white38, size: 40),
                        ),
                      ),
                      if (movie.hasTrailer)
                        Positioned(
                          right: 6,
                          top: 6,
                          child: Container(
                            decoration: const BoxDecoration(
                              color: Colors.black45,
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.play_arrow_rounded,
                                color: Colors.white, size: 22),
                          ),
                        ),
                    ],
                  ),
                ),
              ),
              // ชื่อ + ความยาว บนพื้นขาวของกรอบ
              Padding(
                padding: const EdgeInsets.fromLTRB(8, 6, 8, 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      movie.title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 13,
                          color: AppColors.ink),
                    ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        const Icon(Icons.schedule,
                            size: 12, color: AppColors.muted),
                        const SizedBox(width: 3),
                        Text('${movie.durationMins} นาที',
                            style: const TextStyle(
                                fontSize: 11, color: AppColors.muted)),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
