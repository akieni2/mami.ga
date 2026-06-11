import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/models/payment_method.dart';
import '../domain/models/ride_model.dart';
import '../domain/models/trip_estimate.dart';

class RidesRepository {
  RidesRepository(this._dio);

  final Dio _dio;

  /// Estimation serveur (P1) — sans création de course.
  Future<TripEstimate> estimateTrip({
    required double pickupLatitude,
    required double pickupLongitude,
    required double destinationLatitude,
    required double destinationLongitude,
  }) async {
    final response = await _dio.post('/rides/estimate', data: {
      'pickup_latitude': pickupLatitude,
      'pickup_longitude': pickupLongitude,
      'destination_latitude': destinationLatitude,
      'destination_longitude': destinationLongitude,
    });

    return extractData<TripEstimate>(
      response.data,
      (data) => TripEstimate.fromJson(data as Map<String, dynamic>),
    );
  }

  /// P2A — demande text-first (GPS facultatif).
  Future<RideModel> requestTextRide({
    required String pickupLabel,
    required String destinationLabel,
    required double proposedPrice,
    required RidePaymentMethod paymentMethod,
    double? pickupLatitude,
    double? pickupLongitude,
    double? destinationLatitude,
    double? destinationLongitude,
  }) async {
    final data = <String, dynamic>{
      'pickup_label': pickupLabel,
      'destination_label': destinationLabel,
      'proposed_price': proposedPrice,
      'payment_method': paymentMethod.apiValue,
    };

    if (pickupLatitude != null && pickupLongitude != null) {
      data['pickup_latitude'] = pickupLatitude;
      data['pickup_longitude'] = pickupLongitude;
    }
    if (destinationLatitude != null && destinationLongitude != null) {
      data['destination_latitude'] = destinationLatitude;
      data['destination_longitude'] = destinationLongitude;
    }

    final response = await _dio.post('/rides/request', data: data);

    return extractData<RideModel>(
      response.data,
      (d) => RideModel.fromJson(d as Map<String, dynamic>),
    );
  }

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
