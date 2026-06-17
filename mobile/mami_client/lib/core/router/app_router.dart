import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/providers/auth_provider.dart';
import '../../features/auth/presentation/screens/login_screen.dart';
import '../../features/auth/presentation/screens/register_screen.dart';
import '../../features/home/presentation/screens/home_screen.dart';
import '../../features/municipality/presentation/screens/create_municipality_report_screen.dart';
import '../../features/municipality/presentation/screens/municipality_home_screen.dart';
import '../../features/municipality/presentation/screens/my_municipality_reports_screen.dart';
import '../../features/profile/presentation/screens/profile_screen.dart';
import '../../features/rides/presentation/screens/active_ride_screen.dart';
import '../../features/rides/presentation/screens/ride_booking_gate.dart';
import '../../features/rides/presentation/screens/ride_history_screen.dart';
import '../../features/rides/presentation/screens/ride_searching_screen.dart';
import '../../features/shell/presentation/screens/main_shell.dart';
import '../../features/splash/presentation/screens/splash_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  debugPrint('ROUTER CREATED');

  final router = GoRouter(
    initialLocation: '/splash',
    redirect: (context, state) {
      final auth = ref.read(authStateProvider);
      final path = state.matchedLocation;
      final onSplash = path == '/splash';
      final onAuth = path == '/login' || path == '/register';

      String? target;
      if (auth.isLoading) {
        target = null;
      } else {
        final user = auth.valueOrNull;

        if (user == null && !onAuth && !onSplash) {
          target = '/login';
        } else if (user != null && (onAuth || onSplash)) {
          target = '/';
        }
      }

      if (auth.isLoading) {
        debugPrint(
          'ROUTER REDIRECT: path=$path -> ${target ?? 'null'} (auth loading)',
        );
      } else if (auth.hasError) {
        debugPrint(
          'ROUTER REDIRECT: path=$path -> ${target ?? 'null'} (auth error: ${auth.error})',
        );
      } else {
        debugPrint(
          'ROUTER REDIRECT: path=$path -> ${target ?? 'null'} (user=${auth.valueOrNull?.id})',
        );
      }

      return target;
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
        builder: (context, state) => const RideBookingGate(),
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
      GoRoute(
        path: '/municipality',
        builder: (context, state) => const MunicipalityHomeScreen(),
      ),
      GoRoute(
        path: '/municipality/report/new',
        builder: (context, state) => const CreateMunicipalityReportScreen(),
      ),
      GoRoute(
        path: '/municipality/reports',
        builder: (context, state) => const MyMunicipalityReportsScreen(),
      ),
    ],
  );

  ref.listen(authStateProvider, (previous, next) {
    debugPrint(
      'ROUTER REFRESH (auth changed): user=${next.valueOrNull?.id}',
    );
    router.refresh();
  });

  ref.onDispose(router.dispose);

  return router;
});
