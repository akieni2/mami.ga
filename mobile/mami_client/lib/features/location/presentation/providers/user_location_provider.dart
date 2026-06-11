import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';

import '../../domain/user_location_result.dart';

final userLocationProvider = FutureProvider<UserLocationResult>((ref) async {
  var permission = await Geolocator.checkPermission();
  if (permission == LocationPermission.denied) {
    permission = await Geolocator.requestPermission();
  }

  if (permission != LocationPermission.always &&
      permission != LocationPermission.whileInUse) {
    debugPrint('P1 GPS refused — fallback Libreville');
    return const UserLocationResult(
      position: UserLocationResult.librevilleFallback,
      isGpsAvailable: false,
    );
  }

  try {
    final position = await Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(accuracy: LocationAccuracy.high),
    );
    debugPrint(
      'P1 GPS obtained: ${position.latitude.toStringAsFixed(4)}, '
      '${position.longitude.toStringAsFixed(4)}',
    );
    return UserLocationResult(
      position: LatLng(position.latitude, position.longitude),
      isGpsAvailable: true,
    );
  } catch (e) {
    debugPrint('P1 GPS refused — position unavailable ($e)');
    return const UserLocationResult(
      position: UserLocationResult.librevilleFallback,
      isGpsAvailable: false,
    );
  }
});
