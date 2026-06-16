import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/map/lat_lng_utils.dart';

/// Résout le point de départ pour une réservation texte (GPS auto ou carte).
class BookingPickupLocation {
  const BookingPickupLocation({
    required this.position,
    required this.fromGps,
  });

  final LatLng position;
  final bool fromGps;
}

class BookingPickupLocationDenied implements Exception {
  const BookingPickupLocationDenied(this.message);

  final String message;

  @override
  String toString() => message;
}

/// Demande GPS client pour commande texte sans carte.
Future<BookingPickupLocation> resolveTextBookingPickup({
  required BuildContext context,
  LatLng? mapPickup,
}) async {
  if (LatLngUtils.isValid(mapPickup)) {
    return BookingPickupLocation(position: mapPickup!, fromGps: false);
  }

  final consent = await showDialog<bool>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: const Text('Utiliser votre position'),
      content: const Text(
        'Autorisez-vous l\'application à utiliser votre position actuelle ?',
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(ctx).pop(false),
          child: const Text('Non'),
        ),
        FilledButton(
          onPressed: () => Navigator.of(ctx).pop(true),
          child: const Text('Oui'),
        ),
      ],
    ),
  );

  if (consent != true) {
    throw const BookingPickupLocationDenied(
      'Pour commander sans utiliser la carte, vous devez autoriser l\'accès à '
      'votre position. Sinon, veuillez sélectionner votre point de départ sur la carte.',
    );
  }

  var permission = await Geolocator.checkPermission();
  if (permission == LocationPermission.denied) {
    permission = await Geolocator.requestPermission();
  }

  if (permission == LocationPermission.denied ||
      permission == LocationPermission.deniedForever) {
    throw const BookingPickupLocationDenied(
      'Pour commander sans utiliser la carte, vous devez autoriser l\'accès à '
      'votre position. Sinon, veuillez sélectionner votre point de départ sur la carte.',
    );
  }

  final serviceEnabled = await Geolocator.isLocationServiceEnabled();
  if (!serviceEnabled) {
    throw const BookingPickupLocationDenied(
      'Activez le GPS de votre téléphone ou sélectionnez votre départ sur la carte.',
    );
  }

  final position = await Geolocator.getCurrentPosition(
    locationSettings: const LocationSettings(
      accuracy: LocationAccuracy.high,
    ),
  );

  final latLng = LatLng(position.latitude, position.longitude);
  if (!LatLngUtils.isValid(latLng)) {
    throw const BookingPickupLocationDenied(
      'Position GPS indisponible. Sélectionnez votre départ sur la carte.',
    );
  }

  return BookingPickupLocation(position: latLng, fromGps: true);
}
