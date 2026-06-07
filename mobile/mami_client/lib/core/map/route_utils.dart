import 'dart:math';

import 'package:latlong2/latlong.dart';

/// Tracé simple (Sprint 03) — futur : OSRM / GraphHopper / Valhalla.
class RouteUtils {
  static List<LatLng> straightLine(
    LatLng from,
    LatLng to, {
    int segments = 24,
  }) {
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

  static double estimateDistanceKm(LatLng from, LatLng to) {
    const earthRadiusKm = 6371.0;
    final dLat = _deg2rad(to.latitude - from.latitude);
    final dLon = _deg2rad(to.longitude - from.longitude);
    final a = sin(dLat / 2) * sin(dLat / 2) +
        cos(_deg2rad(from.latitude)) *
            cos(_deg2rad(to.latitude)) *
            sin(dLon / 2) *
            sin(dLon / 2);
    final c = 2 * atan2(sqrt(a), sqrt(1 - a));
    return earthRadiusKm * c;
  }

  static int estimateEtaMinutes(double distanceKm, {double speedKmh = 25}) {
    final hours = distanceKm / max(speedKmh, 1);
    return max(1, (hours * 60).ceil());
  }

  static double _deg2rad(double deg) => deg * (pi / 180);
}
