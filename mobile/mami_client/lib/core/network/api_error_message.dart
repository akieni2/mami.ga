import 'package:dio/dio.dart';

import 'api_exception.dart';

/// Extrait un message utilisateur lisible depuis une erreur API Dio/Laravel.
String resolveApiErrorMessage(Object error) {
  if (error is DioException) {
    if (_isNetworkFailure(error)) {
      return 'Connexion réseau indisponible';
    }

    final nested = error.error;
    if (nested is ApiException) {
      return _firstValidationMessage(error.response?.data) ?? nested.message;
    }

    final fromBody = _firstValidationMessage(error.response?.data);
    if (fromBody != null) {
      return fromBody;
    }

    if (error.response?.data is Map) {
      final message = (error.response!.data as Map)['message'];
      if (message is String && message.isNotEmpty) {
        return message;
      }
    }
  }

  if (error is ApiException) {
    return error.message;
  }

  return error.toString();
}

String? _firstValidationMessage(dynamic responseData) {
  if (responseData is! Map) {
    return null;
  }

  final errors = responseData['errors'];
  if (errors is Map) {
    for (final value in errors.values) {
      if (value is List && value.isNotEmpty) {
        return value.first.toString();
      }
      if (value is String && value.isNotEmpty) {
        return value;
      }
    }
  }

  return null;
}

bool _isNetworkFailure(DioException error) {
  return error.type == DioExceptionType.connectionError ||
      error.type == DioExceptionType.connectionTimeout ||
      error.type == DioExceptionType.receiveTimeout ||
      error.type == DioExceptionType.sendTimeout;
}
