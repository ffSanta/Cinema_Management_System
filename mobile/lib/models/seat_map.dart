import 'package:flutter/material.dart';

/// สถานะที่นั่งบนจอ
enum SeatStatus { available, booked, selecting }

class Seat {
  final String seat; // เช่น 'A1'
  final bool booked;

  Seat({required this.seat, required this.booked});

  factory Seat.fromJson(Map<String, dynamic> json) => Seat(
        seat: json['seat'] as String,
        booked: json['booked'] as bool? ?? false,
      );
}

class SeatRow {
  final String label;
  final String zone;
  final Color color;
  final int price;
  final bool pairs;
  final bool zoneStart;
  final List<Seat> seats;

  SeatRow({
    required this.label,
    required this.zone,
    required this.color,
    required this.price,
    required this.pairs,
    required this.zoneStart,
    required this.seats,
  });

  factory SeatRow.fromJson(Map<String, dynamic> json) => SeatRow(
        label: json['label'] as String,
        zone: json['zone'] as String,
        color: _hexColor(json['color'] as String?),
        price: json['price'] as int? ?? 0,
        pairs: json['pairs'] as bool? ?? false,
        zoneStart: json['zone_start'] as bool? ?? false,
        seats: (json['seats'] as List<dynamic>)
            .map((e) => Seat.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}

class ZoneLegend {
  final String zone;
  final Color color;
  final int price;

  ZoneLegend({required this.zone, required this.color, required this.price});

  factory ZoneLegend.fromJson(Map<String, dynamic> json) => ZoneLegend(
        zone: json['zone'] as String,
        color: _hexColor(json['color'] as String?),
        price: json['price'] as int? ?? 0,
      );
}

/// ผลลัพธ์ของ GET /showtimes/{id}/seats
class SeatMapData {
  final int seatsPerRow;
  final int aislePosition;
  final List<ZoneLegend> zones;
  final List<SeatRow> rows;

  SeatMapData({
    required this.seatsPerRow,
    required this.aislePosition,
    required this.zones,
    required this.rows,
  });

  factory SeatMapData.fromJson(Map<String, dynamic> json) => SeatMapData(
        seatsPerRow: json['seats_per_row'] as int? ?? 20,
        aislePosition: json['aisle_position'] as int? ?? 11,
        zones: (json['zones'] as List<dynamic>? ?? [])
            .map((e) => ZoneLegend.fromJson(e as Map<String, dynamic>))
            .toList(),
        rows: (json['rows'] as List<dynamic>? ?? [])
            .map((e) => SeatRow.fromJson(e as Map<String, dynamic>))
            .toList(),
      );

  /// ราคาต่อที่นั่ง (ไว้คำนวณราคารวมแบบ real-time)
  Map<String, int> get priceBySeat {
    final map = <String, int>{};
    for (final row in rows) {
      for (final s in row.seats) {
        map[s.seat] = row.price;
      }
    }
    return map;
  }
}

/// แปลง '#6f42c1' → Color
Color _hexColor(String? hex) {
  if (hex == null || hex.isEmpty) return const Color(0xFF198754);
  var h = hex.replaceAll('#', '');
  if (h.length == 6) h = 'FF$h';
  return Color(int.parse(h, radix: 16));
}
