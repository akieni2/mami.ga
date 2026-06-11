import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/map/lat_lng_utils.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/primary_button.dart';
import '../../data/rides_repository.dart';
import '../../domain/models/payment_method.dart';
import '../providers/active_ride_provider.dart';
import '../widgets/payment_method_selector.dart';
import '../widgets/price_input_field.dart';
import 'ride_map_picker_sheet.dart';

/// P2A/P2B — réservation text-first, carte optionnelle.
class RideBookingTextScreen extends ConsumerStatefulWidget {
  const RideBookingTextScreen({super.key});

  @override
  ConsumerState<RideBookingTextScreen> createState() =>
      _RideBookingTextScreenState();
}

class _RideBookingTextScreenState extends ConsumerState<RideBookingTextScreen> {
  final _formKey = GlobalKey<FormState>();
  final _pickup = TextEditingController();
  final _destination = TextEditingController();
  final _price = TextEditingController(text: '3000');

  RidePaymentMethod _payment = RidePaymentMethod.cash;
  bool _loading = false;

  LatLng? _pickupCoord;
  LatLng? _destinationCoord;
  bool _pickupFromMap = false;
  bool _destinationFromMap = false;
  double? _suggestedPrice;
  bool _estimateLoading = false;

  @override
  void dispose() {
    _pickup.dispose();
    _destination.dispose();
    _price.dispose();
    super.dispose();
  }

  bool get _hasBothCoords =>
      LatLngUtils.isValid(_pickupCoord) &&
      LatLngUtils.isValid(_destinationCoord);

  Future<void> _refreshEstimate() async {
    if (!_hasBothCoords) {
      setState(() => _suggestedPrice = null);
      return;
    }

    setState(() => _estimateLoading = true);
    try {
      final estimate = await ref.read(ridesRepositoryProvider).estimateTrip(
            pickupLatitude: _pickupCoord!.latitude,
            pickupLongitude: _pickupCoord!.longitude,
            destinationLatitude: _destinationCoord!.latitude,
            destinationLongitude: _destinationCoord!.longitude,
          );
      if (mounted) setState(() => _suggestedPrice = estimate.suggestedPrice);
    } catch (_) {
      if (mounted) setState(() => _suggestedPrice = null);
    } finally {
      if (mounted) setState(() => _estimateLoading = false);
    }
  }

  Future<void> _openMapPicker() async {
    final result = await RideMapPickerSheet.show(
      context,
      initialPickup: _pickupCoord,
      initialDestination: _destinationCoord,
    );

    if (result == null || !mounted) return;

    setState(() {
      if (result.pickup != null) {
        _pickupCoord = result.pickup;
        _pickupFromMap = true;
        if (_pickup.text.trim().isEmpty) {
          _pickup.text = LatLngUtils.format(result.pickup);
        }
      }
      if (result.destination != null) {
        _destinationCoord = result.destination;
        _destinationFromMap = true;
        if (_destination.text.trim().isEmpty) {
          _destination.text = LatLngUtils.format(result.destination);
        }
      }
    });

    await _refreshEstimate();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _loading = true);
    try {
      final ride = await ref.read(ridesRepositoryProvider).requestTextRide(
            pickupLabel: _pickup.text.trim(),
            destinationLabel: _destination.text.trim(),
            proposedPrice: double.parse(_price.text.trim()),
            paymentMethod: _payment,
            pickupLatitude: _pickupCoord?.latitude,
            pickupLongitude: _pickupCoord?.longitude,
            destinationLatitude: _destinationCoord?.latitude,
            destinationLongitude: _destinationCoord?.longitude,
          );

      ref.read(activeRideProvider.notifier).setRide(ride);

      if (mounted) {
        context.go('/ride/searching/${ride.id}');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Impossible de créer la demande : $e')),
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Widget _mapRefinedBadge(String label) {
    return Padding(
      padding: const EdgeInsets.only(top: 6),
      child: Row(
        children: [
          Icon(Icons.map_outlined, size: 14, color: Colors.blue.shade700),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(fontSize: 12, color: Colors.blue.shade700),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Réserver un trajet'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.pop(),
        ),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Text(
              'Décrivez votre trajet',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 4),
            Text(
              'Quartiers, points de repère — la carte est optionnelle.',
              style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
            ),
            const SizedBox(height: 20),
            TextFormField(
              controller: _pickup,
              textCapitalization: TextCapitalization.sentences,
              decoration: const InputDecoration(
                labelText: 'Départ',
                hintText: 'Ex. Carrefour STFO',
                prefixIcon: Icon(Icons.trip_origin, color: Colors.green),
                border: OutlineInputBorder(),
              ),
              validator: (v) =>
                  v == null || v.trim().length < 3 ? 'Départ requis (3 car. min.)' : null,
              onChanged: (_) {
                if (_pickupFromMap) {
                  setState(() => _pickupFromMap = false);
                }
              },
            ),
            if (_pickupFromMap && _pickupCoord != null)
              _mapRefinedBadge('Affiné sur la carte'),
            const SizedBox(height: 16),
            TextFormField(
              controller: _destination,
              textCapitalization: TextCapitalization.sentences,
              decoration: const InputDecoration(
                labelText: 'Destination',
                hintText: 'Ex. Sni owendo',
                prefixIcon: Icon(Icons.flag, color: Colors.red),
                border: OutlineInputBorder(),
              ),
              validator: (v) => v == null || v.trim().length < 3
                  ? 'Destination requise (3 car. min.)'
                  : null,
              onChanged: (_) {
                if (_destinationFromMap) {
                  setState(() => _destinationFromMap = false);
                }
              },
            ),
            if (_destinationFromMap && _destinationCoord != null)
              _mapRefinedBadge('Affiné sur la carte'),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: _openMapPicker,
              icon: const Icon(Icons.map_outlined),
              label: const Text('Choisir sur la carte'),
            ),
            const SizedBox(height: 20),
            PriceInputField(
              controller: _price,
              suggestedPrice: _suggestedPrice,
            ),
            if (_estimateLoading)
              const Padding(
                padding: EdgeInsets.only(top: 8),
                child: LinearProgressIndicator(minHeight: 2),
              ),
            const SizedBox(height: 20),
            PaymentMethodSelector(
              value: _payment,
              onChanged: (m) => setState(() => _payment = m),
            ),
            const SizedBox(height: 24),
            PrimaryButton(
              label: 'Rechercher un chauffeur',
              color: AppTheme.primary,
              loading: _loading,
              onPressed: _submit,
            ),
            const SizedBox(height: 12),
            Text(
              'MAMI Taxi V2 — P2B (carte optionnelle)',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 11,
                color: AppTheme.primary.withValues(alpha: 0.85),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
