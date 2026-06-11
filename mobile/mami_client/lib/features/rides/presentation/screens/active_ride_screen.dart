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

  @override
  Widget build(BuildContext context) {
    final rideAsync = ref.watch(activeRideProvider);
    final live = ref.watch(rideLiveTrackingProvider(widget.rideId));
    final userPos = ref.watch(userLocationProvider).valueOrNull?.position;

    ref.listen(activeRideProvider, (prev, next) {
      final ride = next.valueOrNull;
      if (ride?.isCompleted == true || ride?.status == 'cancelled') {
        ref.read(activeRideProvider.notifier).clear();
        context.go('/history');
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
                    Text('Course #${ride.id}', style: Theme.of(context).textTheme.titleLarge),
                    const SizedBox(height: 12),
                    Text('Départ : ${ride.pickupDisplay}'),
                    Text('Destination : ${ride.destinationDisplay}'),
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

          final price = ride.estimatedPrice != null
              ? NumberFormat.currency(symbol: 'FCFA ', decimalDigits: 0)
                  .format(ride.estimatedPrice)
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
                    if (live.etaMinutes != null)
                      Text('Arrivée estimée : ~${live.etaMinutes} min'),
                    if (live.distanceKm != null)
                      Text(
                        'Distance chauffeur : ${live.distanceKm!.toStringAsFixed(2)} km',
                      ),
                    const SizedBox(height: 8),
                    if (ride.driver != null)
                      Text(
                        '${ride.driver!.name} · ${ride.driver!.vehicleLabel}',
                      ),
                    Text('Tarif estimé : $price'),
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
