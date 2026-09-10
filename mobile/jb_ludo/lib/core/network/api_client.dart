import 'dart:io';

import 'package:dio/dio.dart';
import 'package:dio/io.dart';
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

  _attachIpAwareHttpAdapter(dio);

  dio.interceptors.add(
    InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await ref.read(tokenStorageProvider).readToken();
        if (token != null && token.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        _applyVirtualHostIfNeeded(options);
        handler.next(options);
      },
      onError: (error, handler) async {
        if (error.response == null &&
            error.requestOptions.extra['skipNetworkFallback'] != true) {
          final fallbackAttempt = await _retryWithFallbackHosts(error);
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

void _attachIpAwareHttpAdapter(Dio dio) {
  dio.httpClientAdapter = IOHttpClientAdapter(
    createHttpClient: () {
      final client = HttpClient();
      // Connexion HTTPS via IP : le certificat est émis pour *.mami.ga, pas pour l'IP.
      client.badCertificateCallback = (cert, host, port) {
        if (host == AppConfig.apiDirectIp) {
          final subject = cert.subject.toLowerCase();
          return subject.contains('mami.ga');
        }
        return false;
      };
      return client;
    },
  );
}

void _applyVirtualHostIfNeeded(RequestOptions options) {
  final uriHost = options.uri.host;
  final base = options.baseUrl;
  if (uriHost == AppConfig.apiDirectIp ||
      AppConfig.isDirectIpBaseUrl(base) ||
      options.extra['forceVirtualHost'] == true) {
    options.headers['Host'] = AppConfig.apiVirtualHost;
  }
}

/// Sonde publique : `/api/app/features` puis `/up` sur chaque hôte (+ IP).
Future<String> probeApiConnectivity() async {
  final bases = <String>{
    AppConfig.apiBaseUrl,
    ...AppConfig.apiFallbackBaseUrls,
  };

  final client = Dio(
    BaseOptions(
      connectTimeout: const Duration(seconds: 12),
      receiveTimeout: const Duration(seconds: 12),
      headers: {
        'Accept': 'application/json',
        'User-Agent': 'JB-Games/1.0 Android',
      },
      validateStatus: (status) => status != null && status < 500,
    ),
  );
  _attachIpAwareHttpAdapter(client);

  final failures = <String>[];

  for (final base in bases) {
    try {
      final response = await client.get<dynamic>(
        _absoluteUrl(base, '/app/features'),
        options: Options(
          headers: {
            if (AppConfig.isDirectIpBaseUrl(base))
              'Host': AppConfig.apiVirtualHost,
          },
          extra: {
            if (AppConfig.isDirectIpBaseUrl(base)) 'forceVirtualHost': true,
          },
        ),
      );
      if (response.statusCode != null && response.statusCode! < 500) {
        return 'OK via $base (HTTP ${response.statusCode})';
      }
    } on DioException catch (e) {
      failures.add('$base/app/features → ${_shortNetworkCause(e)}');
    }

    try {
      final origin = AppConfig.originFrom(base);
      final up = await client.get<dynamic>(
        '$origin/up',
        options: Options(
          headers: {
            if (AppConfig.isDirectIpBaseUrl(base))
              'Host': AppConfig.apiVirtualHost,
          },
          extra: {
            if (AppConfig.isDirectIpBaseUrl(base)) 'forceVirtualHost': true,
          },
        ),
      );
      if (up.statusCode != null && up.statusCode! < 500) {
        return 'OK via $origin/up (HTTP ${up.statusCode})';
      }
    } on DioException catch (e) {
      failures.add('${AppConfig.originFrom(base)}/up → ${_shortNetworkCause(e)}');
    }
  }

  throw ApiException(
    'Aucun hôte joignable.\n${failures.take(5).join('\n')}',
  );
}

Future<_FallbackAttempt> _retryWithFallbackHosts(
  DioException originalError,
) async {
  final failedBaseUrl = _normalizeBaseUrl(originalError.requestOptions.baseUrl);
  DioException? lastNetworkError = originalError;

  final fallbackDio = Dio(
    BaseOptions(
      connectTimeout: const Duration(seconds: 20),
      receiveTimeout: const Duration(seconds: 20),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'User-Agent': 'JB-Games/1.0 Android',
      },
    ),
  );
  _attachIpAwareHttpAdapter(fallbackDio);

  for (final baseUrl in AppConfig.apiFallbackBaseUrls) {
    if (_normalizeBaseUrl(baseUrl) == failedBaseUrl) continue;

    final headers =
        Map<String, dynamic>.from(originalError.requestOptions.headers)
          ..removeWhere((key, _) {
            final normalizedKey = key.toLowerCase();
            return normalizedKey == 'host' || normalizedKey == 'content-length';
          });

    if (AppConfig.isDirectIpBaseUrl(baseUrl)) {
      headers['Host'] = AppConfig.apiVirtualHost;
    }

    try {
      final response = await fallbackDio.request<dynamic>(
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
            if (AppConfig.isDirectIpBaseUrl(baseUrl)) 'forceVirtualHost': true,
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
        'Impossible de joindre ${error.requestOptions.uri}.\n${_shortNetworkCause(error)}\nAstuce : installez JB Games 1.0.6+ (contournement DNS) ou désactivez le DNS privé.';
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

String _shortNetworkCause(DioException error) {
  final underlying = error.error;
  if (underlying is SocketException) {
    final os = underlying.osError;
    if (underlying.message.contains('Failed host lookup') ||
        (os?.message.toLowerCase().contains('name or service') ?? false)) {
      return 'DNS : domaine introuvable (Failed host lookup).';
    }
    if (os?.errorCode == 111 ||
        underlying.message.toLowerCase().contains('connection refused')) {
      return 'Connexion refusée par le serveur.';
    }
    if (os?.errorCode == 110 ||
        underlying.message.toLowerCase().contains('timed out')) {
      return 'Délai dépassé (réseau lent ou filtré).';
    }
    return 'Socket : ${underlying.message}';
  }
  if (underlying is HandshakeException || underlying is TlsException) {
    return 'TLS/certificat refusé par Android.';
  }
  if (underlying is CertificateException) {
    return 'Certificat SSL non reconnu sur cet appareil.';
  }
  return switch (error.type) {
    DioExceptionType.connectionTimeout => 'Timeout connexion',
    DioExceptionType.sendTimeout => 'Timeout envoi',
    DioExceptionType.receiveTimeout => 'Timeout réception',
    DioExceptionType.connectionError =>
      'Erreur connexion (${underlying ?? error.message})',
    _ => underlying?.toString() ?? error.message ?? error.type.name,
  };
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
