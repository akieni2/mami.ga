import 'dart:math';

double km(double lat1, double lon1, double lat2, double lon2) {
  const r = 6371.0;
  final dLat = (lat2 - lat1) * pi / 180;
  final dLon = (lon2 - lon1) * pi / 180;
  final a = sin(dLat / 2) * sin(dLat / 2) +
      cos(lat1 * pi / 180) *
          cos(lat2 * pi / 180) *
          sin(dLon / 2) *
          sin(dLon / 2);
  return r * 2 * atan2(sqrt(a), sqrt(1 - a));
}

void est(String name, double pu, double po, double du, double do_) {
  final d = km(pu, po, du, do_);
  final p = (500 + d * 250).roundToDouble();
  final m = max(1, (d / 25 * 60).ceil());
  print('$name: distance_km=${d.toStringAsFixed(3)} duration_min=$m suggested_price=$p');
}

void main() {
  est('short', 0.4162, 9.4673, 0.4180, 9.4690);
  est('medium', 0.4162, 9.4673, 0.3900, 9.4500);
  est('long', 0.4162, 9.4673, 0.3500, 9.5000);
}
