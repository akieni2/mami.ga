import 'dart:convert';

/// Minimal ESC/POS command builder for 58 mm thermal printers.
class EscPosCommandBuilder {
  EscPosCommandBuilder();

  final List<int> _bytes = [];

  List<int> build() => List<int>.from(_bytes);

  void initialize() {
    _bytes.addAll([0x1B, 0x40]);
  }

  void alignCenter() {
    _bytes.addAll([0x1B, 0x61, 0x01]);
  }

  void alignLeft() {
    _bytes.addAll([0x1B, 0x61, 0x00]);
  }

  void boldOn() {
    _bytes.addAll([0x1B, 0x45, 0x01]);
  }

  void boldOff() {
    _bytes.addAll([0x1B, 0x45, 0x00]);
  }

  void text(String value) {
    _bytes.addAll(utf8.encode(value));
    _bytes.add(0x0A);
  }

  void textLine(String value, {bool bold = false, bool center = false}) {
    if (center) alignCenter();
    if (bold) boldOn();
    text(value);
    if (bold) boldOff();
    if (center) alignLeft();
  }

  void hr() {
    text('--------------------------------');
  }

  void feed(int lines) {
    _bytes.addAll([0x1B, 0x64, lines.clamp(0, 255)]);
  }

  void cut() {
    _bytes.addAll([0x1D, 0x56, 0x00]);
  }

  void qrCode(String data, {int moduleSize = 6}) {
    final encoded = utf8.encode(data);
    final size = moduleSize.clamp(1, 16);

    _bytes.addAll([0x1D, 0x28, 0x6B, 0x04, 0x00, 0x31, 0x41, 0x32, 0x00]);
    _bytes.addAll([0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x43, size]);
    _bytes.addAll([0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x45, 0x30]);

    final storeLen = encoded.length + 3;
    _bytes.addAll([
      0x1D,
      0x28,
      0x6B,
      storeLen % 256,
      storeLen ~/ 256,
      0x31,
      0x50,
      0x30,
    ]);
    _bytes.addAll(encoded);
    _bytes.addAll([0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x51, 0x30]);
  }
}
