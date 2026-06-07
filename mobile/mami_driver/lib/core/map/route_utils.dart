import 'dart:math';

import 'package:latlong2/latlong.dart';

class RouteUtils {
  static List<LatLng> straightLine(LatLng from, LatLng to, {int segments = 24}) {
    final points = <LatLng>[];
    for (var i = 0; i <= segments; i++) {
      final ratio = segments == 0 ? 0.0 : i / segments;
      points.add(LatLng(
        from.latitude + (to.latitude - from.latitude) * ratio,
        from.longitude + (to.longitude - from.longitude) * ratio,
      ));
    }
    return points;
  }

  static LatLng boundsCenter(List<LatLng> points) {
    if (points.isEmpty) return const LatLng(0.4162, 9.4673);
    final lat = points.map((p) => p.latitude).reduce((a, b) => a + b) / points.length;
    final lng = points.map((p) => p.longitude).reduce((a, b) => a + b) / points.length;
    return LatLng(lat, lng);
  }

  static double _deg2rad(double deg) => deg * (pi / 180);
}
