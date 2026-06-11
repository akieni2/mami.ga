/// Feature flags MAMI Taxi V2 — alignés sur `GET /api/app/features` et dart-define.
class AppFeatures {
  const AppFeatures({
    required this.taxiV2Enabled,
    required this.dispatchV2Enabled,
    required this.rideBasePrice,
    required this.ridePricePerKm,
    required this.etaAverageSpeedKmh,
  });

  /// Surcharge via `--dart-define=MAMI_TAXI_V2=true` (prioritaire au build).
  static const bool taxiV2FromEnvironment = bool.fromEnvironment(
    'MAMI_TAXI_V2',
    defaultValue: true,
  );

  final bool taxiV2Enabled;
  final bool dispatchV2Enabled;
  final double rideBasePrice;
  final double ridePricePerKm;
  final double etaAverageSpeedKmh;

  bool get useV2Booking => taxiV2FromEnvironment || taxiV2Enabled;

  factory AppFeatures.defaults() => const AppFeatures(
        taxiV2Enabled: taxiV2FromEnvironment,
        dispatchV2Enabled: false,
        rideBasePrice: AppFeaturesConfig.rideBasePrice,
        ridePricePerKm: AppFeaturesConfig.ridePricePerKm,
        etaAverageSpeedKmh: 25,
      );

  factory AppFeatures.fromJson(Map<String, dynamic> json) {
    return AppFeatures(
      taxiV2Enabled: json['taxi_v2_enabled'] == true || taxiV2FromEnvironment,
      dispatchV2Enabled: json['dispatch_v2_enabled'] == true,
      rideBasePrice: _toDouble(json['ride_base_price'], AppFeaturesConfig.rideBasePrice),
      ridePricePerKm: _toDouble(json['ride_price_per_km'], AppFeaturesConfig.ridePricePerKm),
      etaAverageSpeedKmh: _toDouble(json['eta_average_speed_kmh'], 25),
    );
  }

  static double _toDouble(dynamic value, double fallback) {
    if (value is num) return value.toDouble();
    return fallback;
  }
}

/// Constantes partagées (évite dépendance circulaire avec AppConfig).
class AppFeaturesConfig {
  static const double rideBasePrice = 500;
  static const double ridePricePerKm = 250;
}
