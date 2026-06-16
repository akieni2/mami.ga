import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/map/mami_map.dart';
import '../../../location/presentation/providers/user_location_provider.dart';
import '../providers/active_ride_provider.dart';
import '../providers/ride_live_tracking_provider.dart';

class ActiveRideScreen extends ConsumerStatefulWidget {
  const ActiveRideScreen({super.key, required this.rideId});

  final int rideId;

  @override
  ConsumerState<ActiveRideScreen> createState() => _ActiveRideScreenState();
}

class _ActiveRideScreenState extends ConsumerState<ActiveRideScreen> {
  bool _arrivedNotified = false;

  @override
  void initState() {
    super.initState();
    ref.read(activeRideProvider.notifier).startHybridTracking(widget.rideId);
    ref.read(activeRideProvider.notifier).refresh(widget.rideId);
  }

  String _statusLabel(String status) {
    return switch (status) {
      'pending' => 'En attente',
      'accepted' => 'Chauffeur en route',
      'arrived' => 'Chauffeur arrivé',
      'started' => 'Course en cours',
      'completed' => 'Terminée',
      'cancelled' => 'Annulée',
      _ => status,
    };
  }

  Future<void> _showArrivedDialog() async {
    if (!mounted) return;
    await showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Chauffeur arrivé'),
        content: const Text('Votre chauffeur est arrivé.'),
        actions: [
          FilledButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  Future<void> _showCompletionFlow() async {
    if (!mounted) return;

    var rating = 5;
    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setState) => AlertDialog(
          title: const Text('Course terminée'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('Merci d\'avoir voyagé avec MAMI !'),
              const SizedBox(height: 16),
              const Text('Notez votre chauffeur'),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(5, (i) {
                  return IconButton(
                    onPressed: () => setState(() => rating = i + 1),
                    icon: Icon(
                      i < rating ? Icons.star : Icons.star_border,
                      color: Colors.amber,
                    ),
                  );
                }),
              ),
            ],
          ),
          actions: [
            FilledButton(
              onPressed: () => Navigator.of(ctx).pop(),
              child: const Text('Terminer'),
            ),
          ],
        ),
      ),
    );

    ref.read(activeRideProvider.notifier).clear();
    if (mounted) context.go('/');
  }

  @override
  Widget build(BuildContext context) {
    final rideAsync = ref.watch(activeRideProvider);
    final live = ref.watch(rideLiveTrackingProvider(widget.rideId));
    final userPos = ref.watch(userLocationProvider).valueOrNull?.position;

    ref.listen(activeRideProvider, (prev, next) {
      final ride = next.valueOrNull;
      if (ride == null) return;

      if (ride.status == 'arrived' &&
          !_arrivedNotified &&
          prev?.valueOrNull?.status != 'arrived') {
        _arrivedNotified = true;
        _showArrivedDialog();
      }

      if (ride.isCompleted || ride.status == 'cancelled') {
        if (ride.isCompleted) {
          _showCompletionFlow();
        } else {
          ref.read(activeRideProvider.notifier).clear();
          context.go('/');
        }
      }
    });

    return Scaffold(
      body: rideAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (ride) {
          if (ride == null) {
            return const Center(child: Text('Aucune course active'));
          }

          if (!ride.hasPickupCoordinates || !ride.hasDestinationCoordinates) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      'Course #${ride.id}',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    const SizedBox(height: 12),
                    Text('Départ : ${ride.pickupDisplay}'),
                    Text('Destination : ${ride.destinationDisplay}'),
                    const SizedBox(height: 8),
                    Text(_statusLabel(ride.status)),
                  ],
                ),
              ),
            );
          }

          final pickup = LatLng(ride.pickupLatitude!, ride.pickupLongitude!);
          final destination = LatLng(
            ride.destinationLatitude!,
            ride.destinationLongitude!,
          );
          LatLng? driver = live.driverPosition;
          if (driver == null &&
              ride.driver?.latitude != null &&
              ride.driver?.longitude != null) {
            driver = LatLng(
              ride.driver!.latitude!,
              ride.driver!.longitude!,
            );
          }

          final price = ride.proposedPrice ?? ride.suggestedPrice ?? ride.estimatedPrice;
          final priceLabel = price != null
              ? NumberFormat.currency(symbol: 'FCFA ', decimalDigits: 0)
                  .format(price)
              : '—';

          return Column(
            children: [
              Expanded(
                child: Stack(
                  children: [
                    MamiMap(
                      fullScreen: true,
                      user: userPos,
                      client: userPos ?? pickup,
                      pickup: pickup,
                      destination: destination,
                      driver: driver,
                      route: live.route.isNotEmpty ? live.route : null,
                      interactive: true,
                    ),
                    SafeArea(
                      child: Padding(
                        padding: const EdgeInsets.all(8),
                        child: IconButton.filled(
                          style: IconButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: Colors.black,
                          ),
                          onPressed: () => context.go('/'),
                          icon: const Icon(Icons.close),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _statusLabel(ride.status),
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    if (live.etaMinutes != null && ride.status != 'started')
                      Text('Arrivée estimée : ~${live.etaMinutes} min'),
                    if (live.distanceKm != null)
                      Text(
                        ride.status == 'started'
                            ? 'Distance restante : ${live.distanceKm!.toStringAsFixed(2)} km'
                            : 'Distance chauffeur : ${live.distanceKm!.toStringAsFixed(2)} km',
                      ),
                    const SizedBox(height: 8),
                    if (ride.driver != null) ...[
                      Row(
                        children: [
                          CircleAvatar(
                            child: Text(
                              ride.driver!.name.isNotEmpty
                                  ? ride.driver!.name[0].toUpperCase()
                                  : 'C',
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  ride.driver!.name,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                Text(ride.driver!.vehicleLabel),
                                if (ride.driver!.plateNumber != null)
                                  Text('Plaque : ${ride.driver!.plateNumber}'),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ],
                    const SizedBox(height: 8),
                    Text('Tarif estimé : $priceLabel'),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
