import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:jb_ludo/core/theme/app_theme.dart';

void main() {
  test('AppTheme primary is defined', () {
    expect(AppTheme.primary, isNot(equals(const Color(0x00000000))));
  });
}
