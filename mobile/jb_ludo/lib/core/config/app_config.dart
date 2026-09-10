class AppConfig {
  /// URL API de production (même convention que mami_client / mami_driver).
  /// Surcharge build : `--dart-define=API_BASE_URL=https://api.mami.ga/api`
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://api.mami.ga/api',
  );

  /// Hôtes de secours si le DNS / TLS du host principal échoue sur mobile.
  static const List<String> apiFallbackBaseUrls = [
    'https://api.mami.ga/api',
    'https://admin.mami.ga/api',
    'https://mami.ga/api',
  ];

  /// Origine sans `/api` (ex. health Laravel `/up`).
  static String originFrom(String apiUrl) =>
      apiUrl.replaceFirst(RegExp(r'/api/?$'), '');
}
