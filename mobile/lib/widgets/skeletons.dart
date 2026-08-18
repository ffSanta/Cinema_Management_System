import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';

/// กล่อง shimmer สี่เหลี่ยมมุมโค้ง (ชิ้นส่วนพื้นฐานของ skeleton)
class _Box extends StatelessWidget {
  final double? width;
  final double height;
  final double radius;
  const _Box({this.width, required this.height, this.radius = 8});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(radius),
      ),
    );
  }
}

Shimmer _shimmer(Widget child) => Shimmer.fromColors(
      baseColor: Colors.grey.shade300,
      highlightColor: Colors.grey.shade100,
      child: child,
    );

/// skeleton ของกริดโปสเตอร์หนัง (ระหว่างโหลด)
class MovieGridSkeleton extends StatelessWidget {
  final int count;
  const MovieGridSkeleton({super.key, this.count = 9});

  @override
  Widget build(BuildContext context) {
    return _shimmer(
      GridView.builder(
        padding: const EdgeInsets.all(12),
        physics: const NeverScrollableScrollPhysics(),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 3,
          childAspectRatio: 0.52,
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
        ),
        itemCount: count,
        itemBuilder: (_, __) => const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(child: _Box(height: double.infinity, radius: 10)),
            SizedBox(height: 6),
            _Box(width: 80, height: 12),
            SizedBox(height: 4),
            _Box(width: 48, height: 10),
          ],
        ),
      ),
    );
  }
}

/// skeleton ของรายการการ์ด (ประวัติการจอง)
class ListCardSkeleton extends StatelessWidget {
  final int count;
  const ListCardSkeleton({super.key, this.count = 5});

  @override
  Widget build(BuildContext context) {
    return _shimmer(
      ListView.separated(
        padding: const EdgeInsets.all(12),
        physics: const NeverScrollableScrollPhysics(),
        itemCount: count,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (_, __) => Container(
          height: 96,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
          ),
        ),
      ),
    );
  }
}
