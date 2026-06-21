import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_error_message.dart';
import '../../data/fiscal_collection_repository.dart';
import '../../domain/municipal_gps_service.dart';
import '../providers/fiscal_collection_providers.dart';
import '../providers/financial_governance_providers.dart';
import '../providers/municipal_gps_provider.dart';

class OpenCashSessionScreen extends ConsumerStatefulWidget {
  const OpenCashSessionScreen({super.key});

  @override
  ConsumerState<OpenCashSessionScreen> createState() => _OpenCashSessionScreenState();
}

class _OpenCashSessionScreenState extends ConsumerState<OpenCashSessionScreen> {
  final _amountController = TextEditingController(text: '0');
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  Future<void> _open() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final gps = ref.read(municipalGpsServiceProvider);
      final position = await gps.capturePosition();
      final repo = ref.read(fiscalCollectionRepositoryProvider);
      await repo.openSession(
        openingAmountXaf: double.tryParse(_amountController.text) ?? 0,
        latitude: position.latitude,
        longitude: position.longitude,
        gpsAccuracyM: position.accuracy,
      );
      ref.invalidate(currentCashSessionProvider);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Session de caisse ouverte')),
        );
        Navigator.of(context).pop();
      }
    } on MunicipalGpsException catch (e) {
      setState(() => _error = e.message);
    } catch (e) {
      setState(() => _error = resolveApiErrorMessage(e));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final missionAsync = ref.watch(currentFinancialMissionProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Ouvrir caisse')),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            missionAsync.when(
              data: (mission) => mission == null
                  ? const Text(
                      'Aucune mission financière active (ouverture libre si configuré).',
                      style: TextStyle(color: Colors.grey),
                    )
                  : Card(
                      child: ListTile(
                        title: Text('Mission : ${mission.title}'),
                        subtitle: Text(
                          '${mission.reference} · ${mission.validFrom} → ${mission.validUntil}',
                        ),
                      ),
                    ),
              loading: () => const LinearProgressIndicator(),
              error: (_, __) => const SizedBox.shrink(),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _amountController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Fonds de caisse (XAF)',
                border: OutlineInputBorder(),
              ),
            ),
            if (_error != null) ...[
              const SizedBox(height: 12),
              Text(_error!, style: const TextStyle(color: Colors.red)),
            ],
            const Spacer(),
            FilledButton(
              onPressed: _loading ? null : _open,
              child: _loading
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Ouvrir la session'),
            ),
          ],
        ),
      ),
    );
  }
}
