import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/map/mami_map.dart';
import '../../../../core/map/route_utils.dart';
import '../../../../core/utils/price_utils.dart';
import '../../../../core/widgets/primary_button.dart';
import '../../../location/presentation/providers/user_location_provider.dart';
import '../../data/rides_repository.dart';
import '../providers/active_ride_provider.dart';
import '../providers/booking_provider.dart';

enum _MapTapTarget { pickup, destination }

class RideBookingScreen extends ConsumerStatefulWidget {
  const RideBookingScreen({super.key});

  @override
  ConsumerState<RideBookingScreen> createState() => _RideBookingScreenState();
}

class _RideBookingScreenState extends ConsumerState<RideBookingScreen> {
  LatLng? _pickup;
  LatLng? _destination;
  _MapTapTarget _tapTarget = _MapTapTarget.pickup;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _initPickup());
  }

  Future<void> _initPickup() async {
    final pos = await ref.read(userLocationProvider.future);
    if (pos != null && mounted) {
      setState(() => _pickup = pos);
      ref.read(bookingDraftProvider.notifier).setPickup(pos);
    }
  }

  void _onMapTap(LatLng point) {
    setState(() {
      if (_tapTarget == _MapTapTarget.pickup) {
        _pickup = point;
        ref.read(bookingDraftProvider.notifier).setPickup(point);
        _tapTarget = _MapTapTarget.destination;
      } else {
        _destination = point;
        ref.read(bookingDraftProvider.notifier).setDestination(point);
      }
    });
  }

  double? _estimatedPrice() {
    if (_pickup == null || _destination == null) return null;
    return PriceUtils.estimateTripPrice(
      _pickup!.latitude,
      _pickup!.longitude,
      _destination!.latitude,
      _destination!.longitude,
    );
  }

  Future<void> _commander() async {
    if (_pickup == null || _destination == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Sélectionnez départ et destination sur la carte')),
      );
      return;
    }

    setState(() => _loading = true);
    try {
      final ride = await ref.read(ridesRepositoryProvider).requestRide(
            pickupLatitude: _pickup!.latitude,
            pickupLongitude: _pickup!.longitude,
            destinationLatitude: _destination!.latitude,
            destinationLongitude: _destination!.longitude,
          );

      ref.read(activeRideProvider.notifier).setRide(ride);
      ref.read(bookingDraftProvider.notifier).reset();

      if (mounted) context.go('/ride/searching/${ride.id}');
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())),
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final price = _estimatedPrice();
    final priceLabel = price != null
        ? NumberFormat.currency(symbol: 'FCFA ', decimalDigits: 0).format(price)
        : '—';

    final route = (_pickup != null && _destination != null)
        ? RouteUtils.straightLine(_pickup!, _destination!)
        : null;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Réserver'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.pop(),
        ),
      ),
      body: Column(
        children: [
          Expanded(
            child: MamiMap(
              fullScreen: true,
              pickup: _pickup,
              destination: _destination,
              route: route,
              onTap: _onMapTap,
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
                SegmentedButton<_MapTapTarget>(
                  segments: const [
                    ButtonSegment(
                      value: _MapTapTarget.pickup,
                      label: Text('Départ'),
                      icon: Icon(Icons.trip_origin, size: 18),
                    ),
                    ButtonSegment(
                      value: _MapTapTarget.destination,
                      label: Text('Destination'),
                      icon: Icon(Icons.flag, size: 18),
                    ),
                  ],
                  selected: {_tapTarget},
                  onSelectionChanged: (s) =>
                      setState(() => _tapTarget = s.first),
                ),
                const SizedBox(height: 8),
                Text(
                  'Tapez sur la carte pour placer le point sélectionné',
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                ),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Prix estimé',
                        style: TextStyle(fontWeight: FontWeight.w600)),
                    Text(priceLabel,
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        )),
                  ],
                ),
                const SizedBox(height: 12),
                PrimaryButton(
                  label: 'Commander',
                  loading: _loading,
                  onPressed: _commander,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
