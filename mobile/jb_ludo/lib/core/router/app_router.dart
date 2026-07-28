import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/auth_provider.dart';
import '../../features/auth/presentation/login_screen.dart';
import '../../features/auth/presentation/register_screen.dart';
import '../../features/game/presentation/history_screen.dart';
import '../../features/game/presentation/home_screen.dart';
import '../../features/game/presentation/invite_screen.dart';
import '../../features/game/presentation/leaderboard_screen.dart';
import '../../features/game/presentation/match_screen.dart';
import '../../features/game/presentation/profile_setup_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final router = GoRouter(
    initialLocation: '/splash',
    redirect: (context, state) {
      final auth = ref.read(authStateProvider);
      final path = state.matchedLocation;
      if (auth.isLoading) return path == '/splash' ? null : '/splash';
      final user = auth.valueOrNull;
      final onAuth = path == '/login' || path == '/register';
      if (user == null && !onAuth && path != '/splash') return '/login';
      if (user != null && (onAuth || path == '/splash')) return '/home';
      return null;
    },
    routes: [
      GoRoute(
        path: '/splash',
        builder: (context, state) => const Scaffold(body: Center(child: CircularProgressIndicator())),
      ),
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      GoRoute(path: '/register', builder: (context, state) => const RegisterScreen()),
      GoRoute(path: '/profile/setup', builder: (context, state) => const ProfileSetupScreen()),
      GoRoute(path: '/home', builder: (context, state) => const HomeScreen()),
      GoRoute(path: '/invite', builder: (context, state) => const InviteScreen()),
      GoRoute(path: '/leaderboard', builder: (context, state) => const LeaderboardScreen()),
      GoRoute(path: '/history', builder: (context, state) => const HistoryScreen()),
      GoRoute(
        path: '/match/:id',
        builder: (context, state) {
          final id = int.parse(state.pathParameters['id']!);
          return MatchScreen(matchId: id);
        },
      ),
    ],
  );

  ref.listen(authStateProvider, (_, __) => router.refresh());
  ref.onDispose(router.dispose);
  return router;
});
