import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/status_chip.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../../driver/presentation/providers/driver_status_provider.dart';
import '../../../location/presentation/providers/location_tracker_provider.dart';
import '../../../rides/presentation/providers/active_ride_provider.dart';
import '../../../rides/presentation/widgets/incoming_ride_card.dart';

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
      final status = ref.read(driverStatusProvider).valueOrNull;
      if (status == DriverUiStatus.online || status == DriverUiStatus.busy) {
        ref.read(locationTrackerProvider.notifier).start();
      }
    });
  }

  Future<void> _toggleOnline(bool online) async {
    await ref.read(driverStatusProvider.notifier).setOnline(online);
    if (online) {
      await ref.read(locationTrackerProvider.notifier).start();
    } else {
      ref.read(locationTrackerProvider.notifier).stop();
    }
  }

  Future<void> _accept(int id) async {
    setState(() => _actionLoading = true);
    try {
      await ref.read(activeRideProvider.notifier).accept(id);
      if (mounted) context.push('/ride/active');
    } finally {
      if (mounted) setState(() => _actionLoading = false);
    }
  }

  Future<void> _reject(int id) async {
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
    final status = ref.watch(driverStatusProvider).valueOrNull ?? DriverUiStatus.offline;
    final rideAsync = ref.watch(activeRideProvider);
    final gpsActive = ref.watch(locationTrackerProvider);

    final ride = rideAsync.valueOrNull;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Tableau de bord'),
        actions: [Padding(
          padding: const EdgeInsets.only(right: 16),
          child: StatusChip(status: status),
        )],
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
          if (rideAsync.isLoading)
            const Center(child: CircularProgressIndicator())
          else if (ride != null && ride.isPending)
            IncomingRideCard(
              ride: ride,
              loading: _actionLoading,
              onAccept: () => _accept(ride.id),
              onReject: () => _reject(ride.id),
            )
          else if (ride != null)
            Card(
              child: ListTile(
                leading: const Icon(Icons.local_taxi, color: AppTheme.primary),
                title: const Text('Course en cours'),
                subtitle: Text('Statut: ${ride.status}'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push('/ride/active'),
              ),
            )
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
                          ? 'En attente de courses…'
                          : 'Passez en ligne pour recevoir des courses',
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
