import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/utils/price_utils.dart';
import '../../../../core/widgets/primary_button.dart';
import '../../../../core/widgets/ride_map.dart';
import '../../../location/presentation/providers/user_location_provider.dart';
import '../../data/rides_repository.dart';
import '../providers/active_ride_provider.dart';
import '../providers/booking_provider.dart';

class RideBookingScreen extends ConsumerStatefulWidget {
  const RideBookingScreen({super.key});

  @override
  ConsumerState<RideBookingScreen> createState() => _RideBookingScreenState();
}

class _RideBookingScreenState extends ConsumerState<RideBookingScreen> {
  final _pickupLat = TextEditingController(text: '0.4162');
  final _pickupLng = TextEditingController(text: '9.4673');
  final _destLat = TextEditingController(text: '0.4200');
  final _destLng = TextEditingController(text: '9.4800');
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _useGpsPickup());
  }

  Future<void> _useGpsPickup() async {
    final pos = await ref.read(userLocationProvider.future);
    if (pos != null && mounted) {
      _pickupLat.text = pos.latitude.toStringAsFixed(6);
      _pickupLng.text = pos.longitude.toStringAsFixed(6);
      ref.read(bookingDraftProvider.notifier).setPickup(pos);
      setState(() {});
    }
  }

  @override
  void dispose() {
    _pickupLat.dispose();
    _pickupLng.dispose();
    _destLat.dispose();
    _destLng.dispose();
    super.dispose();
  }

  double? _estimatedPrice() {
    try {
      return PriceUtils.estimateTripPrice(
        double.parse(_pickupLat.text),
        double.parse(_pickupLng.text),
        double.parse(_destLat.text),
        double.parse(_destLng.text),
      );
    } catch (_) {
      return null;
    }
  }

  Future<void> _commander() async {
    setState(() => _loading = true);
    try {
      final ride = await ref.read(ridesRepositoryProvider).requestRide(
            pickupLatitude: double.parse(_pickupLat.text),
            pickupLongitude: double.parse(_pickupLng.text),
            destinationLatitude: double.parse(_destLat.text),
            destinationLongitude: double.parse(_destLng.text),
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

    LatLng center;
    try {
      center = LatLng(
        double.parse(_pickupLat.text),
        double.parse(_pickupLng.text),
      );
    } catch (_) {
      center = const LatLng(0.4162, 9.4673);
    }

    LatLng? destination;
    try {
      destination = LatLng(
        double.parse(_destLat.text),
        double.parse(_destLng.text),
      );
    } catch (_) {}

    return Scaffold(
      appBar: AppBar(
        title: const Text('Réserver une course'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.pop(),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          RideMap(
            center: center,
            pickup: center,
            destination: destination,
            height: 200,
          ),
          const SizedBox(height: 16),
          const Text('Point de départ', style: TextStyle(fontWeight: FontWeight.bold)),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _pickupLat,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Lat'),
                  onChanged: (_) => setState(() {}),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: TextField(
                  controller: _pickupLng,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Lng'),
                  onChanged: (_) => setState(() {}),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          const Text('Destination', style: TextStyle(fontWeight: FontWeight.bold)),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _destLat,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Lat'),
                  onChanged: (_) => setState(() {}),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: TextField(
                  controller: _destLng,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Lng'),
                  onChanged: (_) => setState(() {}),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Card(
            child: ListTile(
              leading: const Icon(Icons.payments_outlined),
              title: const Text('Prix estimé'),
              trailing: Text(
                priceLabel,
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
            ),
          ),
          const SizedBox(height: 24),
          PrimaryButton(
            label: 'Commander',
            loading: _loading,
            onPressed: _commander,
          ),
        ],
      ),
    );
  }
}
