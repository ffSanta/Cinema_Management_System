/// การตั้งค่า API — เปลี่ยน baseUrl ให้ตรงกับที่รัน Laravel
///
/// รัน Laravel:  php artisan serve --host 127.0.0.1 --port 8000
///
/// เลือก baseUrl ตามอุปกรณ์:
///  - Chrome (web) / iOS Simulator : http://127.0.0.1:8000/api
///  - Android Emulator             : http://10.0.2.2:8000/api
///  - เครื่องจริง (LAN)             : http://<IP เครื่องคอม>:8000/api
class ApiConfig {
  static const String host = 'http://127.0.0.1:8000';
  static const String baseUrl = '$host/api';

  static const Duration timeout = Duration(seconds: 20);

  /// แปลง URL สัมพัทธ์ให้เป็น URL เต็ม
  /// - /storage/x.jpg → host/media/x.jpg (route ที่มี CORS header ให้ Flutter web โหลดได้)
  /// - path อื่น ๆ → เติม host เฉย ๆ
  static String resolveUrl(String url) {
    if (url.isEmpty || url.startsWith('http')) return url;
    const storagePrefix = '/storage/';
    if (url.startsWith(storagePrefix)) {
      return '$host/media/${url.substring(storagePrefix.length)}';
    }
    return url.startsWith('/') ? '$host$url' : '$host/$url';
  }
}
