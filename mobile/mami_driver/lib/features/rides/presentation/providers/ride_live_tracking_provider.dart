import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/map/route_utils.dart';
import '../../../../core/realtime/reverb_service.dart';
import '../../data/rides_repository.dart';

class RideLiveTrackingState {
  const RideLiveTrackingState({
    this.driverPosition,
    this.route = const [],
    this.distanceKm,
    this.etaMinutes,
  });

  final LatLng? driverPosition;
  final List<LatLng> route;
  final double? distanceKm;
  final int? etaMinutes;

  RideLiveTrackingState copyWith({
    LatLng? driverPosition,
    List<LatLng>? route,
    double? distanceKm,
    int? etaMinutes,
  }) {
    return RideLiveTrackingState(
      driverPosition: driverPosition ?? this.driverPosition,
      route: route ?? this.route,
      distanceKm: distanceKm ?? this.distanceKm,
      etaMinutes: etaMinutes ?? this.etaMinutes,
    );
  }
}

final rideLiveTrackingProvider = StateNotifierProvider.autoDispose
    .family<RideLiveTrackingNotifier, RideLiveTrackingState, int>(
  (ref, rideId) => RideLiveTrackingNotifier(ref, rideId),
);

class RideLiveTrackingNotifier extends StateNotifier<RideLiveTrackingState> {
  RideLiveTrackingNotifier(this._ref, this._rideId)
      : super(const RideLiveTrackingState()) {
    _init();
  }

  final Ref _ref;
  final int _rideId;

  Future<void> _init() async {
    await refreshFromApi();
    _listenWebSocket();
  }

  Future<void> refreshFromApi() async {
    try {
      final data = await _ref.read(ridesRepositoryProvider).fetchTracking(_rideId);
      final driver = data['driver'] as Map<String, dynamic>?;
      final tracking = data['tracking'] as Map<String, dynamic>?;
      final routeData = data['route'] as Map<String, dynamic>?;

      LatLng? driverPos;
      if (driver?['latitude'] != null && driver?['longitude'] != null) {
        driverPos = LatLng(
          (driver!['latitude'] as num).toDouble(),
          (driver['longitude'] as num).toDouble(),
        );
      }

      final route = _parseRoute(routeData) ??
          (driverPos != null && tracking != null
              ? RouteUtils.straightLine(
                  driverPos,
                  LatLng(
                    (tracking['target_latitude'] as num).toDouble(),
                    (tracking['target_longitude'] as num).toDouble(),
                  ),
                )
              : <LatLng>[]);

      state = state.copyWith(
        driverPosition: driverPos,
        route: route,
        distanceKm: (tracking?['distance_km'] as num?)?.toDouble(),
        etaMinutes: (tracking?['eta_minutes'] as num?)?.toInt(),
      );
    } catch (_) {}
  }

  void updateDriverPosition(LatLng position, LatLng target) {
    state = state.copyWith(
      driverPosition: position,
      route: RouteUtils.straightLine(position, target),
    );
  }

  void _listenWebSocket() {
    _ref.read(reverbServiceProvider).subscribeRide(_rideId, (event, payload) {
      if (event != 'DriverLocationUpdated') return;
      final lat = payload['latitude'] as num?;
      final lng = payload['longitude'] as num?;
      if (lat == null || lng == null) return;

      final driverPos = LatLng(lat.toDouble(), lng.toDouble());
      state = state.copyWith(
        driverPosition: driverPos,
        distanceKm: (payload['distance_to_client_km'] as num?)?.toDouble(),
        etaMinutes: (payload['eta_minutes'] as num?)?.toInt(),
      );
    });
  }

  List<LatLng>? _parseRoute(Map<String, dynamic>? routeData) {
    if (routeData == null) return null;
    final coords = routeData['coordinates'];
    if (coords is! List) return null;
    return coords
        .map((c) => LatLng(
              (c['latitude'] as num).toDouble(),
              (c['longitude'] as num).toDouble(),
            ))
        .toList();
  }
}
