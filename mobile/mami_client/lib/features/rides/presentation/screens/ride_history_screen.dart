import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../providers/ride_history_provider.dart';

class RideHistoryScreen extends ConsumerWidget {
  const RideHistoryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final history = ref.watch(rideHistoryProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Historique')),
      body: history.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (rides) {
          if (rides.isEmpty) {
            return const Center(child: Text('Aucune course pour le moment'));
          }

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(rideHistoryProvider),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: rides.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, index) {
                final ride = rides[index];
                final date = ride.createdAt != null
                    ? DateFormat('dd/MM/yyyy HH:mm')
                        .format(DateTime.parse(ride.createdAt!))
                    : '—';

                return Card(
                  child: ListTile(
                    leading: const Icon(Icons.local_taxi),
                    title: Text('Course #${ride.id}'),
                    subtitle: Text(
                      '$date · ${ride.status}'
                      '${ride.driver != null ? ' · ${ride.driver!.name}' : ''}',
                    ),
                    trailing: ride.estimatedPrice != null
                        ? Text(
                            '${ride.estimatedPrice!.toStringAsFixed(0)} FCFA',
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          )
                        : null,
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
