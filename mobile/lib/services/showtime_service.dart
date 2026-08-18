import '../models/showtime.dart';
import 'api_client.dart';

class ShowtimeService {
  final ApiClient api;
  ShowtimeService(this.api);

  /// รอบฉายทั้งหมดที่ยังไม่ผ่าน (ใช้แยกหนัง กำลังฉาย / เร็ว ๆ นี้)
  Future<List<Showtime>> list() async {
    final data = await api.get('/showtimes', auth: false);
    return (data['data'] as List<dynamic>)
        .map((e) => Showtime.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}
