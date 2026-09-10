class AppConfig {
  /// IP VPS (bypass DNS mobile). Nécessite Host virtuel + assouplissement TLS.
  static const String apiDirectIp = '63.142.241.105';

  static const String apiDirectIpBaseUrl = 'https://$apiDirectIp/api';

  /// Contournement DNS mobile : IP VPS par défaut.
  /// Surcharge : `--dart-define=API_BASE_URL=https://api.mami.ga/api`
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: apiDirectIpBaseUrl,
  );

  /// Affiché sur l'écran login pour vérifier la bonne APK.
  static const String appVersion = '1.0.7';

  /// Host HTTP virtuel nginx quand on passe par l'IP.
  static const String apiVirtualHost = 'api.mami.ga';

  /// Hôtes de secours si l'IP / un host échoue.
  static const List<String> apiFallbackBaseUrls = [
    apiDirectIpBaseUrl,
    'https://api.mami.ga/api',
    'https://admin.mami.ga/api',
    'https://mami.ga/api',
  ];

  /// Origine sans `/api` (ex. health Laravel `/up`).
  static String originFrom(String apiUrl) =>
      apiUrl.replaceFirst(RegExp(r'/api/?$'), '');

  static bool isDirectIpBaseUrl(String url) => url.contains(apiDirectIp);
}
