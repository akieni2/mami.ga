import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/widgets/primary_button.dart';
import '../../../../core/widgets/ride_map.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../providers/active_ride_provider.dart';

class ActiveRideScreen extends ConsumerStatefulWidget {
  const ActiveRideScreen({super.key});

  @override
  ConsumerState<ActiveRideScreen> createState() => _ActiveRideScreenState();
}

class _ActiveRideScreenState extends ConsumerState<ActiveRideScreen> {
  bool _loading = false;

  Future<void> _run(Future<void> Function() action) async {
    setState(() => _loading = true);
    try {
      await action();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final ride = ref.watch(activeRideProvider).valueOrNull;
    final user = ref.watch(authStateProvider).valueOrNull;
    final driverLat = user?.driver?.latitude;
    final driverLng = user?.driver?.longitude;

    if (ride == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Course active')),
        body: const Center(child: Text('Aucune course active')),
      );
    }

    final price = ride.estimatedPrice != null
        ? NumberFormat.currency(symbol: 'FCFA ', decimalDigits: 0)
            .format(ride.estimatedPrice)
        : '—';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Course active'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.pop(),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          RideMap(
            pickup: LatLng(ride.pickupLatitude, ride.pickupLongitude),
            destination: LatLng(
              ride.destinationLatitude,
              ride.destinationLongitude,
            ),
            driver: driverLat != null && driverLng != null
                ? LatLng(driverLat, driverLng)
                : null,
            height: 240,
          ),
          const SizedBox(height: 16),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Client: ${ride.client?.name ?? '—'}',
                      style: Theme.of(context).textTheme.titleMedium),
                  Text('Statut: ${ride.status}'),
                  Text('Tarif estimé: $price'),
                  if (ride.distanceToPickupKm != null)
                    Text(
                      'Distance pickup: ${ride.distanceToPickupKm!.toStringAsFixed(2)} km',
                    ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          if (ride.status == 'accepted')
            PrimaryButton(
              label: 'Je suis arrivé',
              loading: _loading,
              onPressed: () => _run(() async {
                await ref.read(activeRideProvider.notifier).arrived(ride.id);
              }),
            ),
          if (ride.status == 'arrived')
            PrimaryButton(
              label: 'Démarrer la course',
              loading: _loading,
              onPressed: () => _run(() async {
                await ref.read(activeRideProvider.notifier).start(ride.id);
              }),
            ),
          if (ride.status == 'started')
            PrimaryButton(
              label: 'Terminer la course',
              loading: _loading,
              color: Colors.green,
              onPressed: () => _run(() async {
                await ref.read(activeRideProvider.notifier).complete(ride.id);
                if (mounted) context.go('/');
              }),
            ),
        ],
      ),
    );
  }
}
