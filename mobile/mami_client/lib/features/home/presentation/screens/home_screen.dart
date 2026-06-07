import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/primary_button.dart';
import '../../../../core/widgets/ride_map.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../../location/presentation/providers/user_location_provider.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authStateProvider).valueOrNull;
    final locationAsync = ref.watch(userLocationProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('MAMI.GA'),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 16),
            child: Center(
              child: Text(
                user?.name.split(' ').first ?? '',
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
          ),
        ],
      ),
      body: locationAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (_, __) => const Center(child: Text('GPS indisponible')),
        data: (position) {
          final center = position ?? const LatLng(0.4162, 9.4673);

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              RideMap(center: center, height: 280),
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Bonjour, ${user?.name ?? 'Client'}',
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Position : ${center.latitude.toStringAsFixed(4)}, '
                        '${center.longitude.toStringAsFixed(4)}',
                        style: TextStyle(color: Colors.grey.shade600),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              PrimaryButton(
                label: 'Commander une course',
                color: AppTheme.primary,
                onPressed: () => context.push('/book'),
              ),
            ],
          );
        },
      ),
    );
  }
}
