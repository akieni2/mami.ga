import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/map/lat_lng_utils.dart';
import '../../data/rides_repository.dart';
import '../../domain/models/trip_estimate.dart';

/// Paramètres d'estimation (pickup GPS + destination carte).
class TripEstimateRequest {
  const TripEstimateRequest({
    required this.pickup,
    required this.destination,
  });

  final LatLng pickup;
  final LatLng destination;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TripEstimateRequest &&
          pickup.latitude == other.pickup.latitude &&
          pickup.longitude == other.pickup.longitude &&
          destination.latitude == other.destination.latitude &&
          destination.longitude == other.destination.longitude;

  @override
  int get hashCode => Object.hash(
        pickup.latitude,
        pickup.longitude,
        destination.latitude,
        destination.longitude,
      );
}

/// Appelle `POST /api/rides/estimate` lorsque pickup et destination sont définis.
final tripEstimateProvider =
    FutureProvider.autoDispose.family<TripEstimate, TripEstimateRequest>(
  (ref, request) async {
    if (!LatLngUtils.isValid(request.pickup) ||
        !LatLngUtils.isValid(request.destination)) {
      debugPrint('DESTINATION INVALID: trip estimate skipped');
      throw StateError('Coordonnées GPS invalides');
    }

    try {
      final estimate = await ref.read(ridesRepositoryProvider).estimateTrip(
            pickupLatitude: request.pickup.latitude,
            pickupLongitude: request.pickup.longitude,
            destinationLatitude: request.destination.latitude,
            destinationLongitude: request.destination.longitude,
          );
      debugPrint(
        'P1 estimate API response: distance=${estimate.distanceKm}km '
        'duration=${estimate.durationMinutes}min price=${estimate.suggestedPrice}',
      );
      return estimate;
    } catch (e) {
      debugPrint('P1 estimate API error: $e');
      rethrow;
    }
  },
);
