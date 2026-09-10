class AppConfig {
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://admin.mami.ga/api',
  );

  static const List<String> apiFallbackBaseUrls = [
    'https://admin.mami.ga/api',
    'https://mami.ga/api',
    'https://api.mami.ga/api',
  ];
}
