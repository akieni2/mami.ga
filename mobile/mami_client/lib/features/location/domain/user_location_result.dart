import 'package:latlong2/latlong.dart';

import '../../../core/map/lat_lng_utils.dart';

/// Résultat GPS client — position réelle ou fallback Libreville.
class UserLocationResult {
  const UserLocationResult({
    required this.position,
    required this.isGpsAvailable,
  });

  static const LatLng librevilleFallback = LatLng(0.4162, 9.4673);

  final LatLng position;
  final bool isGpsAvailable;

  factory UserLocationResult.fromCoordinates({
    required double latitude,
    required double longitude,
    required bool isGpsAvailable,
  }) {
    final position = LatLngUtils.tryCreate(latitude, longitude);
    return UserLocationResult(
      position: position ?? librevilleFallback,
      isGpsAvailable: isGpsAvailable && position != null,
    );
  }
}
