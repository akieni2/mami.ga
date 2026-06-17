import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import 'models/economic_operator_category_model.dart';
import 'models/economic_operator_model.dart';

class EconomicOperatorRepository {
  EconomicOperatorRepository(this._dio);

  final Dio _dio;

  Future<List<EconomicOperatorCategoryModel>> fetchCategories() async {
    final response = await _dio.get('/municipality/economic-categories');
    final envelope = parseApiData(response.data);
    final list = envelope['data'] as List<dynamic>;

    return list
        .map((e) =>
            EconomicOperatorCategoryModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<EconomicOperatorModel> enrollOperator({
    required String commercialName,
    required String activityLabel,
    required int categoryId,
    required String responsibleName,
    required String phone,
    String? email,
    required double latitude,
    required double longitude,
    required double gpsAccuracyM,
    required DateTime gpsCapturedAt,
    required bool locationConfirmed,
    required File facadePhoto,
    File? tradeRegistryPhoto,
    File? businessLicensePhoto,
    File? municipalAuthorizationPhoto,
    String syncStatus = 'synced',
  }) async {
    final formData = FormData.fromMap({
      'commercial_name': commercialName,
      'activity_label': activityLabel,
      'category_id': categoryId,
      'responsible_name': responsibleName,
      'phone': phone,
      if (email != null && email.isNotEmpty) 'email': email,
      'latitude': latitude,
      'longitude': longitude,
      'gps_accuracy_m': gpsAccuracyM,
      'gps_captured_at': gpsCapturedAt.toUtc().toIso8601String(),
      'location_confirmed': locationConfirmed ? 1 : 0,
      'sync_status': syncStatus,
      'facade': await MultipartFile.fromFile(
        facadePhoto.path,
        filename: facadePhoto.path.split(Platform.pathSeparator).last,
      ),
      if (tradeRegistryPhoto != null)
        'trade_registry': await MultipartFile.fromFile(
          tradeRegistryPhoto.path,
          filename: tradeRegistryPhoto.path.split(Platform.pathSeparator).last,
        ),
      if (businessLicensePhoto != null)
        'business_license': await MultipartFile.fromFile(
          businessLicensePhoto.path,
          filename:
              businessLicensePhoto.path.split(Platform.pathSeparator).last,
        ),
      if (municipalAuthorizationPhoto != null)
        'municipal_authorization': await MultipartFile.fromFile(
          municipalAuthorizationPhoto.path,
          filename: municipalAuthorizationPhoto.path
              .split(Platform.pathSeparator)
              .last,
        ),
    });

    final response = await _dio.post(
      '/municipality/operators',
      data: formData,
      options: Options(contentType: 'multipart/form-data'),
    );

    final envelope = parseApiData(response.data);
    return EconomicOperatorModel.fromJson(
      envelope['data'] as Map<String, dynamic>,
    );
  }

  Future<EconomicOperatorDashboardModel> fetchDashboard() async {
    final response = await _dio.get('/municipality/operators/dashboard');
    final envelope = parseApiData(response.data);

    return EconomicOperatorDashboardModel.fromJson(
      envelope['data'] as Map<String, dynamic>,
    );
  }
}

final economicOperatorRepositoryProvider =
    Provider<EconomicOperatorRepository>(
  (ref) => EconomicOperatorRepository(ref.watch(dioProvider)),
);
