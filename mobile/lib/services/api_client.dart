import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import 'token_storage.dart';

/// error จากชั้น API พร้อม status code + ข้อความ + validation errors
class ApiException implements Exception {
  final int statusCode;
  final String message;
  final Map<String, List<String>> errors; // จาก Laravel validation (422)

  ApiException(this.statusCode, this.message, [this.errors = const {}]);

  bool get isValidation => statusCode == 422;
  bool get isUnauthorized => statusCode == 401;

  @override
  String toString() => message;
}

/// http client กลาง — แนบ Bearer token, แปลง JSON, จัดการ error รวมศูนย์
class ApiClient {
  final TokenStorage tokenStorage;
  final http.Client _http;

  ApiClient(this.tokenStorage, {http.Client? client})
      : _http = client ?? http.Client();

  Future<Map<String, String>> _headers({bool auth = true}) async {
    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };
    if (auth) {
      final token = await tokenStorage.read();
      if (token != null) headers['Authorization'] = 'Bearer $token';
    }
    return headers;
  }

  Uri _uri(String path) => Uri.parse('${ApiConfig.baseUrl}$path');

  Future<dynamic> get(String path, {bool auth = true}) => _send(
      () async => _http.get(_uri(path), headers: await _headers(auth: auth)));

  Future<dynamic> post(String path, {Object? body, bool auth = true}) =>
      _send(() async => _http.post(_uri(path),
          headers: await _headers(auth: auth),
          body: body == null ? null : jsonEncode(body)));

  Future<dynamic> put(String path, {Object? body, bool auth = true}) =>
      _send(() async => _http.put(_uri(path),
          headers: await _headers(auth: auth),
          body: body == null ? null : jsonEncode(body)));

  Future<dynamic> patch(String path, {Object? body, bool auth = true}) =>
      _send(() async => _http.patch(_uri(path),
          headers: await _headers(auth: auth),
          body: body == null ? null : jsonEncode(body)));

  Future<dynamic> delete(String path, {bool auth = true}) => _send(() async =>
      _http.delete(_uri(path), headers: await _headers(auth: auth)));

  /// อัปโหลดไฟล์แบบ multipart (เช่น รูปโปรไฟล์)
  Future<dynamic> uploadFile(
    String path, {
    required String field,
    required List<int> bytes,
    required String filename,
    bool auth = true,
  }) =>
      _send(() async {
        final req = http.MultipartRequest('POST', _uri(path));
        req.headers['Accept'] = 'application/json';
        if (auth) {
          final token = await tokenStorage.read();
          if (token != null) req.headers['Authorization'] = 'Bearer $token';
        }
        req.files
            .add(http.MultipartFile.fromBytes(field, bytes, filename: filename));
        return http.Response.fromStream(await req.send());
      });

  /// ยิง request + แปลงผล + โยน ApiException เมื่อ status >= 400
  Future<dynamic> _send(Future<http.Response> Function() request) async {
    http.Response res;
    try {
      res = await request().timeout(ApiConfig.timeout);
    } catch (_) {
      throw ApiException(0, 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาตรวจสอบการเชื่อมต่อ');
    }

    final dynamic data =
        res.body.isEmpty ? null : jsonDecode(utf8.decode(res.bodyBytes));

    if (res.statusCode >= 200 && res.statusCode < 300) {
      return data;
    }

    final message = (data is Map && data['message'] != null)
        ? data['message'].toString()
        : 'เกิดข้อผิดพลาด (${res.statusCode})';

    final errors = <String, List<String>>{};
    if (data is Map && data['errors'] is Map) {
      (data['errors'] as Map).forEach((k, v) {
        errors[k.toString()] = (v as List).map((e) => e.toString()).toList();
      });
    }

    throw ApiException(res.statusCode, message, errors);
  }
}
