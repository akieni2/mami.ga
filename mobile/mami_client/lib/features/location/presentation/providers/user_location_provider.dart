import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';

final userLocationProvider = FutureProvider<LatLng?>((ref) async {
  var permission = await Geolocator.checkPermission();
  if (permission == LocationPermission.denied) {
    permission = await Geolocator.requestPermission();
  }

  if (permission != LocationPermission.always &&
      permission != LocationPermission.whileInUse) {
    return const LatLng(0.4162, 9.4673);
  }

  final position = await Geolocator.getCurrentPosition(
    locationSettings: const LocationSettings(accuracy: LocationAccuracy.high),
  );

  return LatLng(position.latitude, position.longitude);
});
