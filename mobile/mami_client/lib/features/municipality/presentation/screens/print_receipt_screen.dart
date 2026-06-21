import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../data/models/municipal_receipt_model.dart';
import '../../printing/printer_exception.dart';
import '../../printing/selected_printer.dart';
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
  SelectedPrinter? _selectedPrinter;
  bool _loadingPrinter = false;
  bool _printing = false;
  String? _message;
  bool _success = false;

  @override
  void initState() {
    super.initState();
    _loadSelectedPrinter();
  }

  Future<void> _loadSelectedPrinter() async {
    setState(() => _loadingPrinter = true);
    try {
      final printer = await ref.read(printerServiceProvider).selectedPrinter();
      if (!mounted) return;
      setState(() => _selectedPrinter = printer);
    } finally {
      if (mounted) setState(() => _loadingPrinter = false);
    }
  }

  Future<void> _print(MunicipalReceiptModel receipt, {bool reprint = false}) async {
    setState(() {
      _printing = true;
      _message = null;
      _success = false;
    });

    try {
      final repo = ref.read(fiscalCollectionRepositoryProvider);
      final service = ref.read(printerServiceProvider);

      var target = receipt;
      if (reprint) {
        target = await repo.reprintReceipt(receipt.id);
        ref.invalidate(myReceiptsProvider);
      }

      await service.printReceipt(target.printPayload);
      if (!mounted) return;
      setState(() {
        _success = true;
        _message = 'Quittance imprimée sur imprimante 58 mm';
      });
    } on PrinterException catch (e) {
      if (!mounted) return;
      setState(() {
        _success = false;
        _message = e.message;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _success = false;
        _message = e.toString();
      });
    } finally {
      if (mounted) setState(() => _printing = false);
    }
  }

  Future<void> _changePrinter() async {
    await context.push('/municipality/recovery/printer');
    await _loadSelectedPrinter();
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
                      Text(
                        receipt.receiptNumber,
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
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
              Text('Imprimante par défaut', style: Theme.of(context).textTheme.titleSmall),
              const SizedBox(height: 8),
              if (_loadingPrinter)
                const LinearProgressIndicator()
              else if (_selectedPrinter == null)
                const Text(
                  'Aucune imprimante sélectionnée. Choisissez une imprimante Bluetooth appairée.',
                )
              else
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.print_outlined),
                  title: Text(
                    _selectedPrinter!.name.isNotEmpty
                        ? _selectedPrinter!.name
                        : 'Imprimante Bluetooth',
                  ),
                  subtitle: Text(_selectedPrinter!.macAddress),
                ),
              const SizedBox(height: 8),
              OutlinedButton.icon(
                onPressed: _changePrinter,
                icon: const Icon(Icons.bluetooth),
                label: const Text('Changer d\'imprimante'),
              ),
              if (_message != null) ...[
                const SizedBox(height: 12),
                Text(
                  _message!,
                  style: TextStyle(color: _success ? Colors.green : Colors.red),
                ),
              ],
              const Spacer(),
              FilledButton(
                onPressed: _printing || _selectedPrinter == null
                    ? null
                    : () => _print(receipt),
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
                onPressed: _printing || _selectedPrinter == null
                    ? null
                    : () => _print(receipt, reprint: true),
                child: const Text('Réimprimer (audit)'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
