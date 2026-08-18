/// การตั้งค่า API — เปลี่ยน baseUrl ให้ตรงกับที่รัน Laravel
///
/// รัน Laravel:  php artisan serve --host 127.0.0.1 --port 8000
///
/// เลือก baseUrl ตามอุปกรณ์:
///  - Chrome (web) / iOS Simulator : http://127.0.0.1:8000/api
///  - Android Emulator             : http://10.0.2.2:8000/api
///  - เครื่องจริง (LAN)             : http://<IP เครื่องคอม>:8000/api
class ApiConfig {
  static const String baseUrl = 'http://127.0.0.1:8000/api';

  static const Duration timeout = Duration(seconds: 20);
}
