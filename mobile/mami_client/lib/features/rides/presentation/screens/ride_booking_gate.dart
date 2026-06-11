import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/config/app_features_provider.dart';
import 'ride_booking_screen.dart';
import 'ride_booking_text_screen.dart';

/// Choisit l'écran de réservation V1 ou V2 selon le feature flag.
class RideBookingGate extends ConsumerWidget {
  const RideBookingGate({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final features = ref.watch(appFeaturesProvider);

    return features.when(
      loading: () => const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      ),
      error: (_, __) => const RideBookingTextScreen(),
      data: (f) => f.useV2Booking
          ? const RideBookingTextScreen()
          : const RideBookingScreen(),
    );
  }
}
