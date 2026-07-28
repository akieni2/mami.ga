import 'package:flutter/material.dart';

class AppTheme {
  static const Color primary = Color(0xFF1B4D3E);
  static const Color accent = Color(0xFFC4A35A);
  static const Color boardDark = Color(0xFF5C4033);
  static const Color boardLight = Color(0xFFE8D5B7);

  static ThemeData get light => ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: primary, primary: primary),
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
