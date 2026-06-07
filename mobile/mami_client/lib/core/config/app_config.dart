class AppConfig {
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://63.142.241.105/api',
  );

  static const double rideBasePrice = 500;
  static const double ridePricePerKm = 250;

  static const Duration ridePollInterval = Duration(seconds: 5);
  static const Duration splashMinDuration = Duration(seconds: 2);
}
