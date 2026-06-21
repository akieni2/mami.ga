class SelectedPrinter {
  const SelectedPrinter({
    required this.name,
    required this.macAddress,
  });

  final String name;
  final String macAddress;

  String get displayLabel =>
      name.trim().isNotEmpty ? '$name ($macAddress)' : macAddress;
}
