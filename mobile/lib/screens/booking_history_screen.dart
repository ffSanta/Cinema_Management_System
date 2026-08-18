import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/booking.dart';
import '../services/api_client.dart';
import '../services/booking_service.dart';

class BookingHistoryScreen extends StatefulWidget {
  const BookingHistoryScreen({super.key});

  @override
  State<BookingHistoryScreen> createState() => BookingHistoryScreenState();
}

class BookingHistoryScreenState extends State<BookingHistoryScreen> {
  List<Booking>? _bookings; // null = ยังโหลดไม่เสร็จครั้งแรก
  Object? _error;
  bool _busy = false; // กำลังยกเลิกอยู่

  @override
  void initState() {
    super.initState();
    _load();
  }

  /// โหลด/รีเฟรชประวัติแล้ว setState ตรง ๆ (rebuild แน่นอน)
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
      await _load(); // โหลดใหม่ให้เห็นสถานะล่าสุด
      messenger.showSnackBar(
          const SnackBar(content: Text('ยกเลิกการจองเรียบร้อยแล้ว')));
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
      return ListView(children: [
        const SizedBox(height: 120),
        Center(child: Text('$_error')),
      ]);
    }
    if (_bookings == null) {
      return const Center(child: CircularProgressIndicator());
    }
    final bookings = _bookings!;
    if (bookings.isEmpty) {
      return ListView(children: const [
        SizedBox(height: 140),
        Icon(Icons.confirmation_number_outlined,
            size: 48, color: Colors.white30),
        SizedBox(height: 8),
        Center(
            child: Text('ยังไม่มีการจอง',
                style: TextStyle(color: Colors.white54))),
      ]);
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
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFe94560).withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Column(
                children: [
                  const Text('ที่นั่ง', style: TextStyle(fontSize: 10)),
                  Text(booking.seatNumber,
                      style: const TextStyle(
                          fontSize: 18, fontWeight: FontWeight.bold)),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(s?.movieTitle ?? '-',
                      style: const TextStyle(fontWeight: FontWeight.bold),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 2),
                  Text(s?.cinemaName ?? '-',
                      style: const TextStyle(
                          fontSize: 12, color: Colors.white60)),
                  Text(s?.showTime ?? '-',
                      style: const TextStyle(
                          fontSize: 12, color: Colors.white60)),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      _statusChip(booking.isCancelled),
                      const Spacer(),
                      if (booking.isCancelled)
                        TextButton.icon(
                          onPressed: busy ? null : onRestore,
                          icon: const Icon(Icons.restore, size: 16),
                          label: const Text('กู้คืน'),
                        )
                      else
                        TextButton.icon(
                          onPressed: busy ? null : onCancel,
                          style: TextButton.styleFrom(
                              foregroundColor: Colors.redAccent),
                          icon: const Icon(Icons.cancel_outlined, size: 16),
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
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: cancelled ? Colors.grey.shade700 : Colors.green.shade700,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(cancelled ? 'ยกเลิกแล้ว' : 'จองแล้ว',
          style: const TextStyle(fontSize: 11)),
    );
  }
}
