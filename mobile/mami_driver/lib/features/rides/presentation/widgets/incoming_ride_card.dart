import 'package:flutter/material.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/widgets/primary_button.dart';
import '../../../../core/widgets/ride_map.dart';
import '../../domain/models/ride_model.dart';

class IncomingRideCard extends StatelessWidget {
  const IncomingRideCard({
    super.key,
    required this.ride,
    required this.onAccept,
    required this.onReject,
    this.loading = false,
  });

  final RideModel ride;
  final VoidCallback onAccept;
  final VoidCallback onReject;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    final distance = ride.distanceToPickupKm;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.notifications_active, color: Colors.amber),
                const SizedBox(width: 8),
                Text(
                  'Nouvelle course',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text('Client: ${ride.client?.name ?? '—'}'),
            if (ride.client?.phone != null)
              Text('Tél: ${ride.client!.phone}'),
            const SizedBox(height: 8),
            if (distance != null)
              Text(
                'Distance pickup: ${distance.toStringAsFixed(2)} km',
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            const SizedBox(height: 12),
            RideMap(
              pickup: LatLng(ride.pickupLatitude, ride.pickupLongitude),
              destination: LatLng(
                ride.destinationLatitude,
                ride.destinationLongitude,
              ),
              height: 180,
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: loading ? null : onReject,
                    child: const Text('Refuser'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: PrimaryButton(
                    label: 'Accepter',
                    loading: loading,
                    onPressed: onAccept,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
