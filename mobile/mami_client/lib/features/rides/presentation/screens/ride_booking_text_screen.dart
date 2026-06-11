import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/primary_button.dart';
import '../../data/rides_repository.dart';
import '../../domain/models/payment_method.dart';
import '../providers/active_ride_provider.dart';
import '../widgets/payment_method_selector.dart';
import '../widgets/price_input_field.dart';

/// P2A — réservation text-first (sans GPS obligatoire).
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

  @override
  void dispose() {
    _pickup.dispose();
    _destination.dispose();
    _price.dispose();
    super.dispose();
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
              'Quartiers, points de repère — pas besoin de GPS pour commander.',
              style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
            ),
            const SizedBox(height: 20),
            TextFormField(
              controller: _pickup,
              textCapitalization: TextCapitalization.sentences,
              decoration: const InputDecoration(
                labelText: 'Départ',
                hintText: 'Ex. Lalala, rond-point Total',
                prefixIcon: Icon(Icons.trip_origin, color: Colors.green),
                border: OutlineInputBorder(),
              ),
              validator: (v) =>
                  v == null || v.trim().length < 3 ? 'Départ requis (3 car. min.)' : null,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _destination,
              textCapitalization: TextCapitalization.sentences,
              decoration: const InputDecoration(
                labelText: 'Destination',
                hintText: 'Ex. Nzeng-Ayong, marché',
                prefixIcon: Icon(Icons.flag, color: Colors.red),
                border: OutlineInputBorder(),
              ),
              validator: (v) => v == null || v.trim().length < 3
                  ? 'Destination requise (3 car. min.)'
                  : null,
            ),
            const SizedBox(height: 20),
            PriceInputField(controller: _price),
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
              'MAMI Taxi V2 — P2A (recherche chauffeur : dispatch P3)',
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
