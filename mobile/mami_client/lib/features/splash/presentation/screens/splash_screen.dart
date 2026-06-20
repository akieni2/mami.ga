import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../../rides/presentation/providers/active_ride_provider.dart';
import '../../../rides/data/rides_repository.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    await Future.wait([
      ref.read(authStateProvider.notifier).bootstrap(),
      Future.delayed(AppConfig.splashMinDuration),
    ]);

    if (!mounted) return;

    final user = ref.read(authStateProvider).valueOrNull;
    if (user == null) {
      context.go('/login');
      return;
    }

    try {
      final ride = await ref.read(ridesRepositoryProvider).fetchCurrentClientRide();
      if (!mounted) return;
      if (ride != null) {
        ref.read(activeRideProvider.notifier).setRide(ride);
        if (ride.isSearching) {
          context.go('/ride/searching/${ride.id}');
          return;
        }
        if (ride.isAccepted) {
          context.go('/ride/active/${ride.id}');
          return;
        }
      }
    } catch (_) {}

    if (mounted) context.go(user.postAuthRoute);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.local_taxi, size: 88, color: AppTheme.primary),
            const SizedBox(height: 16),
            Text(
              'MAMI.GA',
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 8),
            const Text('Votre taxi, en un clic'),
            const SizedBox(height: 32),
            const CircularProgressIndicator(),
          ],
        ),
      ),
    );
  }
}
