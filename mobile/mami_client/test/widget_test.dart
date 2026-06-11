import 'package:flutter_test/flutter_test.dart';
import 'package:mami_client/features/rides/domain/models/trip_estimate.dart';

void main() {
  test('TripEstimate parses API estimate payload', () {
    final estimate = TripEstimate.fromJson({
      'distance_km': 3.491,
      'duration_minutes': 9,
      'suggested_price': 1373,
    });

    expect(estimate.distanceKm, 3.491);
    expect(estimate.durationMinutes, 9);
    expect(estimate.suggestedPrice, 1373);
  });
}
