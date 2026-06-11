import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/map/mami_map.dart';
import '../../../../core/map/route_utils.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/primary_button.dart';
import '../../../location/presentation/providers/user_location_provider.dart';
import '../providers/booking_provider.dart';
import '../providers/trip_estimate_provider.dart';
import '../widgets/trip_estimate_card.dart';

/// Réservation GPS V2 (P1) — pickup auto, destination carte, estimation sans dispatch.
class RideBookingV2Screen extends ConsumerStatefulWidget {
  const RideBookingV2Screen({super.key});

  @override
  ConsumerState<RideBookingV2Screen> createState() => _RideBookingV2ScreenState();
}

class _RideBookingV2ScreenState extends ConsumerState<RideBookingV2Screen> {
  LatLng? _pickup;
  LatLng? _destination;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _initPickupFromGps());
  }

  /// Position actuelle = point de départ (non modifiable manuellement en P1).
  Future<void> _initPickupFromGps() async {
    final location = await ref.read(userLocationProvider.future);
    if (mounted) {
      setState(() => _pickup = location.position);
      ref.read(bookingDraftProvider.notifier).setPickup(location.position);
    }
  }

  void _onMapTap(LatLng point) {
    debugPrint(
      'P1 destination selected: ${point.latitude.toStringAsFixed(4)}, '
      '${point.longitude.toStringAsFixed(4)}',
    );
    setState(() => _destination = point);
    ref.read(bookingDraftProvider.notifier).setDestination(point);
  }

  void _onConfirmPreview() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text(
          'Recherche chauffeur — disponible à la phase P3 (dispatch V2).',
        ),
        duration: Duration(seconds: 3),
      ),
    );
  }

  String _pickupLabel(bool isGpsAvailable) {
    if (!isGpsAvailable) {
      return 'Position GPS indisponible — carte centrée sur Libreville';
    }
    return 'Départ : votre position GPS';
  }

  @override
  Widget build(BuildContext context) {
    final locationAsync = ref.watch(userLocationProvider);
    final userPosition = locationAsync.valueOrNull?.position;
    final isGpsAvailable = locationAsync.valueOrNull?.isGpsAvailable ?? true;
    final pickup = _pickup ?? userPosition;

    final route = (pickup != null && _destination != null)
        ? RouteUtils.straightLine(pickup, _destination!)
        : null;

    final estimateRequest = (pickup != null && _destination != null)
        ? TripEstimateRequest(pickup: pickup, destination: _destination!)
        : null;

    final estimateAsync = estimateRequest != null
        ? ref.watch(tripEstimateProvider(estimateRequest))
        : null;

    final canPreview = pickup != null && _destination != null;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Réserver un trajet'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.pop(),
        ),
      ),
      body: Column(
        children: [
          Expanded(
            child: Stack(
              children: [
                MamiMap(
                  fullScreen: true,
                  user: userPosition,
                  pickup: pickup,
                  destination: _destination,
                  route: route,
                  onTap: _onMapTap,
                ),
                Positioned(
                  top: 12,
                  left: 12,
                  right: 12,
                  child: Material(
                    elevation: 2,
                    borderRadius: BorderRadius.circular(12),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 10,
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.touch_app, size: 18),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              pickup == null
                                  ? 'Acquisition GPS en cours…'
                                  : 'Touchez la carte pour choisir votre destination',
                              style: const TextStyle(fontSize: 13),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.08),
                  blurRadius: 8,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (pickup == null)
                  const LinearProgressIndicator(minHeight: 2)
                else
                  Row(
                    children: [
                      Icon(
                        isGpsAvailable ? Icons.my_location : Icons.location_off,
                        size: 18,
                        color: isGpsAvailable ? Colors.blue.shade700 : Colors.orange.shade800,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          _pickupLabel(isGpsAvailable),
                          style: TextStyle(
                            fontSize: 13,
                            color: Colors.grey.shade700,
                          ),
                        ),
                      ),
                    ],
                  ),
                if (_destination != null) ...[
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Icon(Icons.flag, size: 18, color: Colors.red),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Destination : ${_destination!.latitude.toStringAsFixed(4)}, '
                          '${_destination!.longitude.toStringAsFixed(4)}',
                          style: TextStyle(
                            fontSize: 13,
                            color: Colors.grey.shade700,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
                const SizedBox(height: 12),
                if (estimateAsync != null)
                  estimateAsync.when(
                    loading: () => const Padding(
                      padding: EdgeInsets.symmetric(vertical: 16),
                      child: Center(child: CircularProgressIndicator()),
                    ),
                    error: (e, _) => Text(
                      'Estimation indisponible : $e',
                      style: TextStyle(color: Theme.of(context).colorScheme.error),
                    ),
                    data: (estimate) => TripEstimateCard(estimate: estimate),
                  )
                else if (canPreview)
                  const SizedBox.shrink()
                else
                  Card(
                    margin: EdgeInsets.zero,
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Text(
                        'Sélectionnez une destination pour voir l\'estimation.',
                        style: TextStyle(color: Colors.grey.shade600),
                      ),
                    ),
                  ),
                const SizedBox(height: 12),
                PrimaryButton(
                  label: 'Continuer',
                  onPressed: canPreview && estimateAsync?.hasValue == true
                      ? _onConfirmPreview
                      : null,
                ),
                const SizedBox(height: 4),
                Text(
                  'MAMI Taxi V2 — Phase P1 (sans dispatch)',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 11,
                    color: AppTheme.primary.withValues(alpha: 0.9),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
