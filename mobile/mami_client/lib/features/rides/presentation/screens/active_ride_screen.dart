import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/widgets/ride_map.dart';
import '../../data/rides_repository.dart';
import '../providers/active_ride_provider.dart';

class ActiveRideScreen extends ConsumerStatefulWidget {
  const ActiveRideScreen({super.key, required this.rideId});

  final int rideId;

  @override
  ConsumerState<ActiveRideScreen> createState() => _ActiveRideScreenState();
}

class _ActiveRideScreenState extends ConsumerState<ActiveRideScreen> {
  Map<String, dynamic>? _tracking;

  @override
  void initState() {
    super.initState();
    ref.read(activeRideProvider.notifier).startHybridTracking(widget.rideId);
    ref.read(activeRideProvider.notifier).refresh(widget.rideId);
    _loadTracking();
  }

  Future<void> _loadTracking() async {
    try {
      final data =
          await ref.read(ridesRepositoryProvider).fetchTracking(widget.rideId);
      if (mounted) setState(() => _tracking = data);
    } catch (_) {}
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

    ref.listen(activeRideProvider, (prev, next) {
      final ride = next.valueOrNull;
      if (ride?.isCompleted == true || ride?.status == 'cancelled') {
        ref.read(activeRideProvider.notifier).clear();
        context.go('/history');
      }
    });

    return Scaffold(
      appBar: AppBar(
        title: const Text('Course en cours'),
        leading: IconButton(
          icon: const Icon(Icons.close),
          onPressed: () => context.go('/'),
        ),
      ),
      body: rideAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (ride) {
          if (ride == null) {
            return const Center(child: Text('Aucune course active'));
          }

          final driver = ride.driver;
          final driverLat = _tracking?['driver_latitude'] as num? ??
              driver?.latitude;
          final driverLng = _tracking?['driver_longitude'] as num? ??
              driver?.longitude;

          final pickup = LatLng(ride.pickupLatitude, ride.pickupLongitude);
          final destination =
              LatLng(ride.destinationLatitude, ride.destinationLongitude);

          final price = ride.estimatedPrice != null
              ? NumberFormat.currency(symbol: 'FCFA ', decimalDigits: 0)
                  .format(ride.estimatedPrice)
              : '—';

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              RideMap(
                center: pickup,
                pickup: pickup,
                destination: destination,
                driver: driverLat != null && driverLng != null
                    ? LatLng(driverLat.toDouble(), driverLng.toDouble())
                    : null,
                height: 240,
              ),
              const SizedBox(height: 8),
              Text(
                'Carte temps réel — structure prête (polling tracking)',
                style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              Card(
                child: Padding(
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
                      const SizedBox(height: 12),
                      if (driver != null) ...[
                        ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: const CircleAvatar(
                            child: Icon(Icons.person),
                          ),
                          title: Text(driver.name),
                          subtitle: Text(driver.phone ?? ''),
                        ),
                        ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: const Icon(Icons.directions_car),
                          title: Text(driver.vehicleLabel),
                          subtitle: Text(
                            [
                              if (driver.color != null) driver.color,
                              if (driver.plateNumber != null)
                                'Plaque ${driver.plateNumber}',
                            ].whereType<String>().join(' · '),
                          ),
                        ),
                        if (driver.rating != null)
                          Text('Note : ${driver.rating!.toStringAsFixed(1)} ★'),
                      ],
                      const Divider(),
                      Text('Tarif estimé : $price'),
                      Text('Course #${ride.id}'),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
