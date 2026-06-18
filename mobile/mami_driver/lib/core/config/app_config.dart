/// Configuration centralisée — production MAMI.GA (domaines).
///
/// Surcharge au build :
/// `flutter run --dart-define=API_BASE_URL=https://api.mami.ga/api`
class AppConfig {
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://api.mami.ga/api',
  );

  static const String portalUrl = String.fromEnvironment(
    'PORTAL_URL',
    defaultValue: 'https://mami.ga',
  );

  static const String adminUrl = String.fromEnvironment(
    'ADMIN_URL',
    defaultValue: 'https://admin.mami.ga',
  );

  /// WebSocket Reverb (Pusher) — wss://ws.mami.ga
  static const String websocketUrl = String.fromEnvironment(
    'WEBSOCKET_URL',
    defaultValue: 'wss://ws.mami.ga',
  );

  static const Duration gpsInterval = Duration(seconds: 10);
  static const Duration ridePollInterval = Duration(seconds: 8);

  /// Polling carte / tracking si Reverb indisponible.
  static const Duration trackingPollInterval = Duration(seconds: 8);

  /// P3 — poll offres plus fréquent pour affichage < 10 s.
  static const Duration offerPollInterval = Duration(seconds: 5);
}
