import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

const _tokenKey = 'mami_client_token';

class TokenStorage {
  TokenStorage(this._storage);

  final FlutterSecureStorage _storage;

  Future<void> saveToken(String token) =>
      _storage.write(key: _tokenKey, value: token);

  Future<String?> readToken() async {
    debugPrint('TOKEN READ START');
    try {
      final token = await _storage.read(key: _tokenKey);
      debugPrint('TOKEN READ SUCCESS');
      return token;
    } catch (e) {
      debugPrint('TOKEN READ FAILED: $e');
      try {
        await _storage.delete(key: _tokenKey);
        debugPrint('TOKEN CLEARED');
      } catch (_) {
        // Ignore delete failures; caller treats missing token as logged out.
      }
      return null;
    }
  }

  Future<void> clear() => _storage.delete(key: _tokenKey);
}

final tokenStorageProvider = Provider<TokenStorage>(
  (ref) => TokenStorage(const FlutterSecureStorage()),
);
