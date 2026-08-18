import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

/// หน้าสถานะว่าง (ไม่มีข้อมูล) — ใช้ซ้ำได้ทุกที่
/// วางใน scrollable เพื่อให้ pull-to-refresh ยังใช้ได้
class EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;
  final Widget? action;

  const EmptyState({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    this.action,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 100),
      children: [
        Icon(icon, size: 64, color: AppColors.muted.withValues(alpha: 0.5)),
        const SizedBox(height: 16),
        Text(
          title,
          textAlign: TextAlign.center,
          style: const TextStyle(
              fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.ink),
        ),
        if (subtitle != null) ...[
          const SizedBox(height: 6),
          Text(
            subtitle!,
            textAlign: TextAlign.center,
            style: const TextStyle(color: AppColors.muted),
          ),
        ],
        if (action != null) ...[
          const SizedBox(height: 20),
          Center(child: action!),
        ],
      ],
    );
  }
}

/// หน้าแสดง error พร้อมปุ่มลองใหม่ (วางใน scrollable เช่นกัน)
class ErrorState extends StatelessWidget {
  final String message;
  final Future<void> Function()? onRetry;

  const ErrorState({super.key, required this.message, this.onRetry});

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 90),
      children: [
        Icon(Icons.cloud_off_rounded,
            size: 60, color: AppColors.muted.withValues(alpha: 0.5)),
        const SizedBox(height: 12),
        const Text(
          'โหลดข้อมูลไม่สำเร็จ',
          textAlign: TextAlign.center,
          style: TextStyle(
              fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.ink),
        ),
        const SizedBox(height: 6),
        Text(message,
            textAlign: TextAlign.center,
            style: const TextStyle(color: AppColors.muted)),
        if (onRetry != null) ...[
          const SizedBox(height: 20),
          Center(
            child: FilledButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: const Text('ลองใหม่'),
            ),
          ),
        ],
      ],
    );
  }
}
