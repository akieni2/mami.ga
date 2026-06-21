/// Erreurs d'impression Bluetooth avec messages utilisateur explicites.
enum PrinterFailureReason {
  bluetoothDisabled('Activez le Bluetooth de votre téléphone.'),
  permissionDenied(
    'Autorisez les permissions Bluetooth (BLUETOOTH_SCAN et BLUETOOTH_CONNECT) dans les paramètres Android.',
  ),
  noPrinterConfigured(
    'Aucune imprimante sélectionnée. Choisissez une imprimante Bluetooth appairée.',
  ),
  printerNotFound(
    'Imprimante non trouvée parmi les appareils appairés. Vérifiez l\'appairage système.',
  ),
  connectionRefused(
    'Connexion refusée par l\'imprimante. Vérifiez qu\'elle est allumée et à portée.',
  ),
  printTimeout('Délai dépassé lors de l\'impression. Réessayez.'),
  printFailed('Échec de l\'envoi des données à l\'imprimante.');

  const PrinterFailureReason(this.message);

  final String message;
}

class PrinterException implements Exception {
  PrinterException(this.reason);

  final PrinterFailureReason reason;

  String get message => reason.message;

  @override
  String toString() => message;
}
