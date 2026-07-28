import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/storage/token_storage.dart';
import '../domain/user_model.dart';

class AuthRepository {
  AuthRepository(this._dio, this._tokens);

  final Dio _dio;
  final TokenStorage _tokens;

  Future<UserModel?> bootstrap() async {
    final token = await _tokens.readToken();
    if (token == null || token.isEmpty) return null;
    try {
      final response = await _dio.get('/me');
      final envelope = parseApiData(response.data);
      final data = envelope['data'] ?? envelope;
      if (data is Map<String, dynamic>) {
        if (data['user'] is Map<String, dynamic>) {
          return UserModel.fromJson(data['user'] as Map<String, dynamic>);
        }
        return UserModel.fromJson(data);
      }
      return null;
    } catch (_) {
      await _tokens.clear();
      return null;
    }
  }

  Future<UserModel> login(String email, String password) async {
    final response = await _dio.post('/login', data: {
      'email': email,
      'password': password,
    });
    final envelope = parseApiData(response.data);
    final data = envelope['data'] as Map<String, dynamic>? ?? envelope;
    final token = data['token'] as String?;
    if (token == null) throw ApiException('Token manquant');
    await _tokens.saveToken(token);
    return UserModel.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<UserModel> register({
    required String name,
    required String email,
    required String password,
    String? phone,
  }) async {
    final response = await _dio.post('/register', data: {
      'name': name,
      'email': email,
      'password': password,
      'password_confirmation': password,
      if (phone != null) 'phone': phone,
    });
    final envelope = parseApiData(response.data);
    final data = envelope['data'] as Map<String, dynamic>? ?? envelope;
    final token = data['token'] as String?;
    if (token == null) throw ApiException('Token manquant');
    await _tokens.saveToken(token);
    return UserModel.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<void> logout() async {
    try {
      await _dio.post('/logout');
    } catch (_) {}
    await _tokens.clear();
  }
}

final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepository(ref.watch(dioProvider), ref.watch(tokenStorageProvider)),
);
