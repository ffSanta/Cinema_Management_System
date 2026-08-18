import 'package:flutter/material.dart';

/// สีของแบรนด์ — ให้ตรงกับธีมเว็บ (resources/views/layouts/theme.blade.php)
class AppColors {
  static const brand = Color(0xFF5B4BD1); // ม่วงแบรนด์
  static const brandDark = Color(0xFF4536A3);
  static const brand050 = Color(0xFFEFEDFB);
  static const gold = Color(0xFFCF9B3A);
  static const gold2 = Color(0xFFECC271);
  static const ink = Color(0xFF211D33); // ตัวหนังสือหลัก
  static const muted = Color(0xFF6B6880); // ตัวหนังสือรอง
  static const pageBg = Color(0xFFF4F3F9); // พื้นหลัง
  static const navbar = Color(0xFF201B39); // แถบบน (โทนโรงหนัง)
  static const surface = Colors.white;
}

class AppTheme {
  static ThemeData get light {
    final scheme = ColorScheme.fromSeed(
      seedColor: AppColors.brand,
      brightness: Brightness.light,
    ).copyWith(
      primary: AppColors.brand,
      secondary: AppColors.gold,
      surface: AppColors.surface,
      onSurface: AppColors.ink,
    );

    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      colorScheme: scheme,
      scaffoldBackgroundColor: AppColors.pageBg,
      // แถบบน = โทนเข้มโรงหนัง + ชื่อสีทอง
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.navbar,
        foregroundColor: AppColors.gold2,
        elevation: 2,
        centerTitle: false,
        titleTextStyle: TextStyle(
          color: AppColors.gold2,
          fontSize: 20,
          fontWeight: FontWeight.w800,
          letterSpacing: 0.3,
        ),
        iconTheme: IconThemeData(color: Colors.white),
      ),
      cardTheme: CardThemeData(
        color: AppColors.surface,
        elevation: 2,
        shadowColor: AppColors.ink.withValues(alpha: 0.10),
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        margin: EdgeInsets.zero,
      ),
      // ปุ่ม: touch target ใหญ่ (สูง ≥ 48) มุมโค้ง
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(64, 48),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
          textStyle:
              const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12)),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size(64, 48),
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12)),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          minimumSize: const Size(48, 44), // touch target ขั้นต่ำ
        ),
      ),
      // ช่องกรอก
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12)),
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.brand, width: 2),
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: Colors.white,
        indicatorColor: AppColors.brand050,
        height: 64,
        labelTextStyle: WidgetStateProperty.all(
          const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
        ),
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
      chipTheme: const ChipThemeData(
        labelStyle: TextStyle(fontWeight: FontWeight.w600),
      ),
      dialogTheme: DialogThemeData(
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
      textTheme: const TextTheme(
        titleLarge: TextStyle(
            color: AppColors.ink, fontWeight: FontWeight.bold),
        titleMedium: TextStyle(
            color: AppColors.ink, fontWeight: FontWeight.w600),
        bodyMedium: TextStyle(color: AppColors.ink),
        bodySmall: TextStyle(color: AppColors.muted),
      ),
    );
  }
}
