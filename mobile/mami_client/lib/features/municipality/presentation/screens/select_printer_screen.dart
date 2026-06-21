import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../printing/bluetooth_printer_adapter.dart';
import '../../printing/printer_exception.dart';
import '../../printing/selected_printer.dart';
import '../providers/fiscal_collection_providers.dart';

class SelectPrinterScreen extends ConsumerStatefulWidget {
  const SelectPrinterScreen({super.key});

  @override
  ConsumerState<SelectPrinterScreen> createState() => _SelectPrinterScreenState();
}

class _SelectPrinterScreenState extends ConsumerState<SelectPrinterScreen> {
  List<BluetoothPrinterDevice> _printers = [];
  SelectedPrinter? _current;
  String? _selectedMac;
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final service = ref.read(printerServiceProvider);
      final current = await service.selectedPrinter();
      final printers = await service.listPairedPrinters();

      if (!mounted) return;
      setState(() {
        _current = current;
        _printers = printers;
        _selectedMac = current?.macAddress ??
            (printers.isNotEmpty ? printers.first.macAddress : null);
      });
    } on PrinterException catch (e) {
      if (!mounted) return;
      setState(() => _error = e.message);
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _saveSelection() async {
    BluetoothPrinterDevice? device;
    for (final printer in _printers) {
      if (printer.macAddress == _selectedMac) {
        device = printer;
        break;
      }
    }

    if (device == null) {
      setState(() => _error = PrinterFailureReason.noPrinterConfigured.message);
      return;
    }

    final service = ref.read(printerServiceProvider);
    await service.selectPrinter(device);

    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Imprimante enregistrée : ${device.label}')),
    );
    context.pop();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Choisir l\'imprimante')),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (_current != null)
              Card(
                child: ListTile(
                  leading: const Icon(Icons.print_outlined),
                  title: const Text('Imprimante par défaut'),
                  subtitle: Text(_current!.displayLabel),
                ),
              ),
            const SizedBox(height: 12),
            Text(
              'Appareils Bluetooth appairés',
              style: Theme.of(context).textTheme.titleSmall,
            ),
            const SizedBox(height: 8),
            if (_loading)
              const Expanded(child: Center(child: CircularProgressIndicator()))
            else if (_error != null)
              Expanded(
                child: Column(
                  children: [
                    Text(_error!, style: const TextStyle(color: Colors.red)),
                    const SizedBox(height: 12),
                    OutlinedButton.icon(
                      onPressed: _load,
                      icon: const Icon(Icons.refresh),
                      label: const Text('Réessayer'),
                    ),
                  ],
                ),
              )
            else if (_printers.isEmpty)
              const Expanded(
                child: Text(
                  'Aucune imprimante appairée. Appairez l\'imprimante dans les paramètres Bluetooth Android, puis actualisez.',
                ),
              )
            else
              Expanded(
                child: ListView.builder(
                  itemCount: _printers.length,
                  itemBuilder: (context, index) {
                    final printer = _printers[index];
                    return RadioListTile<String>(
                      value: printer.macAddress,
                      groupValue: _selectedMac,
                      onChanged: (value) => setState(() => _selectedMac = value),
                      title: Text(printer.name.isNotEmpty ? printer.name : 'Imprimante'),
                      subtitle: Text(printer.macAddress),
                      secondary: const Icon(Icons.bluetooth),
                    );
                  },
                ),
              ),
            OutlinedButton.icon(
              onPressed: _loading ? null : _load,
              icon: const Icon(Icons.bluetooth_searching),
              label: const Text('Actualiser la liste'),
            ),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: _loading || _selectedMac == null ? null : _saveSelection,
              child: const Text('Enregistrer cette imprimante'),
            ),
          ],
        ),
      ),
    );
  }
}
