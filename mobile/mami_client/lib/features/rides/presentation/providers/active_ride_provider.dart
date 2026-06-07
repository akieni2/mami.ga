import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/config/app_config.dart';
import '../../data/rides_repository.dart';
import '../../domain/models/ride_model.dart';

final activeRideIdProvider = StateProvider<int?>((ref) => null);

final activeRideProvider =
    StateNotifierProvider<ActiveRideNotifier, AsyncValue<RideModel?>>(
  (ref) => ActiveRideNotifier(ref),
);

class ActiveRideNotifier extends StateNotifier<AsyncValue<RideModel?>> {
  ActiveRideNotifier(this._ref) : super(const AsyncValue.data(null));

  final Ref _ref;
  Timer? _pollTimer;

  RidesRepository get _repo => _ref.read(ridesRepositoryProvider);

  void setRide(RideModel ride) {
    _ref.read(activeRideIdProvider.notifier).state = ride.id;
    state = AsyncValue.data(ride);
  }

  void clear() {
    _ref.read(activeRideIdProvider.notifier).state = null;
    state = const AsyncValue.data(null);
    stopPolling();
  }

  Future<RideModel> refresh(int id) async {
    final ride = await _repo.fetchRide(id);
    state = AsyncValue.data(ride);
    return ride;
  }

  void startPolling(int id) {
    stopPolling();
    _pollTimer = Timer.periodic(AppConfig.ridePollInterval, (_) async {
      try {
        final ride = await _repo.fetchRide(id);
        state = AsyncValue.data(ride);
        if (!ride.isActive) {
          stopPolling();
        }
      } catch (_) {}
    });
  }

  void stopPolling() {
    _pollTimer?.cancel();
    _pollTimer = null;
  }

  @override
  void dispose() {
    stopPolling();
    super.dispose();
  }
}
