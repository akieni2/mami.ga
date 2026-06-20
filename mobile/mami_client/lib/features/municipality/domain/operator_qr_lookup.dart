import 'package:dio/dio.dart';

import '../../../core/network/api_exception.dart';
import 'qr_scan_token_parser.dart';

class OperatorQrLookupException implements Exception {
  OperatorQrLookupException(this.message);

  final String message;

  @override
  String toString() => message;
}

/// Résout un commerce à partir d'un contenu QR brut ou saisi manuellement.
Future<int> lookupOperatorIdByQr({
  required Future<Map<String, dynamic>> Function(String token) lookup,
  required String rawPayload,
}) async {
  final token = parseQrScanToken(rawPayload);
  if (token == null) {
    throw OperatorQrLookupException('QR non reconnu');
  }

  try {
    final data = await lookup(token);
    final operator = data['operator'] as Map<String, dynamic>? ?? data;
    final operatorId = operator['id'] as int?;

    if (operatorId == null) {
      throw OperatorQrLookupException('Commerce introuvable');
    }

    return operatorId;
  } on DioException catch (e) {
    throw OperatorQrLookupException(_mapDioError(e));
  } on ApiException catch (e) {
    throw OperatorQrLookupException(_mapApiError(e));
  }
}

String _mapDioError(DioException error) {
  if (_isNetworkFailure(error)) {
    return 'Connexion réseau indisponible';
  }

  final nested = error.error;
  if (nested is ApiException) {
    return _mapApiError(nested);
  }

  final status = error.response?.statusCode;
  if (status == 404) {
    return 'Commerce introuvable';
  }

  return 'Commerce introuvable';
}

String _mapApiError(ApiException error) {
  if (error.statusCode == 404) {
    return 'Commerce introuvable';
  }
  return error.message;
}

bool _isNetworkFailure(DioException error) {
  return error.type == DioExceptionType.connectionError ||
      error.type == DioExceptionType.connectionTimeout ||
      error.type == DioExceptionType.receiveTimeout ||
      error.type == DioExceptionType.sendTimeout;
}
