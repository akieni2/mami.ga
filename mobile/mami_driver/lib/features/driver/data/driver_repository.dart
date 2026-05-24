import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/models/driver_model.dart';

class DriverRepository {
  DriverRepository(this._dio);

  final Dio _dio;

  Future<DriverModel> setOnline(bool online) async {
    final response = await _dio.post('/drivers/availability', data: {
      'is_available': online,
    });

    return extractData<DriverModel>(
      response.data,
      (data) => DriverModel.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<DriverModel> updateLocation(double latitude, double longitude) async {
    final response = await _dio.post('/drivers/location/update', data: {
      'latitude': latitude,
      'longitude': longitude,
    });

    return extractData<DriverModel>(
      response.data,
      (data) => DriverModel.fromJson(data as Map<String, dynamic>),
    );
  }
}

final driverRepositoryProvider = Provider<DriverRepository>(
  (ref) => DriverRepository(ref.watch(dioProvider)),
);
