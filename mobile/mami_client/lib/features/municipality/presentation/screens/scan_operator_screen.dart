import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../data/fiscal_collection_repository.dart';

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
      final data = await repo.lookupOperatorByQr(value);
      final operator = data['operator'] as Map<String, dynamic>? ?? data;
      final operatorId = operator['id'] as int?;

      if (!mounted || operatorId == null) return;
      context.push('/municipality/recovery/fiscal-summary/$operatorId');
    } catch (e) {
      setState(() => _error = 'QR introuvable ou invalide');
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
            const Text(
              'Saisissez le jeton QR (UUID) ou scannez avec l\'appareil photo.',
            ),
            const SizedBox(height: 16),
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
