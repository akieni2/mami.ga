import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../auth/presentation/providers/auth_provider.dart';
import '../../data/driver_repository.dart';
import '../../domain/models/driver_model.dart';

enum DriverUiStatus { offline, online, busy }

DriverUiStatus uiStatusFromDriver(DriverModel? driver) {
  if (driver == null) return DriverUiStatus.offline;
  if (driver.presence == 'busy' || driver.status == 'on_ride') {
    return DriverUiStatus.busy;
  }
  if (driver.isAvailable && driver.presence == 'online') {
    return DriverUiStatus.online;
  }
  return DriverUiStatus.offline;
}

final driverStatusProvider =
    StateNotifierProvider<DriverStatusNotifier, AsyncValue<DriverUiStatus>>(
  (ref) => DriverStatusNotifier(ref),
);

class DriverStatusNotifier extends StateNotifier<AsyncValue<DriverUiStatus>> {
  DriverStatusNotifier(this._ref)
      : super(const AsyncValue.data(DriverUiStatus.offline)) {
    _syncFromAuth();
    _ref.listen(authStateProvider, (_, __) => _syncFromAuth());
  }

  final Ref _ref;

  DriverRepository get _repo => _ref.read(driverRepositoryProvider);

  void _syncFromAuth() {
    final user = _ref.read(authStateProvider).valueOrNull;
    state = AsyncValue.data(uiStatusFromDriver(user?.driver));
  }

  Future<void> setOnline(bool online) async {
    state = const AsyncValue.loading();
    try {
      final driver = await _repo.setOnline(online);
      _updateAuthDriver(driver);
      state = AsyncValue.data(uiStatusFromDriver(driver));
    } catch (e, st) {
      state = AsyncValue.error(e, st);
      _syncFromAuth();
    }
  }

  void refreshFromDriver(DriverModel driver) {
    _updateAuthDriver(driver);
    state = AsyncValue.data(uiStatusFromDriver(driver));
  }

  void _updateAuthDriver(DriverModel driver) {
    _ref.read(authStateProvider.notifier).updateDriver(driver);
  }
}
