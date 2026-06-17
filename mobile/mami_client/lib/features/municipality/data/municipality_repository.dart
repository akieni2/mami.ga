import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import 'models/municipality_report_model.dart';

class MunicipalityRepository {
  MunicipalityRepository(this._dio);

  final Dio _dio;

  Future<MunicipalityReportModel> createReport({
    required String category,
    required String title,
    required String description,
    required double latitude,
    required double longitude,
    String? address,
    File? photo,
  }) async {
    final formData = FormData.fromMap({
      'category': category,
      'title': title,
      'description': description,
      'latitude': latitude,
      'longitude': longitude,
      if (address != null && address.isNotEmpty) 'address': address,
      if (photo != null)
        'photo': await MultipartFile.fromFile(
          photo.path,
          filename: photo.path.split(Platform.pathSeparator).last,
        ),
    });

    final response = await _dio.post(
      '/municipality/reports',
      data: formData,
      options: Options(contentType: 'multipart/form-data'),
    );

    final envelope = parseApiData(response.data);
    return MunicipalityReportModel.fromJson(
      envelope['data'] as Map<String, dynamic>,
    );
  }

  Future<List<MunicipalityReportModel>> fetchMyReports() async {
    final response = await _dio.get(
      '/municipality/reports',
      queryParameters: {'mine': 1},
    );

    final envelope = parseApiData(response.data);
    final list = envelope['data'] as List<dynamic>;

    return list
        .map((e) => MunicipalityReportModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}

final municipalityRepositoryProvider = Provider<MunicipalityRepository>(
  (ref) => MunicipalityRepository(ref.watch(dioProvider)),
);
