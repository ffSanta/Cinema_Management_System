import '../models/user.dart';
import 'api_client.dart';

/// ผลลัพธ์ login/register — token + user
class AuthResult {
  final String token;
  final User user;
  AuthResult(this.token, this.user);
}

class AuthService {
  final ApiClient api;
  AuthService(this.api);

  Future<AuthResult> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    final data = await api.post('/register', auth: false, body: {
      'name': name,
      'email': email,
      'password': password,
      'password_confirmation': passwordConfirmation,
    });
    return AuthResult(
      data['token'] as String,
      User.fromJson(data['user'] as Map<String, dynamic>),
    );
  }

  Future<AuthResult> login({
    required String email,
    required String password,
  }) async {
    final data = await api.post('/login', auth: false, body: {
      'email': email,
      'password': password,
    });
    return AuthResult(
      data['token'] as String,
      User.fromJson(data['user'] as Map<String, dynamic>),
    );
  }

  Future<void> logout() => api.post('/logout');

  Future<User> profile() async {
    final data = await api.get('/profile');
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<User> updateProfile({
    required String name,
    required String email,
    String? password,
    String? passwordConfirmation,
  }) async {
    final body = <String, dynamic>{'name': name, 'email': email};
    if (password != null && password.isNotEmpty) {
      body['password'] = password;
      body['password_confirmation'] = passwordConfirmation ?? '';
    }
    final data = await api.put('/profile', body: body);
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }

  /// อัปโหลดรูปโปรไฟล์ (bytes จาก image_picker)
  Future<User> uploadAvatar(List<int> bytes, String filename) async {
    final data = await api.uploadFile('/profile/avatar',
        field: 'avatar', bytes: bytes, filename: filename);
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }
}
