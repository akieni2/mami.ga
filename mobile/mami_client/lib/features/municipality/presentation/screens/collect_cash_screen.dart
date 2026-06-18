import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';

import '../../data/fiscal_collection_repository.dart';
import '../providers/fiscal_collection_providers.dart';

class CollectCashScreen extends ConsumerStatefulWidget {
  const CollectCashScreen({super.key, this.operatorId, this.suggestedAmount});

  final int? operatorId;
  final String? suggestedAmount;

  @override
  ConsumerState<CollectCashScreen> createState() => _CollectCashScreenState();
}

class _CollectCashScreenState extends ConsumerState<CollectCashScreen> {
  final _operatorController = TextEditingController();
  final _amountController = TextEditingController();
  bool _loading = false;
  String? _error;
  String? _success;

  @override
  void initState() {
    super.initState();
    if (widget.operatorId != null) {
      _operatorController.text = widget.operatorId.toString();
    }
    if (widget.suggestedAmount != null) {
      _amountController.text = widget.suggestedAmount!;
    }
  }

  @override
  void dispose() {
    _operatorController.dispose();
    _amountController.dispose();
    super.dispose();
  }

  Future<void> _collect() async {
    final operatorId = int.tryParse(_operatorController.text.trim());
    final amount = double.tryParse(_amountController.text.trim());
    if (operatorId == null || amount == null || amount <= 0) {
      setState(() => _error = 'Opérateur et montant valides requis');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
      _success = null;
    });

    try {
      final session = await ref.read(fiscalCollectionRepositoryProvider).fetchCurrentSession();
      if (session == null || !session.isOpen) {
        setState(() => _error = 'Ouvrez une session de caisse avant d\'encaisser');
        return;
      }

      final position = await Geolocator.getCurrentPosition();
      final repo = ref.read(fiscalCollectionRepositoryProvider);
      final payment = await repo.collectCash(
        operatorId: operatorId,
        amountXaf: amount,
        cashSessionId: session.id,
        latitude: position.latitude,
        longitude: position.longitude,
        gpsAccuracyM: position.accuracy,
      );

      ref.invalidate(currentCashSessionProvider);
      ref.invalidate(myCollectionsProvider);
      setState(() => _success = 'Encaissement ${payment.amountXaf} XAF enregistré');
    } catch (e) {
      setState(() => _error = 'Encaissement refusé');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final sessionAsync = ref.watch(currentCashSessionProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Encaisser')),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            sessionAsync.when(
              data: (s) => Text(
                s?.isOpen == true
                    ? 'Session : ${s!.reference}'
                    : 'Aucune session ouverte',
              ),
              loading: () => const LinearProgressIndicator(),
              error: (_, __) => const SizedBox.shrink(),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _operatorController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'ID opérateur',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _amountController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Montant (XAF)',
                border: OutlineInputBorder(),
              ),
            ),
            if (_error != null) ...[
              const SizedBox(height: 12),
              Text(_error!, style: const TextStyle(color: Colors.red)),
            ],
            if (_success != null) ...[
              const SizedBox(height: 12),
              Text(_success!, style: const TextStyle(color: Colors.green)),
            ],
            const Spacer(),
            FilledButton(
              onPressed: _loading ? null : _collect,
              child: _loading
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Valider l\'encaissement espèces'),
            ),
          ],
        ),
      ),
    );
  }
}
