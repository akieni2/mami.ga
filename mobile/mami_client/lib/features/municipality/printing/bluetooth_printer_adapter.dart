import 'package:print_bluetooth_thermal/print_bluetooth_thermal.dart';

class BluetoothPrinterDevice {
  BluetoothPrinterDevice({
    required this.name,
    required this.macAddress,
  });

  factory BluetoothPrinterDevice.fromNative(BluetoothInfo info) {
    return BluetoothPrinterDevice(
      name: info.name,
      macAddress: info.macAdress,
    );
  }

  final String name;
  final String macAddress;

  String get label => name.isNotEmpty ? '$name ($macAddress)' : macAddress;
}

class BluetoothPrinterAdapter {
  Future<bool> isBluetoothEnabled() => PrintBluetoothThermal.bluetoothEnabled;

  Future<bool> isPermissionGranted() => PrintBluetoothThermal.isPermissionBluetoothGranted;

  Future<List<BluetoothPrinterDevice>> scanPairedDevices() async {
    final devices = await PrintBluetoothThermal.pairedBluetooths;
    return devices.map(BluetoothPrinterDevice.fromNative).toList();
  }

  Future<bool> connect(String macAddress) {
    return PrintBluetoothThermal.connect(macPrinterAddress: macAddress);
  }

  Future<bool> disconnect() => PrintBluetoothThermal.disconnect;

  Future<bool> printBytes(List<int> bytes) {
    return PrintBluetoothThermal.writeBytes(bytes);
  }

  Future<bool> get isConnected => PrintBluetoothThermal.connectionStatus;
}
