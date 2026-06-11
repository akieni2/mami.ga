/// Estimation trajet (P1) — réponse `POST /api/rides/estimate`.
class TripEstimate {
  const TripEstimate({
    required this.distanceKm,
    required this.durationMinutes,
    required this.suggestedPrice,
  });

  final double distanceKm;
  final int durationMinutes;
  final double suggestedPrice;

  factory TripEstimate.fromJson(Map<String, dynamic> json) {
    return TripEstimate(
      distanceKm: (json['distance_km'] as num).toDouble(),
      durationMinutes: (json['duration_minutes'] as num).toInt(),
      suggestedPrice: (json['suggested_price'] as num).toDouble(),
    );
  }
}
