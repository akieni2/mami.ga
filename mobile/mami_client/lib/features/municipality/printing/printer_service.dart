import 'package:shared_preferences/shared_preferences.dart';

import '../data/models/municipal_receipt_model.dart';
import 'bluetooth_printer_adapter.dart';
import 'esc_pos_command_builder.dart';
import 'printer_exception.dart';
import 'selected_printer.dart';

class PrinterService {
  PrinterService(this._adapter);

  final BluetoothPrinterAdapter _adapter;

  static const _printerMacKey = 'municipality.printer_mac';
  static const _printerNameKey = 'municipality.printer_name';
  static const _legacyMacKey = 'municipality.selected_printer_mac';

  Future<List<BluetoothPrinterDevice>> listPairedPrinters() async {
    if (!await _adapter.ensurePermissions()) {
      throw PrinterException(PrinterFailureReason.permissionDenied);
    }
    if (!await _adapter.isBluetoothEnabled()) {
      throw PrinterException(PrinterFailureReason.bluetoothDisabled);
    }

    return _adapter.scanPairedDevices();
  }

  Future<SelectedPrinter?> selectedPrinter() async {
    final prefs = await SharedPreferences.getInstance();
    var mac = prefs.getString(_printerMacKey);
    var name = prefs.getString(_printerNameKey) ?? '';

    if (mac == null || mac.isEmpty) {
      mac = prefs.getString(_legacyMacKey);
      if (mac != null && mac.isNotEmpty) {
        await selectPrinter(BluetoothPrinterDevice(name: name, macAddress: mac));
      }
    }

    if (mac == null || mac.isEmpty) {
      return null;
    }

    return SelectedPrinter(name: name, macAddress: mac);
  }

  Future<String?> selectedPrinterMac() async {
    return (await selectedPrinter())?.macAddress;
  }

  Future<void> selectPrinter(BluetoothPrinterDevice device) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_printerMacKey, device.macAddress);
    await prefs.setString(_printerNameKey, device.name);
    await prefs.setString(_legacyMacKey, device.macAddress);
  }

  Future<void> clearSelectedPrinter() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_printerMacKey);
    await prefs.remove(_printerNameKey);
    await prefs.remove(_legacyMacKey);
  }

  Future<void> prepareForPrint() async {
    if (!await _adapter.ensurePermissions()) {
      throw PrinterException(PrinterFailureReason.permissionDenied);
    }
    if (!await _adapter.isBluetoothEnabled()) {
      throw PrinterException(PrinterFailureReason.bluetoothDisabled);
    }

    final selected = await selectedPrinter();
    if (selected == null) {
      throw PrinterException(PrinterFailureReason.noPrinterConfigured);
    }

    final paired = await _adapter.scanPairedDevices();
    final exists = paired.any((device) => device.macAddress == selected.macAddress);
    if (!exists) {
      throw PrinterException(PrinterFailureReason.printerNotFound);
    }

    if (!await _adapter.isConnected) {
      final connected = await _adapter.connect(selected.macAddress);
      if (!connected) {
        throw PrinterException(PrinterFailureReason.connectionRefused);
      }
    }
  }

  Future<void> printReceipt(MunicipalReceiptPrintPayload payload) async {
    await prepareForPrint();

    final esc = EscPosCommandBuilder()..initialize();
    esc.textLine(payload.commune, bold: true, center: true);
    esc.textLine('QUITTANCE OFFICIELLE', bold: true, center: true);
    esc.hr();
    esc.textLine('Ref: ${payload.receiptNumber}');
    esc.textLine('Commerce: ${payload.commercialName}');
    esc.textLine('ID: ${payload.publicId}');
    esc.textLine('Montant: ${payload.amountXaf} XAF', bold: true);
    esc.textLine('Date: ${_formatDate(payload.issuedAt)}');
    esc.textLine('Agent: ${payload.agentName}');
    esc.textLine('Hash: ${payload.documentHashShort}');
    esc.feed(1);

    if (payload.verificationUrl.isNotEmpty) {
      esc.alignCenter();
      esc.qrCode(payload.verificationUrl);
      esc.alignLeft();
    }

    esc.feed(2);
    esc.cut();

    final ok = await _adapter.printBytes(esc.build());
    if (!ok) {
      final stillConnected = await _adapter.isConnected;
      throw PrinterException(
        stillConnected ? PrinterFailureReason.printFailed : PrinterFailureReason.printTimeout,
      );
    }
  }

  String _formatDate(String iso) {
    if (iso.length < 10) return iso;
    return iso.substring(0, 10);
  }
}
