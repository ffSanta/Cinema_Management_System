import 'api_client.dart';

/// ผลการจอง
class BookingResult {
  final List<String> seats;
  final num totalPrice;
  final List<int> bookingIds;
  BookingResult(this.seats, this.totalPrice, this.bookingIds);
}

class BookingService {
  final ApiClient api;
  BookingService(this.api);

  /// จองที่นั่ง (หลายที่นั่งได้) — ต้องล็อกอิน
  Future<BookingResult> book(int showtimeId, List<String> seats) async {
    final data = await api.post('/showtimes/$showtimeId/bookings', body: {
      'seats': seats,
    });
    return BookingResult(
      (data['seats'] as List<dynamic>).map((e) => e.toString()).toList(),
      data['total_price'] as num? ?? 0,
      (data['booking_ids'] as List<dynamic>? ?? [])
          .map((e) => e as int)
          .toList(),
    );
  }
}
