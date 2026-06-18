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

  static const double rideBasePrice = 500;
  static const double ridePricePerKm = 250;

  static const Duration ridePollInterval = Duration(seconds: 5);
  static const Duration trackingPollInterval = Duration(seconds: 8);
  static const Duration splashMinDuration = Duration(seconds: 2);
}
