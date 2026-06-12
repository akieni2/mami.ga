import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/realtime/reverb_service.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../data/rides_repository.dart';
import '../../domain/models/ride_model.dart';

final activeRideProvider =
    StateNotifierProvider<ActiveRideNotifier, AsyncValue<RideModel?>>(
  (ref) => ActiveRideNotifier(ref),
);

class ActiveRideNotifier extends StateNotifier<AsyncValue<RideModel?>> {
  ActiveRideNotifier(this._ref) : super(const AsyncValue.data(null)) {
    refresh();
  }

  final Ref _ref;
  Timer? _pollTimer;
  bool _driverChannelSubscribed = false;

  RidesRepository get _repo => _ref.read(ridesRepositoryProvider);

  void startPolling() {
    _pollTimer ??= Timer.periodic(AppConfig.ridePollInterval, (_) => refresh());
    _ensureDriverRealtime();
  }

  void stopPolling() {
    _pollTimer?.cancel();
    _pollTimer = null;
  }

  Future<void> refresh() async {
    try {
      final ride = await _repo.fetchCurrentRide();
      state = AsyncValue.data(ride);
      if (ride != null) {
        _subscribeRideRealtime(ride.id);
      }
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }

  /// WebSocket Reverb + polling REST (compatibilité Sprint 01).
  void startHybridTracking() {
    startPolling();
    _ensureDriverRealtime();
  }

  void _ensureDriverRealtime() {
    final driverId = _ref.read(authStateProvider).valueOrNull?.driver?.id;
    if (driverId == null || _driverChannelSubscribed) return;

    _ref.read(reverbServiceProvider).subscribeDriver(driverId, (event, _) {
      if (ReverbService.rideEvents.contains(event)) {
        refresh();
      }
    });
    _driverChannelSubscribed = true;
  }

  void _subscribeRideRealtime(int rideId) {
    _ref.read(reverbServiceProvider).subscribeRide(rideId, (event, _) {
      if (ReverbService.rideEvents.contains(event)) {
        refresh();
      }
    });
  }

  Future<RideModel> acceptOffer(int rideId, int offerId) async {
    final ride = await _repo.acceptOffer(rideId, offerId);
    state = AsyncValue.data(ride);
    _subscribeRideRealtime(rideId);
    return ride;
  }

  Future<RideModel> accept(int id) async {
    final ride = await _repo.accept(id);
    state = AsyncValue.data(ride);
    _subscribeRideRealtime(id);
    return ride;
  }

  Future<void> reject(int id) async {
    await _repo.reject(id);
    await refresh();
  }

  Future<RideModel> arrived(int id) async {
    final ride = await _repo.arrived(id);
    state = AsyncValue.data(ride);
    return ride;
  }

  Future<RideModel> start(int id) async {
    final ride = await _repo.start(id);
    state = AsyncValue.data(ride);
    return ride;
  }

  Future<RideModel> complete(int id) async {
    final ride = await _repo.complete(id);
    state = const AsyncValue.data(null);
    return ride;
  }

  @override
  void dispose() {
    stopPolling();
    super.dispose();
  }
}
