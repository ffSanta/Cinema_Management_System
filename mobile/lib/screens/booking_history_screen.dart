import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/booking.dart';
import '../services/api_client.dart';
import '../services/booking_service.dart';
import '../theme/app_theme.dart';
import '../widgets/empty_state.dart';
import '../widgets/skeletons.dart';

class BookingHistoryScreen extends StatefulWidget {
  const BookingHistoryScreen({super.key});

  @override
  State<BookingHistoryScreen> createState() => BookingHistoryScreenState();
}

class BookingHistoryScreenState extends State<BookingHistoryScreen> {
  List<Booking>? _bookings; // null = ยังโหลดไม่เสร็จครั้งแรก
  Object? _error;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final data = await context.read<BookingService>().history();
      if (mounted) {
        setState(() {
          _bookings = data;
          _error = null;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  /// เรียกจากภายนอก (ตอนสลับเข้าแท็บ) เพื่อโหลดประวัติใหม่
  void reload() => _load();

  Future<void> _cancel(Booking b) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('ยืนยันการยกเลิก'),
        content: Text(
          'ยกเลิกการจองที่นั่ง ${b.seatNumber} (${b.showtime?.movieTitle ?? '-'})?\nที่นั่งจะกลับมาว่างทันที',
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('ปิด')),
          FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('ยกเลิกการจอง')),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    final bookingService = context.read<BookingService>();
    setState(() => _busy = true);
    try {
      await bookingService.cancel(b.id);
      await _load();
      messenger.showSnackBar(const SnackBar(
          content: Text('ยกเลิกการจองเรียบร้อยแล้ว')));
    } on ApiException catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _restore(Booking b) async {
    final messenger = ScaffoldMessenger.of(context);
    final bookingService = context.read<BookingService>();
    setState(() => _busy = true);
    try {
      await bookingService.restore(b.id);
      await _load();
      messenger.showSnackBar(
          const SnackBar(content: Text('กู้คืนการจองเรียบร้อยแล้ว')));
    } on ApiException catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('ประวัติการจอง'),
        actions: [
          IconButton(
            onPressed: _busy ? null : _load,
            icon: const Icon(Icons.refresh),
            tooltip: 'รีเฟรช',
          ),
        ],
      ),
      body: RefreshIndicator(onRefresh: _load, child: _buildBody()),
    );
  }

  Widget _buildBody() {
    if (_error != null) {
      return ErrorState(message: '$_error', onRetry: _load);
    }
    if (_bookings == null) {
      return const ListCardSkeleton();
    }
    final bookings = _bookings!;
    if (bookings.isEmpty) {
      return const EmptyState(
        icon: Icons.confirmation_number_outlined,
        title: 'ยังไม่มีการจอง',
        subtitle: 'เลือกหนังแล้วจองที่นั่งได้เลย',
      );
    }
    return Stack(
      children: [
        ListView.separated(
          padding: const EdgeInsets.all(12),
          itemCount: bookings.length,
          separatorBuilder: (_, __) => const SizedBox(height: 8),
          itemBuilder: (_, i) => _BookingCard(
            booking: bookings[i],
            busy: _busy,
            onCancel: () => _cancel(bookings[i]),
            onRestore: () => _restore(bookings[i]),
          ),
        ),
        if (_busy)
          const Positioned.fill(
            child: IgnorePointer(
              child: Center(child: CircularProgressIndicator()),
            ),
          ),
      ],
    );
  }
}

class _BookingCard extends StatelessWidget {
  final Booking booking;
  final bool busy;
  final VoidCallback onCancel;
  final VoidCallback onRestore;

  const _BookingCard({
    required this.booking,
    required this.busy,
    required this.onCancel,
    required this.onRestore,
  });

  @override
  Widget build(BuildContext context) {
    final s = booking.showtime;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 58,
              padding: const EdgeInsets.symmetric(vertical: 10),
              decoration: BoxDecoration(
                color: AppColors.brand050,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Column(
                children: [
                  const Text('ที่นั่ง',
                      style: TextStyle(fontSize: 10, color: AppColors.muted)),
                  Text(booking.seatNumber,
                      style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: AppColors.brand)),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(s?.movieTitle ?? '-',
                      style: const TextStyle(
                          fontWeight: FontWeight.bold, color: AppColors.ink),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 2),
                  Text(s?.cinemaName ?? '-',
                      style:
                          const TextStyle(fontSize: 12, color: AppColors.muted)),
                  Text(s?.showTime ?? '-',
                      style:
                          const TextStyle(fontSize: 12, color: AppColors.muted)),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      _statusChip(booking.isCancelled),
                      const Spacer(),
                      if (booking.isCancelled)
                        TextButton.icon(
                          onPressed: busy ? null : onRestore,
                          icon: const Icon(Icons.restore, size: 18),
                          label: const Text('กู้คืน'),
                        )
                      else
                        TextButton.icon(
                          onPressed: busy ? null : onCancel,
                          style: TextButton.styleFrom(
                              foregroundColor: Colors.red.shade600),
                          icon: const Icon(Icons.cancel_outlined, size: 18),
                          label: const Text('ยกเลิก'),
                        ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _statusChip(bool cancelled) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: cancelled ? const Color(0xFFEDEBF3) : const Color(0xFFE3F5E9),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(cancelled ? 'ยกเลิกแล้ว' : 'จองแล้ว',
          style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: cancelled ? AppColors.muted : const Color(0xFF1E8E4E))),
    );
  }
}
