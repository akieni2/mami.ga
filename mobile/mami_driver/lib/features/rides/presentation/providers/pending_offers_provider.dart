import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/logging/offers_logger.dart';
import '../../../../core/realtime/reverb_service.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../data/rides_repository.dart';
import '../../domain/models/ride_offer_model.dart';

final pendingOffersProvider =
    StateNotifierProvider<PendingOffersNotifier, AsyncValue<List<RideOfferModel>>>(
  (ref) {
    final notifier = PendingOffersNotifier(ref);
    ref.listen<AsyncValue<dynamic>>(authStateProvider, (prev, next) {
      final driverId = next.valueOrNull?.driver?.id;
      if (driverId != null) {
        notifier.startHybridTracking();
        notifier.refresh();
      }
    });
    return notifier;
  },
);

class PendingOffersNotifier
    extends StateNotifier<AsyncValue<List<RideOfferModel>>> {
  PendingOffersNotifier(this._ref) : super(const AsyncValue.data([]));

  final Ref _ref;
  Timer? _pollTimer;
  bool _driverChannelSubscribed = false;

  RidesRepository get _repo => _ref.read(ridesRepositoryProvider);

  int? get _driverId => _ref.read(authStateProvider).valueOrNull?.driver?.id;

  Future<void> refresh() async {
    final driverId = _driverId;
    if (driverId == null) {
      OffersLogger.fetchError('driver id unavailable — auth not ready');
      return;
    }

    OffersLogger.fetchStart(driverId);

    try {
      final offers = await _repo.fetchCurrentOffers();
      OffersLogger.fetchSuccess(offers.length);
      OffersLogger.fetchCount(offers.length);
      state = AsyncValue.data(offers);
      _ensureDriverRealtime();
    } catch (e, st) {
      OffersLogger.fetchError(e, st);
      state = AsyncValue.error(e, st);
    }
  }

  void startHybridTracking() {
    if (_driverId == null) return;

    _pollTimer ??= Timer.periodic(
      AppConfig.offerPollInterval,
      (_) => refresh(),
    );
    _ensureDriverRealtime();
    refresh();
  }

  void stopTracking() {
    _pollTimer?.cancel();
    _pollTimer = null;
  }

  void _ensureDriverRealtime() {
    final driverId = _driverId;
    if (driverId == null || _driverChannelSubscribed) return;

    OffersLogger.reverbSubscribe(driverId);

    _ref.read(reverbServiceProvider).subscribeDriver(driverId, (event, payload) {
      if (ReverbService.dispatchEvents.contains(event)) {
        if (event == 'RideOfferCreated') {
          OffersLogger.reverbOfferReceived(event, payload);
        }
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
