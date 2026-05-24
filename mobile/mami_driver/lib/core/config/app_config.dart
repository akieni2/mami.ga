class AppConfig {
  /// Override at build time:
  /// flutter run --dart-define=API_BASE_URL=https://mami.ga/api
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api',
  );

  static const Duration gpsInterval = Duration(seconds: 10);
  static const Duration ridePollInterval = Duration(seconds: 8);
}
