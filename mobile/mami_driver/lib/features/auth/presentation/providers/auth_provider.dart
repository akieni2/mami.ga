import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_exception.dart';
import '../../data/auth_repository.dart';
import '../../../driver/domain/models/driver_model.dart';
import '../../domain/models/user_model.dart';

final authStateProvider = StateNotifierProvider<AuthNotifier, AsyncValue<UserModel?>>(
  (ref) => AuthNotifier(ref),
);

class AuthNotifier extends StateNotifier<AsyncValue<UserModel?>> {
  AuthNotifier(this._ref) : super(const AsyncValue.loading()) {
    _bootstrap();
  }

  final Ref _ref;

  AuthRepository get _repo => _ref.read(authRepositoryProvider);

  Future<void> _bootstrap() async {
    try {
      final user = await _repo.restoreSession();
      state = AsyncValue.data(user);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }

  Future<void> login(String email, String password) async {
    state = const AsyncValue.loading();
    try {
      final user = await _repo.login(email, password);
      final refreshed = await _repo.restoreSession();
      state = AsyncValue.data(refreshed ?? user);
    } on DioException catch (e) {
      final err = e.error;
      state = AsyncValue.error(
        err is ApiException ? err : ApiException(e.message ?? 'Login failed'),
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

  void updateDriver(DriverModel driver) {
    final user = state.valueOrNull;
    if (user == null) return;
    state = AsyncValue.data(user.copyWith(driver: driver));
  }
}
