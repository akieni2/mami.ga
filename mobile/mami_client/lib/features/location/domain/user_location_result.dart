import 'package:latlong2/latlong.dart';

/// Résultat GPS client — position réelle ou fallback Libreville.
class UserLocationResult {
  const UserLocationResult({
    required this.position,
    required this.isGpsAvailable,
  });

  static const LatLng librevilleFallback = LatLng(0.4162, 9.4673);

  final LatLng position;
  final bool isGpsAvailable;
}
