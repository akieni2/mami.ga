import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/fiscal_collection_repository.dart';
import '../../domain/municipal_gps_service.dart';
import '../providers/fiscal_collection_providers.dart';
import '../providers/municipal_gps_provider.dart';

class CloseCashSessionScreen extends ConsumerStatefulWidget {
  const CloseCashSessionScreen({super.key});

  @override
  ConsumerState<CloseCashSessionScreen> createState() => _CloseCashSessionScreenState();
}

class _CloseCashSessionScreenState extends ConsumerState<CloseCashSessionScreen> {
  final _amountController = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  Future<void> _close(CashSessionModel session) async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final gps = ref.read(municipalGpsServiceProvider);
      final position = await gps.capturePosition();
      final repo = ref.read(fiscalCollectionRepositoryProvider);
      await repo.closeSession(
        sessionId: session.id,
        actualAmountXaf: double.tryParse(_amountController.text) ??
            double.tryParse(session.expectedAmountXaf) ??
            0,
        latitude: position.latitude,
        longitude: position.longitude,
      );
      ref.invalidate(currentCashSessionProvider);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Session fermée')),
        );
        Navigator.of(context).pop();
      }
    } on MunicipalGpsException catch (e) {
      setState(() => _error = e.message);
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final sessionAsync = ref.watch(currentCashSessionProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Fermer caisse')),
      body: sessionAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (session) {
          if (session == null || !session.isOpen) {
            return const Center(child: Text('Aucune session ouverte'));
          }

          _amountController.text = session.expectedAmountXaf;

          return Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text('Référence : ${session.reference}'),
                Text('Montant attendu : ${session.expectedAmountXaf} XAF'),
                const SizedBox(height: 16),
                TextField(
                  controller: _amountController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Montant réel en caisse (XAF)',
                    border: OutlineInputBorder(),
                  ),
                ),
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  Text(_error!, style: const TextStyle(color: Colors.red)),
                ],
                const Spacer(),
                FilledButton(
                  onPressed: _loading ? null : () => _close(session),
                  child: _loading
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Fermer la session'),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
