import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../models/booking.dart';
import '../theme/app_theme.dart';

/// E-Ticket ดีไซน์รูปตั๋ว + QR Code จากรหัสการจอง (ไว้สแกนหน้าโรงจริง)
class TicketScreen extends StatelessWidget {
  final Booking booking;
  const TicketScreen({super.key, required this.booking});

  /// รหัสการจอง เช่น TKT-000123
  String get _code => 'TKT-${booking.id.toString().padLeft(6, '0')}';

  @override
  Widget build(BuildContext context) {
    final s = booking.showtime;
    return Scaffold(
      appBar: AppBar(title: const Text('ตั๋วภาพยนตร์')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 360),
            child: Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(
                      color: AppColors.ink.withValues(alpha: 0.15),
                      blurRadius: 20,
                      offset: const Offset(0, 8)),
                ],
              ),
              clipBehavior: Clip.antiAlias,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // ===== ส่วนหัว (โทนโรงหนัง) =====
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.fromLTRB(20, 20, 20, 18),
                    decoration: const BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: [AppColors.brand, AppColors.brandDark],
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('E-TICKET',
                                style: TextStyle(
                                    color: AppColors.gold2,
                                    fontWeight: FontWeight.w800,
                                    letterSpacing: 2,
                                    fontSize: 13)),
                            Icon(Icons.local_movies,
                                color: Colors.white70, size: 22),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Text(s?.movieTitle ?? '-',
                            style: const TextStyle(
                                color: Colors.white,
                                fontSize: 22,
                                fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),

                  // ===== รายละเอียด =====
                  Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      children: [
                        Row(
                          children: [
                            _info('โรงภาพยนตร์', s?.cinemaName ?? '-',
                                Icons.location_on_outlined),
                            _info('ที่นั่ง', booking.seatNumber,
                                Icons.event_seat),
                          ],
                        ),
                        const SizedBox(height: 16),
                        Row(
                          children: [
                            _info('รอบฉาย', s?.showTime ?? '-',
                                Icons.schedule),
                            _info(
                                'สถานะ',
                                booking.isCancelled ? 'ยกเลิกแล้ว' : 'ใช้งานได้',
                                Icons.verified_outlined),
                          ],
                        ),
                      ],
                    ),
                  ),

                  // ===== รอยปรุ (perforation) =====
                  const _Perforation(),

                  // ===== QR Code =====
                  Padding(
                    padding: const EdgeInsets.fromLTRB(20, 18, 20, 24),
                    child: Column(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            border:
                                Border.all(color: const Color(0xFFE7E4F2)),
                          ),
                          child: booking.isCancelled
                              ? _cancelledQr()
                              : QrImageView(
                                  data: _code,
                                  version: QrVersions.auto,
                                  size: 176,
                                  gapless: false,
                                  eyeStyle: const QrEyeStyle(
                                      eyeShape: QrEyeShape.square,
                                      color: AppColors.ink),
                                  dataModuleStyle: const QrDataModuleStyle(
                                      dataModuleShape:
                                          QrDataModuleShape.square,
                                      color: AppColors.ink),
                                ),
                        ),
                        const SizedBox(height: 12),
                        Text(_code,
                            style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                letterSpacing: 2,
                                color: AppColors.ink)),
                        const SizedBox(height: 4),
                        Text(
                          booking.isCancelled
                              ? 'ตั๋วนี้ถูกยกเลิกแล้ว'
                              : 'แสดง QR นี้ที่หน้าโรงภาพยนตร์เพื่อสแกนเข้าชม',
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                              fontSize: 12, color: AppColors.muted),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _info(String label, String value, IconData icon) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 14, color: AppColors.muted),
              const SizedBox(width: 4),
              Text(label,
                  style:
                      const TextStyle(fontSize: 11, color: AppColors.muted)),
            ],
          ),
          const SizedBox(height: 3),
          Text(value,
              style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                  color: AppColors.ink)),
        ],
      ),
    );
  }

  Widget _cancelledQr() {
    return SizedBox(
      width: 176,
      height: 176,
      child: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.block, size: 56, color: Colors.grey.shade400),
            const SizedBox(height: 8),
            Text('ยกเลิกแล้ว',
                style: TextStyle(color: Colors.grey.shade500)),
          ],
        ),
      ),
    );
  }
}

/// เส้นปรุตั๋ว — วงกลมเว้าสองข้าง + เส้นประ
class _Perforation extends StatelessWidget {
  const _Perforation();

  @override
  Widget build(BuildContext context) {
    const bg = AppColors.pageBg;
    return SizedBox(
      height: 30,
      child: Stack(
        alignment: Alignment.center,
        children: [
          // เส้นประ
          LayoutBuilder(
            builder: (context, c) {
              final count = (c.maxWidth / 12).floor();
              return Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: List.generate(
                    count,
                    (_) => Container(
                        width: 6, height: 2, color: const Color(0xFFD9D5EA)),
                  ),
                ),
              );
            },
          ),
          // วงกลมเว้าซ้าย/ขวา (สีพื้นหลังหน้า)
          const Positioned(
              left: -15,
              child: CircleAvatar(radius: 15, backgroundColor: bg)),
          const Positioned(
              right: -15,
              child: CircleAvatar(radius: 15, backgroundColor: bg)),
        ],
      ),
    );
  }
}
