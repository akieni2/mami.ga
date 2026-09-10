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
        'User-Agent': 'JB-Games/1.0 Android',
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
      onError: (error, handler) async {
        if (error.response == null &&
            error.requestOptions.extra['skipNetworkFallback'] != true) {
          final fallbackAttempt = await _retryWithFallbackHosts(dio, error);
          if (fallbackAttempt.response != null) {
            handler.resolve(fallbackAttempt.response!);
            return;
          }
          error = fallbackAttempt.error ?? error;
        }
        _rejectWithApiException(error, handler);
      },
    ),
  );

  return dio;
});

Future<_FallbackAttempt> _retryWithFallbackHosts(
  Dio dio,
  DioException originalError,
) async {
  final failedBaseUrl = _normalizeBaseUrl(originalError.requestOptions.baseUrl);
  DioException? lastNetworkError = originalError;

  for (final baseUrl in AppConfig.apiFallbackBaseUrls) {
    if (_normalizeBaseUrl(baseUrl) == failedBaseUrl) continue;

    final headers =
        Map<String, dynamic>.from(originalError.requestOptions.headers)
          ..removeWhere((key, _) {
            final normalizedKey = key.toLowerCase();
            return normalizedKey == 'host' || normalizedKey == 'content-length';
          });

    try {
      final response = await dio.request<dynamic>(
        _absoluteUrl(baseUrl, originalError.requestOptions.path),
        data: originalError.requestOptions.data,
        queryParameters: originalError.requestOptions.queryParameters,
        options: Options(
          method: originalError.requestOptions.method,
          headers: headers,
          contentType: originalError.requestOptions.contentType,
          responseType: originalError.requestOptions.responseType,
          followRedirects: originalError.requestOptions.followRedirects,
          receiveDataWhenStatusError:
              originalError.requestOptions.receiveDataWhenStatusError,
          validateStatus: originalError.requestOptions.validateStatus,
          extra: {
            ...originalError.requestOptions.extra,
            'skipNetworkFallback': true,
          },
        ),
        cancelToken: originalError.requestOptions.cancelToken,
        onSendProgress: originalError.requestOptions.onSendProgress,
        onReceiveProgress: originalError.requestOptions.onReceiveProgress,
      );
      return _FallbackAttempt(response: response);
    } on DioException catch (retryError) {
      if (retryError.response != null) {
        return _FallbackAttempt(error: retryError);
      }
      lastNetworkError = retryError;
    }
  }

  return _FallbackAttempt(error: lastNetworkError);
}

class _FallbackAttempt {
  _FallbackAttempt({this.response, this.error});

  final Response<dynamic>? response;
  final DioException? error;
}

void _rejectWithApiException(
  DioException error,
  ErrorInterceptorHandler handler,
) {
  final data = error.response?.data;
  var message = error.message ?? 'Erreur réseau';
  if (error.response == null) {
    message =
        'Impossible de joindre ${error.requestOptions.uri}. Vérifiez Internet, DNS, VPN/DNS privé ou réseau mobile.';
  }
  if (data is Map && data['message'] is String) {
    message = data['message'] as String;
  }
  if (data is Map && data['errors'] is Map) {
    final errors = data['errors'] as Map;
    if (errors.isNotEmpty) {
      final first = errors.values.first;
      if (first is List && first.isNotEmpty) {
        message = first.first.toString();
      }
    }
  }
  handler.reject(
    DioException(
      requestOptions: error.requestOptions,
      response: error.response,
      type: error.type,
      error: ApiException(message, statusCode: error.response?.statusCode),
    ),
  );
}

String _absoluteUrl(String baseUrl, String path) {
  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path;
  }

  final normalizedBaseUrl = baseUrl.replaceFirst(RegExp(r'/+$'), '');
  final normalizedPath = path.replaceFirst(RegExp(r'^/+'), '');
  return '$normalizedBaseUrl/$normalizedPath';
}

String _normalizeBaseUrl(String url) => url.replaceFirst(RegExp(r'/+$'), '');
