import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_exception.dart';
import '../../data/auth_repository.dart';
import '../../domain/models/user_model.dart';

final authStateProvider =
    StateNotifierProvider<AuthNotifier, AsyncValue<UserModel?>>(
  (ref) => AuthNotifier(ref),
);

class AuthNotifier extends StateNotifier<AsyncValue<UserModel?>> {
  AuthNotifier(this._ref) : super(const AsyncValue.loading());

  final Ref _ref;

  AuthRepository get _repo => _ref.read(authRepositoryProvider);

  Future<void> bootstrap() async {
    try {
      final user = await _repo.restoreSession();
      state = AsyncValue.data(user);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }

  Future<void> login(String identifier, String password) async {
    state = const AsyncValue.loading();
    try {
      final user = await _repo.login(identifier, password);
      state = AsyncValue.data(user);
    } on DioException catch (e) {
      final err = e.error;
      state = AsyncValue.error(
        err is ApiException ? err : ApiException(e.message ?? 'Connexion impossible'),
        StackTrace.current,
      );
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }

  Future<void> register({
    required String name,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
  }) async {
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
    } on DioException catch (e) {
      final err = e.error;
      state = AsyncValue.error(
        err is ApiException ? err : ApiException(e.message ?? 'Inscription impossible'),
        StackTrace.current,
      );
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }

  Future<void> logout() async {
    await _repo.logout();
    state = const AsyncValue.data(null);
  }
}
