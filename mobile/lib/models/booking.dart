class BookingShowtime {
  final int id;
  final String showTime;
  final String? movieTitle;
  final String? cinemaName;

  BookingShowtime({
    required this.id,
    required this.showTime,
    this.movieTitle,
    this.cinemaName,
  });

  factory BookingShowtime.fromJson(Map<String, dynamic> json) =>
      BookingShowtime(
        id: json['id'] as int? ?? 0,
        showTime: json['show_time'] as String? ?? '',
        movieTitle: json['movie'] as String?,
        cinemaName: json['cinema'] as String?,
      );
}

class Booking {
  final int id;
  final String seatNumber;
  final String status;
  final bool isCancelled;
  final BookingShowtime? showtime;

  Booking({
    required this.id,
    required this.seatNumber,
    required this.status,
    required this.isCancelled,
    this.showtime,
  });

  factory Booking.fromJson(Map<String, dynamic> json) => Booking(
        id: json['id'] as int,
        seatNumber: json['seat_number'] as String? ?? '',
        status: json['status'] as String? ?? 'booked',
        isCancelled: json['is_cancelled'] as bool? ?? false,
        showtime: json['showtime'] != null
            ? BookingShowtime.fromJson(json['showtime'] as Map<String, dynamic>)
            : null,
      );
}
