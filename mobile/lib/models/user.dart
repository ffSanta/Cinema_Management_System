import '../config/api_config.dart';

class User {
  final int id;
  final String name;
  final String email;
  final String role;
  final String? avatarUrl;

  User({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.avatarUrl,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    final avatar = json['avatar_url'] as String?;
    return User(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      email: json['email'] as String? ?? '',
      role: json['role'] as String? ?? 'user',
      avatarUrl: (avatar == null || avatar.isEmpty)
          ? null
          : ApiConfig.resolveUrl(avatar),
    );
  }
}
