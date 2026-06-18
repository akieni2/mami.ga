import 'package:shared_preferences/shared_preferences.dart';

import '../data/models/municipal_receipt_model.dart';
import 'bluetooth_printer_adapter.dart';
import 'esc_pos_command_builder.dart';

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

    return _adapter.printBytes(esc.build());
  }

  String _formatDate(String iso) {
    if (iso.length < 10) return iso;
    return iso.substring(0, 10);
  }
}
