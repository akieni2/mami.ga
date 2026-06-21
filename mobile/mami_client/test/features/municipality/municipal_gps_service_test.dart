import 'package:flutter_test/flutter_test.dart';
import 'package:geolocator/geolocator.dart';
import 'package:mami_client/features/municipality/domain/municipal_gps_service.dart';

Position _fakePosition() => Position(
      latitude: 0.338,
      longitude: 9.471,
      timestamp: DateTime.utc(2026, 6, 16),
      accuracy: 8,
      altitude: 0,
      altitudeAccuracy: 0,
      heading: 0,
      headingAccuracy: 0,
      speed: 0,
      speedAccuracy: 0,
    );

void main() {
  group('MunicipalGpsService.ensureGpsAvailable', () {
    test('réussit quand le GPS et les permissions sont OK', () async {
      final service = MunicipalGpsService(
        isLocationServiceEnabled: () async => true,
        checkPermission: () async => LocationPermission.whileInUse,
        requestPermission: () async => LocationPermission.whileInUse,
      );

      await expectLater(service.ensureGpsAvailable(), completes);
    });

    test('échoue si le GPS système est désactivé', () async {
      final service = MunicipalGpsService(
        isLocationServiceEnabled: () async => false,
      );

      expect(
        () => service.ensureGpsAvailable(),
        throwsA(
          isA<MunicipalGpsException>().having(
            (e) => e.message,
            'message',
            MunicipalGpsMessages.serviceDisabled,
          ),
        ),
      );
    });

    test('échoue si la permission est refusée', () async {
      final service = MunicipalGpsService(
        isLocationServiceEnabled: () async => true,
        checkPermission: () async => LocationPermission.denied,
        requestPermission: () async => LocationPermission.denied,
      );

      expect(
        () => service.ensureGpsAvailable(),
        throwsA(
          isA<MunicipalGpsException>().having(
            (e) => e.message,
            'message',
            MunicipalGpsMessages.permissionDenied,
          ),
        ),
      );
    });

    test('échoue si la permission est refusée définitivement', () async {
      final service = MunicipalGpsService(
        isLocationServiceEnabled: () async => true,
        checkPermission: () async => LocationPermission.denied,
        requestPermission: () async => LocationPermission.deniedForever,
      );

      expect(
        () => service.ensureGpsAvailable(),
        throwsA(
          isA<MunicipalGpsException>().having(
            (e) => e.message,
            'message',
            MunicipalGpsMessages.permissionDeniedForever,
          ),
        ),
      );
    });

    test('demande la permission si elle est initialement refusée puis accordée', () async {
      var requested = false;
      final service = MunicipalGpsService(
        isLocationServiceEnabled: () async => true,
        checkPermission: () async => LocationPermission.denied,
        requestPermission: () async {
          requested = true;
          return LocationPermission.whileInUse;
        },
      );

      await service.ensureGpsAvailable();
      expect(requested, isTrue);
    });
  });

  group('MunicipalGpsService.capturePosition', () {
    test('retourne la position après validation GPS', () async {
      final expected = _fakePosition();
      final service = MunicipalGpsService(
        isLocationServiceEnabled: () async => true,
        checkPermission: () async => LocationPermission.whileInUse,
        getCurrentPosition: () async => expected,
      );

      final position = await service.capturePosition();
      expect(position.latitude, expected.latitude);
      expect(position.longitude, expected.longitude);
    });

    test('ne capture pas si les permissions sont refusées', () async {
      var captureCalled = false;
      final service = MunicipalGpsService(
        isLocationServiceEnabled: () async => true,
        checkPermission: () async => LocationPermission.deniedForever,
        getCurrentPosition: () async {
          captureCalled = true;
          return _fakePosition();
        },
      );

      await expectLater(
        service.capturePosition(),
        throwsA(isA<MunicipalGpsException>()),
      );
      expect(captureCalled, isFalse);
    });
  });
}
