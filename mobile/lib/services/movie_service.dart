import '../models/movie.dart';
import 'api_client.dart';

class MovieService {
  final ApiClient api;
  MovieService(this.api);

  /// รายการภาพยนตร์ทั้งหมด (public — ไม่ต้องล็อกอิน)
  Future<List<Movie>> list() async {
    final data = await api.get('/movies', auth: false);
    return (data['data'] as List<dynamic>)
        .map((e) => Movie.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  /// รายละเอียดภาพยนตร์ + รอบฉาย
  Future<Movie> detail(int id) async {
    final data = await api.get('/movies/$id', auth: false);
    return Movie.fromJson(data['data'] as Map<String, dynamic>);
  }
}
