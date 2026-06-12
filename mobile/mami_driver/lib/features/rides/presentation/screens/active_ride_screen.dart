import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/map/mami_map.dart';
import '../../../../core/widgets/primary_button.dart';
import '../../../location/presentation/providers/location_tracker_provider.dart';
import '../../../location/presentation/providers/user_location_provider.dart';
import '../providers/active_ride_provider.dart';
import '../providers/ride_live_tracking_provider.dart';

class ActiveRideScreen extends ConsumerStatefulWidget {
  const ActiveRideScreen({super.key});

  @override
  ConsumerState<ActiveRideScreen> createState() => _ActiveRideScreenState();
}

class _ActiveRideScreenState extends ConsumerState<ActiveRideScreen> {
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(activeRideProvider.notifier).startHybridTracking();
    });
  }

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
    final gps = ref.watch(userLocationProvider).valueOrNull;
    final live = ride != null
        ? ref.watch(rideLiveTrackingProvider(ride.id))
        : const RideLiveTrackingState();

    if (ride == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Course active')),
        body: const Center(child: Text('Aucune course active')),
      );
    }

    const fallback = LatLng(0.4162, 9.4673);
    final pickup = ride.hasPickupCoordinates
        ? LatLng(ride.pickupLatitude!, ride.pickupLongitude!)
        : fallback;
    final destination = ride.hasDestinationCoordinates
        ? LatLng(ride.destinationLatitude!, ride.destinationLongitude!)
        : fallback;
    final client = pickup;
    final driver = gps ?? live.driverPosition;

    final priceValue = ride.displayPrice;
    final price = priceValue != null
        ? NumberFormat.currency(symbol: 'FCFA ', decimalDigits: 0)
            .format(priceValue)
        : '—';

    return Scaffold(
      body: Column(
        children: [
          Expanded(
            child: Stack(
              children: [
                MamiMap(
                  fullScreen: true,
                  driver: driver,
                  client: client,
                  pickup: pickup,
                  destination: destination,
                  route: live.route.isNotEmpty ? live.route : null,
                ),
                SafeArea(
                  child: Padding(
                    padding: const EdgeInsets.all(8),
                    child: IconButton.filled(
                      style: IconButton.styleFrom(
                        backgroundColor: Colors.white,
                        foregroundColor: Colors.black,
                      ),
                      onPressed: () => context.pop(),
                      icon: const Icon(Icons.arrow_back),
                    ),
                  ),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text('Client: ${ride.client?.name ?? '—'}',
                    style: Theme.of(context).textTheme.titleMedium),
                Text('Statut: ${ride.status}'),
                if (live.etaMinutes != null)
                  Text('ETA : ~${live.etaMinutes} min'),
                if (live.distanceKm != null)
                  Text('Distance : ${live.distanceKm!.toStringAsFixed(2)} km'),
                Text('Tarif estimé: $price'),
                const SizedBox(height: 12),
                if (ride.status == 'accepted')
                  PrimaryButton(
                    label: 'Je suis arrivé',
                    loading: _loading,
                    onPressed: () => _run(() async {
                      await ref
                          .read(activeRideProvider.notifier)
                          .arrived(ride.id);
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
                      await ref
                          .read(activeRideProvider.notifier)
                          .complete(ride.id);
                      ref.read(locationTrackerProvider.notifier).stop();
                      if (mounted) context.go('/');
                    }),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
