import 'package:flutter_test/flutter_test.dart';
import 'package:mami_client/features/municipality/printing/printer_exception.dart';
import 'package:mami_client/features/municipality/printing/selected_printer.dart';

void main() {
  group('PrinterFailureReason', () {
    test('messages are explicit in French', () {
      expect(
        PrinterFailureReason.bluetoothDisabled.message,
        contains('Bluetooth'),
      );
      expect(
        PrinterFailureReason.permissionDenied.message,
        contains('BLUETOOTH'),
      );
      expect(
        PrinterFailureReason.printerNotFound.message,
        contains('appair'),
      );
      expect(
        PrinterFailureReason.connectionRefused.message,
        contains('Connexion refusée'),
      );
      expect(
        PrinterFailureReason.printTimeout.message,
        contains('Délai'),
      );
    });
  });

  group('SelectedPrinter', () {
    test('displayLabel includes name and MAC', () {
      const printer = SelectedPrinter(
        name: 'RPP02N',
        macAddress: '66:02:BD:06:18:7B',
      );

      expect(printer.displayLabel, 'RPP02N (66:02:BD:06:18:7B)');
    });

    test('displayLabel falls back to MAC when name empty', () {
      const printer = SelectedPrinter(
        name: '',
        macAddress: '66:02:BD:06:18:7B',
      );

      expect(printer.displayLabel, '66:02:BD:06:18:7B');
    });
  });
}
