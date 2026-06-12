import 'package:flutter/material.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/primary_button.dart';
import '../../domain/models/ride_offer_model.dart';

/// Carte offre dispatch P3 — text-first.
class RideOfferCard extends StatelessWidget {
  const RideOfferCard({
    super.key,
    required this.offer,
    required this.onAccept,
    required this.onReject,
    this.loading = false,
  });

  final RideOfferModel offer;
  final VoidCallback onAccept;
  final VoidCallback onReject;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    final distance = offer.distanceToPickupKm;
    final price = offer.offeredPrice;
    final payment = offer.ride?.paymentMethod?.label;

    return Card(
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.local_offer, color: AppTheme.primary),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Nouvelle offre de course',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            _labelRow(Icons.trip_origin, Colors.green, 'Départ', offer.pickupDisplay),
            const SizedBox(height: 8),
            _labelRow(Icons.flag, Colors.red, 'Destination', offer.destinationDisplay),
            const SizedBox(height: 12),
            Text(
              'Prix proposé : ${price.toStringAsFixed(0)} FCFA',
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: AppTheme.primary,
              ),
            ),
            if (payment != null) ...[
              const SizedBox(height: 4),
              Text('Paiement : $payment', style: TextStyle(color: Colors.grey.shade700)),
            ],
            const SizedBox(height: 8),
            Text(
              'Distance : ${distance < 1 ? '${(distance * 1000).round()} m' : '${distance.toStringAsFixed(1)} km'}',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            if (offer.ride?.client != null) ...[
              const SizedBox(height: 8),
              Text('Client : ${offer.ride!.client!.name}'),
            ],
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

  Widget _labelRow(IconData icon, Color color, String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: color),
        const SizedBox(width: 8),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
              Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
            ],
          ),
        ),
      ],
    );
  }
}
