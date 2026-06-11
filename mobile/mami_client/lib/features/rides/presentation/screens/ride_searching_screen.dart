import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/theme/app_theme.dart';
import '../providers/active_ride_provider.dart';

class RideSearchingScreen extends ConsumerStatefulWidget {
  const RideSearchingScreen({super.key, required this.rideId});

  final int rideId;

  @override
  ConsumerState<RideSearchingScreen> createState() =>
      _RideSearchingScreenState();
}

class _RideSearchingScreenState extends ConsumerState<RideSearchingScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _pulse;

  @override
  void initState() {
    super.initState();
    _pulse = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    )..repeat(reverse: true);

    ref.read(activeRideProvider.notifier).startHybridTracking(widget.rideId);
    ref.read(activeRideProvider.notifier).refresh(widget.rideId);
  }

  @override
  void dispose() {
    _pulse.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rideAsync = ref.watch(activeRideProvider);

    ref.listen(activeRideProvider, (prev, next) {
      final ride = next.valueOrNull;
      if (ride == null) return;

      if (ride.status == 'cancelled') {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Course annulée')),
        );
        context.go('/');
        return;
      }

      if (ride.status == 'accepted' ||
          ride.status == 'arrived' ||
          ride.status == 'started') {
        context.go('/ride/active/${ride.id}');
      }
    });

    final ride = rideAsync.valueOrNull;
    final statusLabel = ride?.status ?? 'searching';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Recherche chauffeur'),
        automaticallyImplyLeading: false,
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              ScaleTransition(
                scale: Tween<double>(begin: 0.9, end: 1.1).animate(
                  CurvedAnimation(parent: _pulse, curve: Curves.easeInOut),
                ),
                child: Icon(Icons.radar, size: 96, color: AppTheme.primary),
              ),
              const SizedBox(height: 24),
              Text(
                'Recherche d\'un chauffeur…',
                style: Theme.of(context).textTheme.titleLarge,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 12),
              Text(
                'Course #${widget.rideId} — statut : $statusLabel',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.grey.shade600),
              ),
              if (ride != null) ...[
                const SizedBox(height: 16),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Icon(Icons.trip_origin,
                                size: 18, color: Colors.green),
                            const SizedBox(width: 8),
                            Expanded(child: Text(ride.pickupDisplay)),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            const Icon(Icons.flag, size: 18, color: Colors.red),
                            const SizedBox(width: 8),
                            Expanded(child: Text(ride.destinationDisplay)),
                          ],
                        ),
                        if (ride.proposedPrice != null) ...[
                          const SizedBox(height: 8),
                          Text(
                            'Prix proposé : ${ride.proposedPrice!.toStringAsFixed(0)} FCFA',
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                        ],
                        if (ride.paymentMethod != null) ...[
                          const SizedBox(height: 4),
                          Text(
                            'Paiement : ${ride.paymentMethod!.label}',
                            style: TextStyle(color: Colors.grey.shade700),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
              ],
              if (ride?.driver != null) ...[
                const SizedBox(height: 8),
                Text('Chauffeur assigné : ${ride!.driver!.name}'),
              ],
              const SizedBox(height: 32),
              const CircularProgressIndicator(),
              const SizedBox(height: 32),
              TextButton(
                onPressed: () {
                  ref.read(activeRideProvider.notifier).clear();
                  context.go('/');
                },
                child: const Text('Annuler'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
