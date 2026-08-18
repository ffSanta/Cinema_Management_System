import 'package:flutter/foundation.dart';
import '../models/user.dart';
import '../services/auth_service.dart';
import '../services/token_storage.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

/// สถานะการล็อกอินทั้งแอป — เก็บ token + ข้อมูล user
class AuthProvider extends ChangeNotifier {
  final AuthService authService;
  final TokenStorage tokenStorage;

  AuthProvider({required this.authService, required this.tokenStorage});

  AuthStatus status = AuthStatus.unknown;
  User? user;

  bool get isLoggedIn => status == AuthStatus.authenticated;

  /// เรียกตอนเปิดแอป — มี token เก็บไว้ไหม แล้วดึงโปรไฟล์
  Future<void> bootstrap() async {
    final token = await tokenStorage.read();
    if (token == null) {
      _set(AuthStatus.unauthenticated, null);
      return;
    }
    try {
      final u = await authService.profile();
      _set(AuthStatus.authenticated, u);
    } catch (_) {
      await tokenStorage.clear(); // token หมดอายุ/ใช้ไม่ได้
      _set(AuthStatus.unauthenticated, null);
    }
  }

  Future<void> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    final res = await authService.register(
      name: name,
      email: email,
      password: password,
      passwordConfirmation: passwordConfirmation,
    );
    await tokenStorage.save(res.token);
    _set(AuthStatus.authenticated, res.user);
  }

  Future<void> login({required String email, required String password}) async {
    final res = await authService.login(email: email, password: password);
    await tokenStorage.save(res.token);
    _set(AuthStatus.authenticated, res.user);
  }

  Future<void> logout() async {
    try {
      await authService.logout();
    } catch (_) {
      // ถึง API พังก็ยังเคลียร์ token ฝั่ง client
    }
    await tokenStorage.clear();
    _set(AuthStatus.unauthenticated, null);
  }

  void updateUser(User updated) {
    user = updated;
    notifyListeners();
  }

  void _set(AuthStatus s, User? u) {
    status = s;
    user = u;
    notifyListeners();
  }
}
