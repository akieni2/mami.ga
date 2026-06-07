import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/providers/auth_provider.dart';
import '../../features/auth/presentation/screens/login_screen.dart';
import '../../features/auth/presentation/screens/register_screen.dart';
import '../../features/home/presentation/screens/home_screen.dart';
import '../../features/profile/presentation/screens/profile_screen.dart';
import '../../features/rides/presentation/screens/active_ride_screen.dart';
import '../../features/rides/presentation/screens/ride_booking_screen.dart';
import '../../features/rides/presentation/screens/ride_history_screen.dart';
import '../../features/rides/presentation/screens/ride_searching_screen.dart';
import '../../features/shell/presentation/screens/main_shell.dart';
import '../../features/splash/presentation/screens/splash_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final auth = ref.watch(authStateProvider);

  return GoRouter(
    initialLocation: '/splash',
    redirect: (context, state) {
      final path = state.matchedLocation;
      final onSplash = path == '/splash';
      final onAuth = path == '/login' || path == '/register';

      if (auth.isLoading && !onSplash) return '/splash';

      final user = auth.valueOrNull;

      if (user == null && !onAuth && !onSplash) return '/login';
      if (user != null && onAuth) return '/';
      return null;
    },
    routes: [
      GoRoute(
        path: '/splash',
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/register',
        builder: (context, state) => const RegisterScreen(),
      ),
      ShellRoute(
        builder: (context, state, child) => MainShell(child: child),
        routes: [
          GoRoute(
            path: '/',
            builder: (context, state) => const HomeScreen(),
          ),
          GoRoute(
            path: '/history',
            builder: (context, state) => const RideHistoryScreen(),
          ),
          GoRoute(
            path: '/profile',
            builder: (context, state) => const ProfileScreen(),
          ),
        ],
      ),
      GoRoute(
        path: '/book',
        builder: (context, state) => const RideBookingScreen(),
      ),
      GoRoute(
        path: '/ride/searching/:id',
        builder: (context, state) {
          final id = int.parse(state.pathParameters['id']!);
          return RideSearchingScreen(rideId: id);
        },
      ),
      GoRoute(
        path: '/ride/active/:id',
        builder: (context, state) {
          final id = int.parse(state.pathParameters['id']!);
          return ActiveRideScreen(rideId: id);
        },
      ),
    ],
  );
});
