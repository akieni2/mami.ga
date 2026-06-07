import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/models/ride_model.dart';

class RidesRepository {
  RidesRepository(this._dio);

  final Dio _dio;

  Future<RideModel> requestRide({
    required double pickupLatitude,
    required double pickupLongitude,
    required double destinationLatitude,
    required double destinationLongitude,
  }) async {
    final response = await _dio.post('/rides/request', data: {
      'pickup_latitude': pickupLatitude,
      'pickup_longitude': pickupLongitude,
      'destination_latitude': destinationLatitude,
      'destination_longitude': destinationLongitude,
    });

    return extractData<RideModel>(
      response.data,
      (data) => RideModel.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<RideModel> fetchRide(int id) async {
    final response = await _dio.get('/rides/$id');

    return extractData<RideModel>(
      response.data,
      (data) => RideModel.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<Map<String, dynamic>> fetchTracking(int id) async {
    final response = await _dio.get('/rides/$id/tracking');

    return extractData<Map<String, dynamic>>(
      response.data,
      (data) => data as Map<String, dynamic>,
    );
  }

  Future<List<RideModel>> fetchHistory() async {
    final response = await _dio.get('/rides/history');

    final data = extractData<dynamic>(response.data, (d) => d);
    final list = data is List ? data : <dynamic>[];

    return list
        .map((e) => RideModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}

final ridesRepositoryProvider = Provider<RidesRepository>(
  (ref) => RidesRepository(ref.watch(dioProvider)),
);
