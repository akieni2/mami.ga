import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';

import '../../../../core/config/app_config.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../../driver/data/driver_repository.dart';
import '../../../driver/presentation/providers/driver_status_provider.dart';

final locationTrackerProvider =
    StateNotifierProvider<LocationTrackerNotifier, bool>(
  (ref) => LocationTrackerNotifier(ref),
);

class LocationTrackerNotifier extends StateNotifier<bool> {
  LocationTrackerNotifier(this._ref) : super(false);

  final Ref _ref;
  Timer? _timer;

  Future<bool> _ensurePermission() async {
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    return permission == LocationPermission.always ||
        permission == LocationPermission.whileInUse;
  }

  Future<void> start() async {
    if (state) return;

    final granted = await _ensurePermission();
    if (!granted) return;

    state = true;
    await _sendOnce();
    _timer = Timer.periodic(AppConfig.gpsInterval, (_) => _sendOnce());
  }

  void stop() {
    _timer?.cancel();
    _timer = null;
    state = false;
  }

  Future<void> _sendOnce() async {
    final status = _ref.read(driverStatusProvider).valueOrNull;
    if (status != DriverUiStatus.online && status != DriverUiStatus.busy) {
      return;
    }

    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
        ),
      );

      final driver = await _ref.read(driverRepositoryProvider).updateLocation(
            position.latitude,
            position.longitude,
          );

      _ref.read(authStateProvider.notifier).updateDriver(driver);
      _ref.read(driverStatusProvider.notifier).refreshFromDriver(driver);
    } catch (_) {
      // GPS or network failure — next tick will retry
    }
  }

  @override
  void dispose() {
    stop();
    super.dispose();
  }
}
