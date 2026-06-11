import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/storage/token_storage.dart';
import '../../data/auth_repository.dart';
import '../../domain/models/user_model.dart';

final authStateProvider =
    StateNotifierProvider<AuthNotifier, AsyncValue<UserModel?>>(
  (ref) => AuthNotifier(ref),
);

class AuthNotifier extends StateNotifier<AsyncValue<UserModel?>> {
  AuthNotifier(this._ref) : super(const AsyncValue.data(null)) {
    debugPrint('AUTH STATE LOADING (initial)');
  }

  final Ref _ref;
  bool _bootstrapped = false;

  AuthRepository get _repo => _ref.read(authRepositoryProvider);

  Future<void> bootstrap() async {
    if (_bootstrapped) {
      debugPrint('AUTH BOOTSTRAP SKIP (already done)');
      return;
    }
    _bootstrapped = true;

    debugPrint('AUTH BOOTSTRAP START');
    try {
      final token = await _ref.read(tokenStorageProvider).readToken();
      if (token == null || token.isEmpty) {
        state = const AsyncValue.data(null);
        debugPrint('AUTH STATE DATA: user=null (no token)');
        debugPrint('AUTH BOOTSTRAP END');
        return;
      }

      final user = await _repo.restoreSession();
      state = AsyncValue.data(user);
      debugPrint('AUTH STATE DATA: user=${user?.id}');
      debugPrint('AUTH BOOTSTRAP END');
    } catch (e, st) {
      state = AsyncValue.error(e, st);
      debugPrint('AUTH STATE ERROR: $e');
      debugPrint('AUTH BOOTSTRAP END (error)');
    }
  }

  Future<void> login(String identifier, String password) async {
    debugPrint('AUTH STATE LOADING (login)');
    state = const AsyncValue.loading();
    try {
      final user = await _repo.login(identifier, password);
      state = AsyncValue.data(user);
      debugPrint('AUTH STATE DATA: user=${user.id}');
    } on DioException catch (e) {
      final err = e.error;
      state = AsyncValue.error(
        err is ApiException ? err : ApiException(e.message ?? 'Connexion impossible'),
        StackTrace.current,
      );
      debugPrint('AUTH STATE ERROR: $err');
    } catch (e, st) {
      state = AsyncValue.error(e, st);
      debugPrint('AUTH STATE ERROR: $e');
    }
  }

  Future<void> register({
    required String name,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
  }) async {
    debugPrint('AUTH STATE LOADING (register)');
    state = const AsyncValue.loading();
    try {
      final user = await _repo.register(
        name: name,
        email: email,
        phone: phone,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );
      state = AsyncValue.data(user);
      debugPrint('AUTH STATE DATA: user=${user.id}');
    } on DioException catch (e) {
      final err = e.error;
      state = AsyncValue.error(
        err is ApiException ? err : ApiException(e.message ?? 'Inscription impossible'),
        StackTrace.current,
      );
      debugPrint('AUTH STATE ERROR: $err');
    } catch (e, st) {
      state = AsyncValue.error(e, st);
      debugPrint('AUTH STATE ERROR: $e');
    }
  }

  Future<void> logout() async {
    await _repo.logout();
    state = const AsyncValue.data(null);
    debugPrint('AUTH STATE DATA: user=null (logout)');
  }
}
