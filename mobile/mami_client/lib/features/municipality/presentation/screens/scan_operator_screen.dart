import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../data/fiscal_collection_repository.dart';
import '../../domain/operator_qr_lookup.dart';

class ScanOperatorScreen extends ConsumerStatefulWidget {
  const ScanOperatorScreen({super.key});

  @override
  ConsumerState<ScanOperatorScreen> createState() => _ScanOperatorScreenState();
}

class _ScanOperatorScreenState extends ConsumerState<ScanOperatorScreen> {
  final _qrController = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _qrController.dispose();
    super.dispose();
  }

  Future<void> _lookup() async {
    final value = _qrController.text.trim();
    if (value.isEmpty) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final repo = ref.read(fiscalCollectionRepositoryProvider);
      final operatorId = await lookupOperatorIdByQr(
        lookup: repo.lookupOperatorByQr,
        rawPayload: value,
      );

      if (!mounted) return;
      context.push('/municipality/recovery/fiscal-summary/$operatorId');
    } on OperatorQrLookupException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Connexion réseau indisponible');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Scanner QR commerce')),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            FilledButton.icon(
              onPressed: _loading
                  ? null
                  : () => context.push('/municipality/recovery/scan/camera'),
              icon: const Text('📷', style: TextStyle(fontSize: 18)),
              label: const Text('Scanner avec la caméra'),
            ),
            const SizedBox(height: 20),
            const Row(
              children: [
                Expanded(child: Divider()),
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 12),
                  child: Text('OU'),
                ),
                Expanded(child: Divider()),
              ],
            ),
            const SizedBox(height: 20),
            TextField(
              controller: _qrController,
              decoration: const InputDecoration(
                labelText: 'Jeton QR / UUID',
                border: OutlineInputBorder(),
              ),
            ),
            if (_error != null) ...[
              const SizedBox(height: 12),
              Text(_error!, style: const TextStyle(color: Colors.red)),
            ],
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _loading ? null : _lookup,
              child: _loading
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Identifier le commerce'),
            ),
          ],
        ),
      ),
    );
  }
}
