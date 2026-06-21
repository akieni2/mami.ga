import 'dart:async';
import 'dart:io';

import 'package:permission_handler/permission_handler.dart';
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
  static const Duration printTimeout = Duration(seconds: 15);

  Future<bool> isBluetoothEnabled() => PrintBluetoothThermal.bluetoothEnabled;

  Future<bool> isPermissionGranted() => PrintBluetoothThermal.isPermissionBluetoothGranted;

  /// Demande BLUETOOTH_SCAN / BLUETOOTH_CONNECT (Android 12+).
  Future<bool> ensurePermissions() async {
    if (!Platform.isAndroid) {
      return isPermissionGranted();
    }

    final scanStatus = await Permission.bluetoothScan.request();
    final connectStatus = await Permission.bluetoothConnect.request();

    if (scanStatus.isGranted && connectStatus.isGranted) {
      return true;
    }

    if (scanStatus.isPermanentlyDenied || connectStatus.isPermanentlyDenied) {
      return false;
    }

    return await isPermissionGranted();
  }

  Future<List<BluetoothPrinterDevice>> scanPairedDevices() async {
    final devices = await PrintBluetoothThermal.pairedBluetooths;
    return devices.map(BluetoothPrinterDevice.fromNative).toList();
  }

  Future<bool> connect(String macAddress) {
    return PrintBluetoothThermal.connect(macPrinterAddress: macAddress);
  }

  Future<bool> disconnect() => PrintBluetoothThermal.disconnect;

  Future<bool> printBytes(List<int> bytes) async {
    return PrintBluetoothThermal.writeBytes(bytes).timeout(
      printTimeout,
      onTimeout: () => false,
    );
  }

  Future<bool> get isConnected => PrintBluetoothThermal.connectionStatus;
}
