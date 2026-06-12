class AppConfig {
  /// Override at build time:
  /// flutter run --dart-define=API_BASE_URL=https://mami.ga/api

  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://63.142.241.105/api',
  );

  static const Duration gpsInterval = Duration(seconds: 10);
  static const Duration ridePollInterval = Duration(seconds: 8);
  /// P3 — poll offres plus fréquent pour affichage < 10 s.
  static const Duration offerPollInterval = Duration(seconds: 5);
}
