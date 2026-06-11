import 'package:latlong2/latlong.dart';

import '../../features/location/domain/user_location_result.dart';

/// Helpers GPS / carte — évite les LatLng invalides (NaN, hors bornes).
class LatLngUtils {
  LatLngUtils._();

  static bool isValid(LatLng? point) {
    if (point == null) return false;
    final lat = point.latitude;
    final lng = point.longitude;
    return lat.isFinite &&
        lng.isFinite &&
        lat >= -90 &&
        lat <= 90 &&
        lng >= -180 &&
        lng <= 180;
  }

  static LatLng? tryCreate(double latitude, double longitude) {
    final point = LatLng(latitude, longitude);
    return isValid(point) ? point : null;
  }

  static LatLng orFallback(LatLng? point) =>
      isValid(point) ? point! : UserLocationResult.librevilleFallback;

  static List<LatLng> validPoints(Iterable<LatLng?> points) =>
      points.whereType<LatLng>().where(isValid).toList();

  /// Points distincts (tolérance ~1 m) pour éviter fitCamera sur doublons.
  static List<LatLng> distinctPoints(List<LatLng> points) {
    const epsilon = 0.00001;
    final distinct = <LatLng>[];
    for (final point in points) {
      final duplicate = distinct.any(
        (existing) =>
            (existing.latitude - point.latitude).abs() < epsilon &&
            (existing.longitude - point.longitude).abs() < epsilon,
      );
      if (!duplicate) distinct.add(point);
    }
    return distinct;
  }

  static String format(LatLng? point, {int fractionDigits = 4}) {
    if (!isValid(point)) return 'Aucune destination sélectionnée';
    return '${point!.latitude.toStringAsFixed(fractionDigits)}, '
        '${point.longitude.toStringAsFixed(fractionDigits)}';
  }
}
