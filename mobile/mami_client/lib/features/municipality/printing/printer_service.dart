import 'package:esc_pos_utils_plus/esc_pos_utils_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../data/models/municipal_receipt_model.dart';
import 'bluetooth_printer_adapter.dart';

class PrinterService {
  PrinterService(this._adapter);

  final BluetoothPrinterAdapter _adapter;
  static const _printerKey = 'municipality.selected_printer_mac';

  Future<List<BluetoothPrinterDevice>> listPairedPrinters() {
    return _adapter.scanPairedDevices();
  }

  Future<String?> selectedPrinterMac() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_printerKey);
  }

  Future<void> selectPrinter(String macAddress) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_printerKey, macAddress);
  }

  Future<bool> ensureReady() async {
    if (!await _adapter.isPermissionGranted()) {
      return false;
    }
    if (!await _adapter.isBluetoothEnabled()) {
      return false;
    }

    final mac = await selectedPrinterMac();
    if (mac == null || mac.isEmpty) {
      return false;
    }

    if (!await _adapter.isConnected) {
      return _adapter.connect(mac);
    }

    return true;
  }

  Future<bool> printReceipt(MunicipalReceiptPrintPayload payload) async {
    if (!await ensureReady()) {
      return false;
    }

    final profile = await CapabilityProfile.load();
    final generator = Generator(PaperSize.mm58, profile);
    final bytes = <int>[];

    bytes.addAll(generator.text(payload.commune,
        styles: const PosStyles(align: PosAlign.center, bold: true)));
    bytes.addAll(generator.text('QUITTANCE OFFICIELLE',
        styles: const PosStyles(align: PosAlign.center, bold: true)));
    bytes.addAll(generator.hr());
    bytes.addAll(generator.text('Ref: ${payload.receiptNumber}'));
    bytes.addAll(generator.text('Commerce: ${payload.commercialName}'));
    bytes.addAll(generator.text('ID: ${payload.publicId}'));
    bytes.addAll(generator.text('Montant: ${payload.amountXaf} XAF',
        styles: const PosStyles(bold: true)));
    bytes.addAll(generator.text('Date: ${_formatDate(payload.issuedAt)}'));
    bytes.addAll(generator.text('Agent: ${payload.agentName}'));
    bytes.addAll(generator.text('Hash: ${payload.documentHashShort}'));
    bytes.addAll(generator.feed(1));

    if (payload.verificationUrl.isNotEmpty) {
      bytes.addAll(generator.qrcode(payload.verificationUrl));
    }

    bytes.addAll(generator.feed(2));
    bytes.addAll(generator.cut());

    return _adapter.printBytes(bytes);
  }

  String _formatDate(String iso) {
    if (iso.length < 10) return iso;
    return iso.substring(0, 10);
  }
}
