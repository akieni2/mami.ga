/// Feature flags MAMI Super App — alignés sur `GET /api/app/features` et dart-define.
class AppFeatures {
  const AppFeatures({
    required this.superAppEnabled,
    required this.taxiV2Enabled,
    required this.dispatchV2Enabled,
    required this.modules,
    required this.rideBasePrice,
    required this.ridePricePerKm,
    required this.etaAverageSpeedKmh,
  });

  /// Surcharge via `--dart-define=MAMI_TAXI_V2=true` (prioritaire au build).
  static const bool taxiV2FromEnvironment = bool.fromEnvironment(
    'MAMI_TAXI_V2',
    defaultValue: true,
  );

  final bool superAppEnabled;
  final bool taxiV2Enabled;
  final bool dispatchV2Enabled;
  final Map<String, bool> modules;
  final double rideBasePrice;
  final double ridePricePerKm;
  final double etaAverageSpeedKmh;

  bool get useV2Booking => taxiV2FromEnvironment || taxiV2Enabled;

  bool isModuleEnabled(String slug) {
    if (slug == 'taxi') return true;
    return modules[slug] == true;
  }

  factory AppFeatures.defaults() => const AppFeatures(
        superAppEnabled: true,
        taxiV2Enabled: taxiV2FromEnvironment,
        dispatchV2Enabled: false,
        modules: {
          'taxi': true,
          'carpool': false,
          'transport': false,
          'commerce': false,
          'municipality': false,
        },
        rideBasePrice: AppFeaturesConfig.rideBasePrice,
        ridePricePerKm: AppFeaturesConfig.ridePricePerKm,
        etaAverageSpeedKmh: 25,
      );

  factory AppFeatures.fromJson(Map<String, dynamic> json) {
    final modulesRaw = json['modules'];
    final modules = <String, bool>{
      'taxi': true,
      'carpool': false,
      'transport': false,
      'commerce': false,
      'municipality': false,
    };

    if (modulesRaw is Map) {
      modulesRaw.forEach((key, value) {
        modules[key.toString()] = value == true;
      });
    }

    modules['taxi'] = true;

    return AppFeatures(
      superAppEnabled: json['super_app_enabled'] != false,
      taxiV2Enabled: json['taxi_v2_enabled'] == true || taxiV2FromEnvironment,
      dispatchV2Enabled: json['dispatch_v2_enabled'] == true,
      modules: modules,
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
