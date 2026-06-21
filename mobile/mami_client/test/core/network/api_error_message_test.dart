import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mami_client/core/network/api_error_message.dart';
import 'package:mami_client/core/network/api_exception.dart';

void main() {
  test('resolveApiErrorMessage returns first Laravel validation error', () {
    final error = DioException(
      requestOptions: RequestOptions(path: '/collections'),
      response: Response(
        requestOptions: RequestOptions(path: '/collections'),
        statusCode: 422,
        data: {
          'message': 'The given data was invalid.',
          'errors': {
            'operator_id': ['Aucune taxe n\'est affectée à ce commerce.'],
          },
        },
      ),
      error: ApiException('The given data was invalid.', statusCode: 422),
    );

    expect(
      resolveApiErrorMessage(error),
      'Aucune taxe n\'est affectée à ce commerce.',
    );
  });

  test('resolveApiErrorMessage returns nested ApiException message', () {
    final error = DioException(
      requestOptions: RequestOptions(path: '/collections'),
      error: ApiException('Commerce inactif — encaissement refusé.', statusCode: 422),
    );

    expect(
      resolveApiErrorMessage(error),
      'Commerce inactif — encaissement refusé.',
    );
  });
}
