import 'ride_model.dart';

/// Offre dispatch P3 — miroir `RideOfferResource`.
class RideOfferModel {
  const RideOfferModel({
    required this.id,
    required this.rideId,
    required this.driverId,
    required this.status,
    required this.offeredPrice,
    required this.distanceToPickupKm,
    required this.radiusWave,
    required this.expiresAt,
    this.dispatchScore,
    this.ride,
  });

  final int id;
  final int rideId;
  final int driverId;
  final String status;
  final double offeredPrice;
  final double distanceToPickupKm;
  final String radiusWave;
  final String expiresAt;
  final double? dispatchScore;
  final RideModel? ride;

  bool get isPending => status == 'pending';

  String get pickupDisplay => ride?.pickupDisplay ?? '—';
  String get destinationDisplay => ride?.destinationDisplay ?? '—';

  factory RideOfferModel.fromJson(Map<String, dynamic> json) {
    final rideJson = json['ride'] as Map<String, dynamic>?;

    return RideOfferModel(
      id: (json['id'] as num).toInt(),
      rideId: (json['ride_id'] as num).toInt(),
      driverId: (json['driver_id'] as num).toInt(),
      status: json['status'] as String,
      offeredPrice: (json['offered_price'] as num).toDouble(),
      distanceToPickupKm: (json['distance_to_pickup_km'] as num).toDouble(),
      radiusWave: json['radius_wave'] as String? ?? '',
      expiresAt: json['expires_at'] as String,
      dispatchScore: (json['dispatch_score'] as num?)?.toDouble(),
      ride: rideJson != null ? RideModel.fromJson(rideJson) : null,
    );
  }
}
