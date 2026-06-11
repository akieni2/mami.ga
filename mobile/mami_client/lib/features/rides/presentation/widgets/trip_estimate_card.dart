import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../../../core/theme/app_theme.dart';
import '../../domain/models/trip_estimate.dart';

/// Récapitulatif distance / durée / prix conseillé (P1).
class TripEstimateCard extends StatelessWidget {
  const TripEstimateCard({
    super.key,
    required this.estimate,
  });

  final TripEstimate estimate;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'FCFA ', decimalDigits: 0);

    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              'Estimation du trajet',
              style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),
            _Row(
              icon: Icons.straighten,
              label: 'Distance',
              value: '${estimate.distanceKm.toStringAsFixed(1)} km',
            ),
            const SizedBox(height: 8),
            _Row(
              icon: Icons.schedule,
              label: 'Durée estimée',
              value: '${estimate.durationMinutes} min',
            ),
            const SizedBox(height: 8),
            _Row(
              icon: Icons.payments_outlined,
              label: 'Prix conseillé',
              value: currency.format(estimate.suggestedPrice),
              emphasized: true,
            ),
          ],
        ),
      ),
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({
    required this.icon,
    required this.label,
    required this.value,
    this.emphasized = false,
  });

  final IconData icon;
  final String label;
  final String value;
  final bool emphasized;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 20, color: AppTheme.primary),
        const SizedBox(width: 8),
        Expanded(child: Text(label)),
        Text(
          value,
          style: TextStyle(
            fontWeight: emphasized ? FontWeight.bold : FontWeight.w600,
            fontSize: emphasized ? 16 : 14,
            color: emphasized ? AppTheme.surfaceDark : null,
          ),
        ),
      ],
    );
  }
}
