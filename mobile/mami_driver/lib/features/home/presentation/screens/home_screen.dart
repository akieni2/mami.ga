import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/status_chip.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../../driver/presentation/providers/driver_status_provider.dart';
import '../../../location/presentation/providers/location_tracker_provider.dart';
import '../../../rides/domain/models/ride_offer_model.dart';
import '../../../rides/presentation/providers/active_ride_provider.dart';
import '../../../rides/presentation/providers/pending_offers_provider.dart';
import '../../../rides/presentation/widgets/incoming_ride_card.dart';
import '../../../rides/presentation/widgets/ride_offer_card.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  bool _actionLoading = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(activeRideProvider.notifier).startHybridTracking();
      ref.read(activeRideProvider.notifier).refresh();
      ref.read(pendingOffersProvider.notifier).startHybridTracking();
      ref.read(pendingOffersProvider.notifier).refresh();
      final status = ref.read(driverStatusProvider).valueOrNull;
      if (status == DriverUiStatus.online || status == DriverUiStatus.busy) {
        ref.read(locationTrackerProvider.notifier).start();
      }
      final ride = ref.read(activeRideProvider).valueOrNull;
      if (ride != null && ride.isAccepted && mounted) {
        context.go('/ride/active');
      }
    });
  }

  Future<void> _toggleOnline(bool online) async {
    await ref.read(driverStatusProvider.notifier).setOnline(online);
    if (online) {
      await ref.read(locationTrackerProvider.notifier).start();
      ref.read(pendingOffersProvider.notifier).startHybridTracking();
      ref.read(pendingOffersProvider.notifier).refresh();
    } else {
      ref.read(locationTrackerProvider.notifier).stop();
      ref.read(pendingOffersProvider.notifier).stopTracking();
    }
  }

  Future<void> _acceptOffer(RideOfferModel offer) async {
    setState(() => _actionLoading = true);
    try {
      final ride = await ref
          .read(activeRideProvider.notifier)
          .acceptOffer(offer.rideId, offer.id);
      await ref.read(pendingOffersProvider.notifier).refresh();
      if (mounted && ride.isAccepted) {
        ref.read(locationTrackerProvider.notifier).start();
        context.go('/ride/active');
      }
    } finally {
      if (mounted) setState(() => _actionLoading = false);
    }
  }

  Future<void> _rejectOffer(RideOfferModel offer) async {
    setState(() => _actionLoading = true);
    try {
      await ref.read(pendingOffersProvider.notifier).rejectOffer(offer);
    } finally {
      if (mounted) setState(() => _actionLoading = false);
    }
  }

  Future<void> _acceptV1(int id) async {
    setState(() => _actionLoading = true);
    try {
      await ref.read(activeRideProvider.notifier).accept(id);
      if (mounted) {
        ref.read(locationTrackerProvider.notifier).start();
        context.go('/ride/active');
      }
    } finally {
      if (mounted) setState(() => _actionLoading = false);
    }
  }

  Future<void> _rejectV1(int id) async {
    setState(() => _actionLoading = true);
    try {
      await ref.read(activeRideProvider.notifier).reject(id);
    } finally {
      if (mounted) setState(() => _actionLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authStateProvider).valueOrNull;
    final status =
        ref.watch(driverStatusProvider).valueOrNull ?? DriverUiStatus.offline;
    final rideAsync = ref.watch(activeRideProvider);
    final offersAsync = ref.watch(pendingOffersProvider);
    final gpsActive = ref.watch(locationTrackerProvider);

    final ride = rideAsync.valueOrNull;
    final offers = offersAsync.valueOrNull ?? [];
    final hasActiveRide = ride != null && ride.isAccepted;

    ref.listen(activeRideProvider, (prev, next) {
      final activeRide = next.valueOrNull;
      if (activeRide == null || !activeRide.isAccepted) return;
      final wasActive = prev?.valueOrNull?.isAccepted ?? false;
      if (!wasActive && mounted) {
        ref.read(locationTrackerProvider.notifier).start();
        context.go('/ride/active');
      }
    });

    return Scaffold(
      appBar: AppBar(
        title: const Text('Tableau de bord'),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 16),
            child: StatusChip(status: status),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Bonjour, ${user?.name ?? 'Chauffeur'}',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  if (user?.driver?.vehicleLabel != null)
                    Text(user!.driver!.vehicleLabel!),
                  const SizedBox(height: 16),
                  Text('Statut', style: Theme.of(context).textTheme.titleSmall),
                  const SizedBox(height: 8),
                  SegmentedButton<DriverUiStatus>(
                    segments: const [
                      ButtonSegment(
                        value: DriverUiStatus.offline,
                        label: Text('Hors ligne'),
                        icon: Icon(Icons.power_settings_new),
                      ),
                      ButtonSegment(
                        value: DriverUiStatus.online,
                        label: Text('En ligne'),
                        icon: Icon(Icons.check_circle_outline),
                      ),
                      ButtonSegment(
                        value: DriverUiStatus.busy,
                        label: Text('Occupé'),
                        icon: Icon(Icons.directions_car),
                        enabled: false,
                      ),
                    ],
                    selected: {status},
                    onSelectionChanged: status == DriverUiStatus.busy
                        ? null
                        : (set) {
                            final next = set.first;
                            if (next == DriverUiStatus.busy) return;
                            _toggleOnline(next == DriverUiStatus.online);
                          },
                  ),
                  const SizedBox(height: 8),
                  Text(
                    gpsActive
                        ? 'GPS actif — envoi toutes les 10 s'
                        : 'GPS inactif',
                    style: TextStyle(
                      color: gpsActive ? Colors.green : Colors.grey,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          if (offersAsync.hasError)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    const Icon(Icons.error_outline, color: Colors.red),
                    const SizedBox(height: 8),
                    Text(
                      'Erreur chargement des offres',
                      style: Theme.of(context).textTheme.titleSmall,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${offersAsync.error}',
                      style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
                    ),
                    TextButton(
                      onPressed: () =>
                          ref.read(pendingOffersProvider.notifier).refresh(),
                      child: const Text('Réessayer'),
                    ),
                  ],
                ),
              ),
            )
          else if (rideAsync.isLoading && offersAsync.isLoading)
            const Center(child: CircularProgressIndicator())
          else if (hasActiveRide)
            Card(
              child: ListTile(
                leading: const Icon(Icons.local_taxi, color: AppTheme.primary),
                title: const Text('Course en cours'),
                subtitle: Text('Statut: ${ride.status}'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/ride/active'),
              ),
            )
          else if (offers.isNotEmpty)
            ...offers.map(
              (offer) => Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: RideOfferCard(
                  offer: offer,
                  loading: _actionLoading,
                  onAccept: () => _acceptOffer(offer),
                  onReject: () => _rejectOffer(offer),
                ),
              ),
            )
          else if (ride != null && ride.isPending)
            IncomingRideCard(
              ride: ride,
              loading: _actionLoading,
              onAccept: () => _acceptV1(ride.id),
              onReject: () => _rejectV1(ride.id),
            )
          else if (offersAsync.isLoading && offers.isEmpty)
            const Center(child: CircularProgressIndicator())
          else
            Card(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  children: [
                    Icon(
                      Icons.radar,
                      size: 48,
                      color: status == DriverUiStatus.online
                          ? AppTheme.primary
                          : Colors.grey,
                    ),
                    const SizedBox(height: 12),
                    Text(
                      status == DriverUiStatus.online
                          ? 'En attente d\'offres de course…'
                          : 'Passez en ligne pour recevoir des offres',
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}
