import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/municipal_receipt_model.dart';
import '../../printing/bluetooth_printer_adapter.dart';
import '../providers/fiscal_collection_providers.dart';

class PrintReceiptScreen extends ConsumerStatefulWidget {
  const PrintReceiptScreen({
    super.key,
    required this.receiptId,
    this.initialReceipt,
  });

  final int receiptId;
  final MunicipalReceiptModel? initialReceipt;

  @override
  ConsumerState<PrintReceiptScreen> createState() => _PrintReceiptScreenState();
}

class _PrintReceiptScreenState extends ConsumerState<PrintReceiptScreen> {
  List<BluetoothPrinterDevice> _printers = [];
  String? _selectedMac;
  bool _loadingPrinters = false;
  bool _printing = false;
  String? _message;

  @override
  void initState() {
    super.initState();
    _loadPrinters();
  }

  Future<void> _loadPrinters() async {
    setState(() => _loadingPrinters = true);
    try {
      final service = ref.read(printerServiceProvider);
      final mac = await service.selectedPrinterMac();
      final printers = await service.listPairedPrinters();
      if (!mounted) return;
      setState(() {
        _printers = printers;
        _selectedMac = mac ?? (printers.isNotEmpty ? printers.first.macAddress : null);
      });
    } finally {
      if (mounted) setState(() => _loadingPrinters = false);
    }
  }

  Future<void> _print(MunicipalReceiptModel receipt, {bool reprint = false}) async {
    setState(() {
      _printing = true;
      _message = null;
    });

    try {
      final repo = ref.read(fiscalCollectionRepositoryProvider);
      final service = ref.read(printerServiceProvider);

      if (_selectedMac != null) {
        await service.selectPrinter(_selectedMac!);
      }

      var target = receipt;
      if (reprint) {
        target = await repo.reprintReceipt(receipt.id);
        ref.invalidate(myReceiptsProvider);
      }

      final ok = await service.printReceipt(target.printPayload);
      if (!mounted) return;
      setState(() {
        _message = ok
            ? 'Quittance imprimée sur imprimante 58 mm'
            : 'Échec impression — vérifiez Bluetooth et imprimante';
      });
    } catch (_) {
      if (mounted) {
        setState(() => _message = 'Erreur lors de l\'impression');
      }
    } finally {
      if (mounted) setState(() => _printing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final receiptAsync = widget.initialReceipt != null
        ? AsyncValue.data(widget.initialReceipt!)
        : ref.watch(receiptDetailProvider(widget.receiptId));

    return Scaffold(
      appBar: AppBar(title: const Text('Impression quittance')),
      body: receiptAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (receipt) => Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(receipt.receiptNumber,
                          style: Theme.of(context).textTheme.titleMedium),
                      const SizedBox(height: 8),
                      Text(receipt.printPayload.commercialName),
                      Text('${receipt.printPayload.amountXaf} XAF'),
                      Text('Statut : ${receipt.statusLabel}'),
                      Text('Réimpressions : ${receipt.reprintCount}'),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text('Imprimante Bluetooth (58 mm)',
                  style: Theme.of(context).textTheme.titleSmall),
              const SizedBox(height: 8),
              if (_loadingPrinters)
                const LinearProgressIndicator()
              else if (_printers.isEmpty)
                const Text('Aucune imprimante appairée détectée')
              else
                DropdownButtonFormField<String>(
                  value: _selectedMac,
                  decoration: const InputDecoration(border: OutlineInputBorder()),
                  items: _printers
                      .map((p) => DropdownMenuItem(
                            value: p.macAddress,
                            child: Text(p.label),
                          ))
                      .toList(),
                  onChanged: (value) => setState(() => _selectedMac = value),
                ),
              const SizedBox(height: 12),
              OutlinedButton.icon(
                onPressed: _loadingPrinters ? null : _loadPrinters,
                icon: const Icon(Icons.bluetooth_searching),
                label: const Text('Actualiser les imprimantes'),
              ),
              if (_message != null) ...[
                const SizedBox(height: 12),
                Text(
                  _message!,
                  style: TextStyle(
                    color: _message!.contains('imprimée') ? Colors.green : Colors.red,
                  ),
                ),
              ],
              const Spacer(),
              FilledButton(
                onPressed: _printing ? null : () => _print(receipt),
                child: _printing
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Imprimer'),
              ),
              const SizedBox(height: 8),
              OutlinedButton(
                onPressed: _printing ? null : () => _print(receipt, reprint: true),
                child: const Text('Réimprimer (audit)'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
