import 'package:flutter/material.dart';

class AppTheme {
  static const Color primary = Color(0xFF0F8A4C);
  static const Color accent = Color(0xFFE8B923);
  static const Color boardDark = Color(0xFF232323);
  static const Color boardLight = Color(0xFFF4F1E8);

  static ThemeData get light => ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: primary,
          primary: primary,
          secondary: accent,
        ),
        scaffoldBackgroundColor: const Color(0xFFFAF8F1),
        useMaterial3: true,
        appBarTheme: const AppBarTheme(
          backgroundColor: primary,
          foregroundColor: Colors.white,
        ),
        filledButtonTheme: FilledButtonThemeData(
          style: FilledButton.styleFrom(backgroundColor: primary),
        ),
      );
}
