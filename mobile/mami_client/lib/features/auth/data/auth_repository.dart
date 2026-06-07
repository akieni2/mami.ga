import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/storage/token_storage.dart';
import '../domain/models/user_model.dart';

class AuthRepository {
  AuthRepository(this._dio, this._tokenStorage);

  final Dio _dio;
  final TokenStorage _tokenStorage;

  Future<UserModel> login(String identifier, String password) async {
    final email = _resolveLoginEmail(identifier);

    final response = await _dio.post('/login', data: {
      'email': email,
      'password': password,
    });

    final data =
        extractData<Map<String, dynamic>>(response.data, (d) => d as Map<String, dynamic>);
    final user = UserModel.fromJson(data['user'] as Map<String, dynamic>);

    if (user.isDriver) {
      throw ApiException('Utilisez l\'application chauffeur pour ce compte.');
    }

    final token = data['token'] as String;
    await _tokenStorage.saveToken(token);

    return user;
  }

  Future<UserModel> register({
    required String name,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
  }) async {
    final response = await _dio.post('/register', data: {
      'name': name,
      'email': email,
      'phone': phone,
      'password': password,
      'password_confirmation': passwordConfirmation,
    });

    final data =
        extractData<Map<String, dynamic>>(response.data, (d) => d as Map<String, dynamic>);
    final user = UserModel.fromJson(data['user'] as Map<String, dynamic>);
    final token = data['token'] as String;
    await _tokenStorage.saveToken(token);

    return user;
  }

  Future<UserModel?> restoreSession() async {
    final token = await _tokenStorage.readToken();
    if (token == null || token.isEmpty) return null;

    final response = await _dio.get('/me');
    final data =
        extractData<Map<String, dynamic>>(response.data, (d) => d as Map<String, dynamic>);
    final user = UserModel.fromJson(data['user'] as Map<String, dynamic>);

    if (user.isDriver) {
      await _tokenStorage.clear();
      return null;
    }

    return user;
  }

  Future<void> logout() async {
    try {
      await _dio.post('/logout');
    } finally {
      await _tokenStorage.clear();
    }
  }

  String _resolveLoginEmail(String identifier) {
    final value = identifier.trim();
    if (value.contains('@')) return value;
    throw ApiException(
      'Connexion par téléphone bientôt disponible. Utilisez votre email.',
    );
  }
}

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(ref.watch(dioProvider), ref.watch(tokenStorageProvider));
});
