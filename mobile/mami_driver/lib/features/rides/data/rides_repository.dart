import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/models/ride_model.dart';

class RidesRepository {
  RidesRepository(this._dio);

  final Dio _dio;

  Future<RideModel?> fetchCurrentRide() async {
    final response = await _dio.get('/rides/current');
    final ride = extractData<dynamic>(response.data, (d) => d);

    if (ride == null) return null;

    return RideModel.fromJson(ride as Map<String, dynamic>);
  }

  Future<List<RideModel>> fetchHistory() async {
    final response = await _dio.get('/rides/history', queryParameters: {
      'as_driver': true,
    });

    final data = extractData<dynamic>(response.data, (d) => d);
    final list = data is List ? data : <dynamic>[];

    return list
        .map((e) => RideModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<RideModel> accept(int rideId) => _action(rideId, 'accept');

  Future<RideModel> reject(int rideId) => _action(rideId, 'reject');

  Future<RideModel> arrived(int rideId) => _action(rideId, 'arrived');

  Future<RideModel> start(int rideId) => _action(rideId, 'start');

  Future<RideModel> complete(int rideId) => _action(rideId, 'complete');

  Future<RideModel> _action(int rideId, String action) async {
    final response = await _dio.post('/rides/$rideId/$action');

    return extractData<RideModel>(
      response.data,
      (data) => RideModel.fromJson(data as Map<String, dynamic>),
    );
  }
}

final ridesRepositoryProvider = Provider<RidesRepository>(
  (ref) => RidesRepository(ref.watch(dioProvider)),
);
