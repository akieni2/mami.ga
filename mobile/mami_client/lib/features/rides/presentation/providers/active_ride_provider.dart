import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/realtime/reverb_service.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
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
  int? _realtimeRideId;

  RidesRepository get _repo => _ref.read(ridesRepositoryProvider);

  void setRide(RideModel ride) {
    _ref.read(activeRideIdProvider.notifier).state = ride.id;
    state = AsyncValue.data(ride);
  }

  void clear() {
    _ref.read(activeRideIdProvider.notifier).state = null;
    state = const AsyncValue.data(null);
    stopPolling();
    _stopRealtime();
  }

  Future<RideModel> refresh(int id) async {
    final ride = await _repo.fetchRide(id);
    state = AsyncValue.data(ride);
    return ride;
  }

  /// Polling REST (fallback) + WebSocket Reverb (Sprint 02).
  void startHybridTracking(int id) {
    startPolling(id);
    _startRealtime(id);
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

  void _startRealtime(int rideId) {
    if (_realtimeRideId == rideId) return;
    _stopRealtime();

    final userId = _ref.read(authStateProvider).valueOrNull?.id;
    final reverb = _ref.read(reverbServiceProvider);

    void onEvent(String event, Map<String, dynamic> _) {
      if (ReverbService.rideEvents.contains(event)) {
        refresh(rideId);
      }
    }

    reverb.subscribeRide(rideId, onEvent);
    if (userId != null) {
      reverb.subscribeUser(userId, onEvent);
    }

    _realtimeRideId = rideId;
  }

  void _stopRealtime() {
    if (_realtimeRideId == null) return;
    final reverb = _ref.read(reverbServiceProvider);
    reverb.unsubscribe('private-ride-$_realtimeRideId');
    final userId = _ref.read(authStateProvider).valueOrNull?.id;
    if (userId != null) {
      reverb.unsubscribe('private-user-$userId');
    }
    _realtimeRideId = null;
  }

  @override
  void dispose() {
    stopPolling();
    _stopRealtime();
    super.dispose();
  }
}
