class Cinema {
  final int id;
  final String name;
  final int totalSeats;

  Cinema({required this.id, required this.name, required this.totalSeats});

  factory Cinema.fromJson(Map<String, dynamic> json) => Cinema(
        id: json['id'] as int? ?? 0,
        name: json['name'] as String? ?? '',
        totalSeats: json['total_seats'] as int? ?? 0,
      );
}

class Showtime {
  final int id;
  final String showTime; // 'Y-m-d H:i' สำหรับแสดงผล
  final double price;
  final double pricePremium;
  final double priceVip;
  final int? movieId;
  final String? movieTitle;
  final String? moviePosterUrl;
  final Cinema? cinema;
  final int bookedSeats;
  final int availableSeats;

  Showtime({
    required this.id,
    required this.showTime,
    required this.price,
    required this.pricePremium,
    required this.priceVip,
    this.movieId,
    this.movieTitle,
    this.moviePosterUrl,
    this.cinema,
    this.bookedSeats = 0,
    this.availableSeats = 0,
  });

  factory Showtime.fromJson(Map<String, dynamic> json) {
    final movie = json['movie'];
    return Showtime(
      id: json['id'] as int,
      showTime: json['show_time'] as String? ?? '',
      price: _toDouble(json['price']),
      pricePremium: _toDouble(json['price_premium']),
      priceVip: _toDouble(json['price_vip']),
      movieId: movie is Map ? movie['id'] as int? : null,
      movieTitle: movie is Map ? movie['title'] as String? : null,
      moviePosterUrl: movie is Map ? movie['poster_url'] as String? : null,
      cinema: json['cinema'] != null
          ? Cinema.fromJson(json['cinema'] as Map<String, dynamic>)
          : null,
      bookedSeats: json['booked_seats'] as int? ?? 0,
      availableSeats: json['available_seats'] as int? ?? 0,
    );
  }

  static double _toDouble(dynamic v) {
    if (v is num) return v.toDouble();
    if (v is String) return double.tryParse(v) ?? 0;
    return 0;
  }
}
