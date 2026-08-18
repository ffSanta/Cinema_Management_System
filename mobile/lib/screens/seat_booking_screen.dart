import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/seat_map.dart';
import '../models/showtime.dart';
import '../services/api_client.dart';
import '../services/booking_service.dart';
import '../services/showtime_service.dart';
import '../theme/app_theme.dart';
import '../widgets/empty_state.dart';

/// ใส่ , คั่นหลักพัน (เช่น 12345 -> 12,345)
String _baht(num n) {
  final s = n.toStringAsFixed(0);
  final buf = StringBuffer();
  for (var i = 0; i < s.length; i++) {
    if (i > 0 && (s.length - i) % 3 == 0) buf.write(',');
    buf.write(s[i]);
  }
  return buf.toString();
}

class SeatBookingScreen extends StatefulWidget {
  final Showtime showtime;
  final String movieTitle;

  const SeatBookingScreen({
    super.key,
    required this.showtime,
    required this.movieTitle,
  });

  @override
  State<SeatBookingScreen> createState() => _SeatBookingScreenState();
}

class _SeatBookingScreenState extends State<SeatBookingScreen> {
  SeatMapData? _map;
  String? _error;
  bool _loading = true;
  bool _booking = false;

  final Set<String> _selected = {};
  Map<String, int> _priceBySeat = {};

  @override
  void initState() {
    super.initState();
    _loadSeats();
  }

  Future<void> _loadSeats() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final map =
          await context.read<ShowtimeService>().seatMap(widget.showtime.id);
      if (!mounted) return;
      setState(() {
        _map = map;
        _priceBySeat = map.priceBySeat;
        _selected.removeWhere((s) => !_priceBySeat.containsKey(s));
        _loading = false;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.message;
        _loading = false;
      });
    }
  }

  int get _total => _selected.fold(0, (sum, s) => sum + (_priceBySeat[s] ?? 0));

  void _toggle(String seat) {
    setState(() {
      if (_selected.contains(seat)) {
        _selected.remove(seat);
      } else {
        _selected.add(seat);
      }
    });
  }

  Future<void> _confirm() async {
    if (_selected.isEmpty) return;
    final messenger = ScaffoldMessenger.of(context);
    final bookingService = context.read<BookingService>();
    setState(() => _booking = true);
    try {
      final result =
          await bookingService.book(widget.showtime.id, _selected.toList());
      if (!mounted) return;
      // แจ้งเตือนแบบ notify (SnackBar) — ไม่เด้ง modal, ไม่ย้อนหน้า
      messenger.showSnackBar(SnackBar(
        content: Text(
            'จองสำเร็จ ที่นั่ง ${result.seats.join(', ')} รวม ${_baht(result.totalPrice)} บาท'),
        backgroundColor: Colors.green.shade700,
      ));
      _selected.clear();
      await _loadSeats(); // อยู่หน้าเดิม + โหลดผังใหม่ → ที่นั่งที่เพิ่งจองเป็นสีเทา
    } on ApiException catch (e) {
      messenger.showSnackBar(SnackBar(
          content: Text(e.message), backgroundColor: Colors.red.shade700));
      if (e.statusCode == 409) _loadSeats();
    } finally {
      if (mounted) setState(() => _booking = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(widget.movieTitle,
                style: const TextStyle(
                    fontSize: 16,
                    color: AppColors.gold2,
                    fontWeight: FontWeight.w700),
                overflow: TextOverflow.ellipsis),
            Text(
                '${widget.showtime.cinema?.name ?? ''} · ${widget.showtime.showTime}',
                style: const TextStyle(fontSize: 12, color: Colors.white70)),
          ],
        ),
      ),
      body: _buildBody(),
      bottomNavigationBar: _map == null ? null : _buildBottomBar(),
    );
  }

  Widget _buildBody() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) {
      return ErrorState(message: _error!, onRetry: _loadSeats);
    }

    final map = _map!;
    return Column(
      children: [
        const SizedBox(height: 16),
        _screenBar(),
        // ผังที่นั่งแบบ zoom/pan/scroll ได้ลื่นไหล
        Expanded(
          child: InteractiveViewer(
            boundaryMargin: const EdgeInsets.all(120),
            minScale: 0.5,
            maxScale: 4.0,
            constrained: false,
            child: Padding(
              padding: const EdgeInsets.all(28),
              child: Column(children: map.rows.map(_buildRow).toList()),
            ),
          ),
        ),
        _legend(map),
      ],
    );
  }

  /// แถบ "จอภาพยนตร์"
  Widget _screenBar() {
    return Column(
      children: [
        Container(
          width: 240,
          height: 8,
          decoration: BoxDecoration(
            gradient: const LinearGradient(
                colors: [AppColors.brand, AppColors.brandDark]),
            borderRadius: BorderRadius.circular(4),
            boxShadow: [
              BoxShadow(
                  color: AppColors.brand.withValues(alpha: 0.35),
                  blurRadius: 18,
                  spreadRadius: 1),
            ],
          ),
        ),
        const SizedBox(height: 6),
        const Text('จอภาพยนตร์',
            style: TextStyle(fontSize: 12, color: AppColors.muted)),
      ],
    );
  }

  Widget _buildRow(SeatRow row) {
    final aisle = _map!.aislePosition;
    final children = <Widget>[
      SizedBox(
        width: 24,
        child: Text(row.label,
            style: const TextStyle(
                fontSize: 12,
                color: AppColors.muted,
                fontWeight: FontWeight.w600)),
      ),
    ];

    for (var i = 0; i < row.seats.length; i++) {
      final col = i + 1;
      if (row.pairs) {
        if (i > 0 && i % 2 == 0) children.add(const SizedBox(width: 16));
      } else if (col == aisle) {
        children.add(const SizedBox(width: 22));
      }
      children.add(_buildSeat(row.seats[i], row.color));
    }

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(mainAxisSize: MainAxisSize.min, children: children),
    );
  }

  Widget _buildSeat(Seat seat, Color zoneColor) {
    final selected = _selected.contains(seat.seat);
    final SeatStatus status = seat.booked
        ? SeatStatus.booked
        : (selected ? SeatStatus.selecting : SeatStatus.available);

    Color bg;
    Color fg = Colors.white;
    switch (status) {
      case SeatStatus.booked:
        bg = const Color(0xFFCBC9D6); // เทาอ่อน = จองแล้ว
        fg = Colors.white;
        break;
      case SeatStatus.selecting:
        bg = AppColors.brand; // ม่วงแบรนด์ = กำลังเลือก
        break;
      case SeatStatus.available:
        bg = zoneColor;
        break;
    }

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 3),
      child: GestureDetector(
        onTap: seat.booked ? null : () => _toggle(seat.seat),
        child: Container(
          width: 32,
          height: 32,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: bg,
            borderRadius: BorderRadius.circular(7),
            border: selected
                ? Border.all(color: AppColors.gold, width: 2)
                : null,
          ),
          child: Text(
            seat.seat.replaceAll(RegExp(r'^[A-Z]+'), ''),
            style: TextStyle(
                fontSize: 11, color: fg, fontWeight: FontWeight.w600),
          ),
        ),
      ),
    );
  }

  Widget _legend(SeatMapData map) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Color(0xFFE7E4F2))),
      ),
      child: Wrap(
        spacing: 16,
        runSpacing: 8,
        children: [
          _legendDot(const Color(0xFFCBC9D6), 'จองแล้ว'),
          _legendDot(AppColors.brand, 'กำลังเลือก'),
          ...map.zones
              .map((z) => _legendDot(z.color, '${z.zone} ${_baht(z.price)}฿')),
        ],
      ),
    );
  }

  Widget _legendDot(Color color, String label) {
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Container(
        width: 16,
        height: 16,
        decoration:
            BoxDecoration(color: color, borderRadius: BorderRadius.circular(5)),
      ),
      const SizedBox(width: 6),
      Text(label,
          style: const TextStyle(fontSize: 12, color: AppColors.ink)),
    ]);
  }

  Widget _buildBottomBar() {
    final count = _selected.length;
    return SafeArea(
      child: Container(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: Color(0xFFE7E4F2))),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    count == 0
                        ? 'ยังไม่ได้เลือกที่นั่ง'
                        : 'เลือก $count ที่นั่ง: ${_selected.join(', ')}',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 12, color: AppColors.muted),
                  ),
                  const SizedBox(height: 2),
                  Text('${_baht(_total)} บาท',
                      style: const TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.bold,
                          color: AppColors.ink)),
                ],
              ),
            ),
            const SizedBox(width: 12),
            FilledButton.icon(
              onPressed: (count == 0 || _booking) ? null : _confirm,
              icon: _booking
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.check),
              label: const Text('ยืนยันการจอง'),
            ),
          ],
        ),
      ),
    );
  }
}
