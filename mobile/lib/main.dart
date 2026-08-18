import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'services/api_client.dart';
import 'services/auth_service.dart';
import 'services/booking_service.dart';
import 'services/movie_service.dart';
import 'services/showtime_service.dart';
import 'services/token_storage.dart';
import 'state/auth_provider.dart';
import 'theme/app_theme.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';

void main() {
  final tokenStorage = TokenStorage();
  final apiClient = ApiClient(tokenStorage);
  final authService = AuthService(apiClient);

  runApp(CinemaApp(
    tokenStorage: tokenStorage,
    apiClient: apiClient,
    authService: authService,
  ));
}

class CinemaApp extends StatelessWidget {
  final TokenStorage tokenStorage;
  final ApiClient apiClient;
  final AuthService authService;

  const CinemaApp({
    super.key,
    required this.tokenStorage,
    required this.apiClient,
    required this.authService,
  });

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        Provider<AuthService>.value(value: authService),
        Provider<MovieService>(create: (_) => MovieService(apiClient)),
        Provider<ShowtimeService>(create: (_) => ShowtimeService(apiClient)),
        Provider<BookingService>(create: (_) => BookingService(apiClient)),
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
        theme: AppTheme.light,
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
        return const HomeScreen();
      case AuthStatus.unauthenticated:
        return const LoginScreen();
    }
  }
}
