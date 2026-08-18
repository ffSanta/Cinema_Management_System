import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/seat_map.dart';
import '../models/showtime.dart';
import '../services/api_client.dart';
import '../services/booking_service.dart';
import '../services/showtime_service.dart';

/// ใส่ , คั่นหลักพัน โดยไม่พึ่ง package (เช่น 12345 -> 12,345)
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

  static const _accent = Color(0xFFe94560);

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
        // เอาที่นั่งที่ถูกจองไปแล้วออกจาก selection (กรณี reload หลังชน)
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

  int get _total =>
      _selected.fold(0, (sum, s) => sum + (_priceBySeat[s] ?? 0));

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
      // แจ้งเตือนแบบ notify (SnackBar) เด้งขึ้นแล้วหายเอง — ไม่เด้ง modal, ไม่ย้อนหน้า
      messenger.showSnackBar(SnackBar(
        content: Text(
            'จองสำเร็จ ที่นั่ง ${result.seats.join(', ')} รวม ${_baht(result.totalPrice)} บาท'),
        backgroundColor: Colors.green.shade700,
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 3),
      ));
      _selected.clear();
      await _loadSeats(); // อยู่หน้าเดิม + โหลดผังใหม่ → ที่นั่งที่เพิ่งจองเป็นสีเทา
    } on ApiException catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(e.message)));
      if (e.statusCode == 409) _loadSeats(); // ที่นั่งชน → โหลดผังใหม่
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
                style: const TextStyle(fontSize: 16),
                overflow: TextOverflow.ellipsis),
            Text('${widget.showtime.cinema?.name ?? ''} · ${widget.showtime.showTime}',
                style: const TextStyle(fontSize: 12, color: Colors.white60)),
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
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off, size: 48, color: Colors.white38),
            const SizedBox(height: 8),
            Text(_error!),
            const SizedBox(height: 12),
            FilledButton(onPressed: _loadSeats, child: const Text('ลองใหม่')),
          ],
        ),
      );
    }

    final map = _map!;
    return Column(
      children: [
        const SizedBox(height: 12),
        _screenBar(),
        // ผังที่นั่งแบบ zoom/pan/scroll ได้
        Expanded(
          child: InteractiveViewer(
            boundaryMargin: const EdgeInsets.all(80),
            minScale: 0.4,
            maxScale: 3.0,
            constrained: false,
            child: Padding(
              padding: const EdgeInsets.all(24),
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
          width: 220,
          height: 6,
          decoration: BoxDecoration(
            color: Colors.white24,
            borderRadius: BorderRadius.circular(3),
            boxShadow: const [
              BoxShadow(color: Colors.white24, blurRadius: 18, spreadRadius: 1),
            ],
          ),
        ),
        const SizedBox(height: 4),
        const Text('จอภาพยนตร์',
            style: TextStyle(fontSize: 11, color: Colors.white38)),
      ],
    );
  }

  Widget _buildRow(SeatRow row) {
    final aisle = _map!.aislePosition;
    final children = <Widget>[
      SizedBox(
        width: 22,
        child: Text(row.label,
            style: const TextStyle(fontSize: 11, color: Colors.white54)),
      ),
    ];

    for (var i = 0; i < row.seats.length; i++) {
      final col = i + 1;
      // เว้นทางเดิน: โซนจับคู่เว้นทุก 2 ที่, โซนอื่นเว้นกลางแถว
      if (row.pairs) {
        if (i > 0 && i % 2 == 0) children.add(const SizedBox(width: 14));
      } else if (col == aisle) {
        children.add(const SizedBox(width: 18));
      }
      children.add(_buildSeat(row.seats[i], row.color));
    }

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
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
        bg = const Color(0xFF3a3a44);
        fg = Colors.white24;
        break;
      case SeatStatus.selecting:
        bg = _accent;
        break;
      case SeatStatus.available:
        bg = zoneColor;
        break;
    }

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 2),
      child: GestureDetector(
        onTap: seat.booked ? null : () => _toggle(seat.seat),
        child: Container(
          width: 26,
          height: 26,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: bg,
            borderRadius: BorderRadius.circular(6),
            border:
                selected ? Border.all(color: Colors.white, width: 1.5) : null,
          ),
          child: Text(
            seat.seat.replaceAll(RegExp(r'^[A-Z]+'), ''), // เลขที่นั่ง
            style: TextStyle(fontSize: 9, color: fg),
          ),
        ),
      ),
    );
  }

  /// legend: สถานะ + โซน/ราคา
  Widget _legend(SeatMapData map) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      color: Colors.black26,
      child: Wrap(
        spacing: 14,
        runSpacing: 6,
        children: [
          _legendDot(const Color(0xFF3a3a44), 'จองแล้ว'),
          _legendDot(_accent, 'กำลังเลือก'),
          ...map.zones
              .map((z) => _legendDot(z.color, '${z.zone} ${_baht(z.price)}฿')),
        ],
      ),
    );
  }

  Widget _legendDot(Color color, String label) {
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Container(
        width: 14,
        height: 14,
        decoration:
            BoxDecoration(color: color, borderRadius: BorderRadius.circular(4)),
      ),
      const SizedBox(width: 5),
      Text(label, style: const TextStyle(fontSize: 12)),
    ]);
  }

  Widget _buildBottomBar() {
    final count = _selected.length;
    return SafeArea(
      child: Container(
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 10),
        decoration: const BoxDecoration(
          color: Color(0xFF1e1e28),
          border: Border(top: BorderSide(color: Colors.white12)),
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
                    style: const TextStyle(fontSize: 12, color: Colors.white60),
                  ),
                  const SizedBox(height: 2),
                  Text('${_baht(_total)} บาท', // ราคารวมแบบ real-time
                      style: const TextStyle(
                          fontSize: 20, fontWeight: FontWeight.bold)),
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
                      child: CircularProgressIndicator(strokeWidth: 2))
                  : const Icon(Icons.check),
              label: const Text('ยืนยันการจอง'),
              style: FilledButton.styleFrom(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 18, vertical: 14)),
            ),
          ],
        ),
      ),
    );
  }
}
