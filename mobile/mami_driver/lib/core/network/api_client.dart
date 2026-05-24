import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../config/app_config.dart';
import '../storage/token_storage.dart';
import 'api_exception.dart';

final dioProvider = Provider<Dio>((ref) {
  final dio = Dio(
    BaseOptions(
      baseUrl: AppConfig.apiBaseUrl,
      connectTimeout: const Duration(seconds: 20),
      receiveTimeout: const Duration(seconds: 20),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ),
  );

  dio.interceptors.add(
    InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await ref.read(tokenStorageProvider).readToken();
        if (token != null && token.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (error, handler) {
        final data = error.response?.data;
        String message = error.message ?? 'Network error';

        if (data is Map && data['message'] is String) {
          message = data['message'] as String;
        }

        handler.reject(
          DioException(
            requestOptions: error.requestOptions,
            response: error.response,
            type: error.type,
            error: ApiException(message, statusCode: error.response?.statusCode),
          ),
        );
      },
    ),
  );

  return dio;
});

Map<String, dynamic> parseApiData(dynamic responseData) {
  if (responseData is! Map<String, dynamic>) {
    throw ApiException('Invalid API response');
  }

  if (responseData['success'] != true) {
    throw ApiException(
      responseData['message']?.toString() ?? 'Request failed',
    );
  }

  return responseData;
}

T extractData<T>(dynamic responseData, T Function(dynamic json) mapper) {
  final envelope = parseApiData(responseData);
  return mapper(envelope['data']);
}
