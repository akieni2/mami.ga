import 'package:geolocator/geolocator.dart';

/// Messages affichés à l'agent lors des opérations de recouvrement.
abstract final class MunicipalGpsMessages {
  static const serviceDisabled =
      'Veuillez activer la localisation de votre téléphone.';

  static const permissionDenied =
      'Autorisez la localisation pour effectuer les opérations de recouvrement.';

  static const permissionDeniedForever =
      'Veuillez autoriser la localisation dans les paramètres Android.';
}

class MunicipalGpsException implements Exception {
  MunicipalGpsException(this.message);

  final String message;

  @override
  String toString() => message;
}

typedef IsLocationServiceEnabled = Future<bool> Function();
typedef CheckLocationPermission = Future<LocationPermission> Function();
typedef RequestLocationPermission = Future<LocationPermission> Function();
typedef GetCurrentGpsPosition = Future<Position> Function();

/// Centralise la vérification des permissions GPS pour le module municipalité.
class MunicipalGpsService {
  MunicipalGpsService({
    IsLocationServiceEnabled? isLocationServiceEnabled,
    CheckLocationPermission? checkPermission,
    RequestLocationPermission? requestPermission,
    GetCurrentGpsPosition? getCurrentPosition,
  })  : _isLocationServiceEnabled =
            isLocationServiceEnabled ?? Geolocator.isLocationServiceEnabled,
        _checkPermission = checkPermission ?? Geolocator.checkPermission,
        _requestPermission = requestPermission ?? Geolocator.requestPermission,
        _getCurrentPosition = getCurrentPosition ??
            (() => Geolocator.getCurrentPosition(
                  locationSettings: const LocationSettings(
                    accuracy: LocationAccuracy.high,
                  ),
                ));

  final IsLocationServiceEnabled _isLocationServiceEnabled;
  final CheckLocationPermission _checkPermission;
  final RequestLocationPermission _requestPermission;
  final GetCurrentGpsPosition _getCurrentPosition;

  /// Vérifie que le GPS système est actif et que les permissions sont accordées.
  Future<void> ensureGpsAvailable() async {
    final serviceEnabled = await _isLocationServiceEnabled();
    if (!serviceEnabled) {
      throw MunicipalGpsException(MunicipalGpsMessages.serviceDisabled);
    }

    var permission = await _checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await _requestPermission();
    }

    if (permission == LocationPermission.deniedForever) {
      throw MunicipalGpsException(MunicipalGpsMessages.permissionDeniedForever);
    }

    if (permission == LocationPermission.denied) {
      throw MunicipalGpsException(MunicipalGpsMessages.permissionDenied);
    }
  }

  /// Capture la position après validation GPS / permissions.
  Future<Position> capturePosition() async {
    await ensureGpsAvailable();
    return _getCurrentPosition();
  }
}
