import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/realtime/reverb_service.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../data/rides_repository.dart';
import '../../domain/models/ride_offer_model.dart';

final pendingOffersProvider =
    StateNotifierProvider<PendingOffersNotifier, AsyncValue<List<RideOfferModel>>>(
  (ref) => PendingOffersNotifier(ref),
);

class PendingOffersNotifier
    extends StateNotifier<AsyncValue<List<RideOfferModel>>> {
  PendingOffersNotifier(this._ref) : super(const AsyncValue.loading()) {
    refresh();
  }

  final Ref _ref;
  Timer? _pollTimer;
  bool _driverChannelSubscribed = false;

  RidesRepository get _repo => _ref.read(ridesRepositoryProvider);

  Future<void> refresh() async {
    try {
      final offers = await _repo.fetchCurrentOffers();
      state = AsyncValue.data(offers);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }

  void startHybridTracking() {
    _pollTimer ??= Timer.periodic(AppConfig.ridePollInterval, (_) => refresh());
    _ensureDriverRealtime();
  }

  void stopTracking() {
    _pollTimer?.cancel();
    _pollTimer = null;
  }

  void _ensureDriverRealtime() {
    final driverId = _ref.read(authStateProvider).valueOrNull?.driver?.id;
    if (driverId == null || _driverChannelSubscribed) return;

    _ref.read(reverbServiceProvider).subscribeDriver(driverId, (event, _) {
      if (ReverbService.dispatchEvents.contains(event)) {
        refresh();
      }
    });
    _driverChannelSubscribed = true;
  }

  Future<void> rejectOffer(RideOfferModel offer) async {
    await _repo.rejectOffer(offer.rideId, offer.id);
    final current = state.valueOrNull ?? [];
    state = AsyncValue.data(
      current.where((o) => o.id != offer.id).toList(),
    );
  }

  @override
  void dispose() {
    stopTracking();
    super.dispose();
  }
}
