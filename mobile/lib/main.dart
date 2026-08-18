import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'services/api_client.dart';
import 'services/auth_service.dart';
import 'services/token_storage.dart';
import 'state/auth_provider.dart';
import 'screens/login_screen.dart';
import 'screens/profile_screen.dart';

void main() {
  final tokenStorage = TokenStorage();
  final apiClient = ApiClient(tokenStorage);
  final authService = AuthService(apiClient);

  runApp(CinemaApp(tokenStorage: tokenStorage, authService: authService));
}

class CinemaApp extends StatelessWidget {
  final TokenStorage tokenStorage;
  final AuthService authService;

  const CinemaApp({
    super.key,
    required this.tokenStorage,
    required this.authService,
  });

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        Provider<AuthService>.value(value: authService),
        ChangeNotifierProvider<AuthProvider>(
          create: (_) => AuthProvider(
            authService: authService,
            tokenStorage: tokenStorage,
          )..bootstrap(),
        ),
      ],
      child: MaterialApp(
        title: 'Cinema',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          colorSchemeSeed: const Color(0xFFe94560),
          brightness: Brightness.dark,
          scaffoldBackgroundColor: const Color(0xFF16161f),
          useMaterial3: true,
        ),
        home: const _Root(),
      ),
    );
  }
}

/// เลือกหน้าตามสถานะล็อกอิน
class _Root extends StatelessWidget {
  const _Root();

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    switch (auth.status) {
      case AuthStatus.unknown:
        return const Scaffold(body: Center(child: CircularProgressIndicator()));
      case AuthStatus.authenticated:
        return const ProfileScreen();
      case AuthStatus.unauthenticated:
        return const LoginScreen();
    }
  }
}
