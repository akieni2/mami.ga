import 'dart:async';
import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';

import '../config/app_config.dart';
import '../config/reverb_config.dart';
import '../network/api_client.dart';
import '../storage/token_storage.dart';

typedef RealtimeHandler = void Function(String eventName, Map<String, dynamic> data);

class ReverbService {
  ReverbService(this._dio, this._tokenStorage);

  final Dio _dio;
  final TokenStorage _tokenStorage;
  final PusherChannelsFlutter _pusher = PusherChannelsFlutter.getInstance();

  final _handlers = <String, List<RealtimeHandler>>{};
  final _subscribed = <String>{};
  bool _initialized = false;

  static const rideEvents = {
    'RideRequested',
    'RideAssigned',
    'RideAccepted',
    'RideArrived',
    'RideStarted',
    'RideCompleted',
    'DriverLocationUpdated',
  };

  /// Événements dispatch P3 (canal private-driver-{id}).
  static const dispatchEvents = {
    'RideOfferCreated',
    'RideOfferAccepted',
    'RideSearchExpired',
  };

  Future<void> ensureConnected() async {
    if (_initialized) return;

    final token = await _tokenStorage.readToken();
    if (token == null || token.isEmpty) return;

    final authUrl = ReverbConfig.broadcastAuthUrl(AppConfig.apiBaseUrl);

    await _pusher.init(
      apiKey: ReverbConfig.appKey,
      cluster: ReverbConfig.pusherCluster,
      useTLS: ReverbConfig.useTls,
      onAuthorizer: (channelName, socketId, options) => _authorize(
        authUrl: authUrl,
        token: token,
        channelName: channelName,
        socketId: socketId,
      ),
      onEvent: _handleEvent,
    );

    await _configureReverbNativeLayer();

    await _pusher.connect();
    _initialized = true;
  }

  Future<Map<String, dynamic>> _authorize({
    required String authUrl,
    required String token,
    required String channelName,
    required String socketId,
  }) async {
    final response = await _dio.post(
      authUrl,
      data: {
        'socket_id': socketId,
        'channel_name': channelName,
      },
      options: Options(
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ),
    );

    return jsonDecode(response.data is String
        ? response.data as String
        : jsonEncode(response.data)) as Map<String, dynamic>;
  }

  void _handleEvent(PusherEvent event) {
    final handlers = _handlers[event.channelName] ?? [];
    Map<String, dynamic> data = {};
    try {
      final decoded = jsonDecode(event.data);
      if (decoded is Map<String, dynamic>) {
        data = decoded;
      }
    } catch (_) {}

    final eventName = data['event'] as String? ?? event.eventName;
    final payload = data['payload'] is Map
        ? Map<String, dynamic>.from(data['payload'] as Map)
        : data;

    for (final handler in handlers) {
      handler(eventName, payload);
    }
  }

  /// pusher_channels_flutter 2.4.0 n'expose pas host/wsPort dans init().
  /// iOS et notre patch Android les acceptent via le MethodChannel natif.
  Future<void> _configureReverbNativeLayer() async {
    await _pusher.methodChannel.invokeMethod<void>('init', {
      'apiKey': ReverbConfig.appKey,
      'host': ReverbConfig.host,
      'wsPort': ReverbConfig.port,
      'wssPort': ReverbConfig.port,
      'useTLS': ReverbConfig.useTls,
      'authorizer': true,
    });
  }

  Future<void> subscribe(String channelName, RealtimeHandler handler) async {
    await ensureConnected();
    _handlers.putIfAbsent(channelName, () => []).add(handler);

    if (_subscribed.contains(channelName)) return;

    await _pusher.subscribe(channelName: channelName);
    _subscribed.add(channelName);
  }

  Future<void> subscribeDriver(int driverId, RealtimeHandler handler) =>
      subscribe('private-driver-$driverId', handler);

  Future<void> subscribeRide(int rideId, RealtimeHandler handler) =>
      subscribe('private-ride-$rideId', handler);

  Future<void> unsubscribe(String channelName) async {
    if (!_subscribed.contains(channelName)) return;
    await _pusher.unsubscribe(channelName: channelName);
    _subscribed.remove(channelName);
    _handlers.remove(channelName);
  }

  Future<void> disconnect() async {
    if (!_initialized) return;
    await _pusher.disconnect();
    _initialized = false;
    _subscribed.clear();
    _handlers.clear();
  }
}

final reverbServiceProvider = Provider<ReverbService>((ref) {
  final service = ReverbService(
    ref.watch(dioProvider),
    ref.watch(tokenStorageProvider),
  );
  ref.onDispose(service.disconnect);
  return service;
});
