import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/providers/auth_provider.dart';
import '../../features/auth/presentation/screens/login_screen.dart';
import '../../features/auth/presentation/screens/register_screen.dart';
import '../../features/home/presentation/screens/home_screen.dart';
import '../../features/municipality/presentation/screens/collect_cash_screen.dart';
import '../../features/municipality/presentation/screens/close_cash_session_screen.dart';
import '../../features/municipality/presentation/screens/create_municipality_report_screen.dart';
import '../../features/municipality/presentation/screens/enroll_economic_operator_screen.dart';
import '../../features/municipality/presentation/screens/fiscal_summary_screen.dart';
import '../../features/municipality/presentation/screens/municipality_agent_home_screen.dart';
import '../../features/municipality/presentation/screens/municipality_home_screen.dart';
import '../../features/municipality/presentation/screens/my_collections_screen.dart';
import '../../features/municipality/presentation/screens/my_municipality_reports_screen.dart';
import '../../features/municipality/presentation/screens/open_cash_session_screen.dart';
import '../../features/municipality/data/models/municipal_receipt_model.dart';
import '../../features/municipality/presentation/screens/print_receipt_screen.dart';
import '../../features/municipality/presentation/screens/receipt_history_screen.dart';
import '../../features/municipality/presentation/screens/recovery_hub_screen.dart';
import '../../features/municipality/presentation/screens/scan_operator_screen.dart';
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
          target = user.postAuthRoute;
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
        path: '/municipality/agent',
        builder: (context, state) => const MunicipalityAgentHomeScreen(),
      ),
      GoRoute(
        path: '/municipality/enrollment/new',
        builder: (context, state) => const EnrollEconomicOperatorScreen(),
      ),
      GoRoute(
        path: '/municipality/report/new',
        builder: (context, state) => const CreateMunicipalityReportScreen(),
      ),
      GoRoute(
        path: '/municipality/reports',
        builder: (context, state) => const MyMunicipalityReportsScreen(),
      ),
      GoRoute(
        path: '/municipality/recovery',
        builder: (context, state) => const RecoveryHubScreen(),
      ),
      GoRoute(
        path: '/municipality/recovery/open-session',
        builder: (context, state) => const OpenCashSessionScreen(),
      ),
      GoRoute(
        path: '/municipality/recovery/close-session',
        builder: (context, state) => const CloseCashSessionScreen(),
      ),
      GoRoute(
        path: '/municipality/recovery/scan',
        builder: (context, state) => const ScanOperatorScreen(),
      ),
      GoRoute(
        path: '/municipality/recovery/fiscal-summary',
        builder: (context, state) => const FiscalSummaryScreen(),
      ),
      GoRoute(
        path: '/municipality/recovery/fiscal-summary/:operatorId',
        builder: (context, state) {
          final operatorId = int.parse(state.pathParameters['operatorId']!);
          return FiscalSummaryScreen(operatorId: operatorId);
        },
      ),
      GoRoute(
        path: '/municipality/recovery/collect',
        builder: (context, state) {
          final operatorId = int.tryParse(state.uri.queryParameters['operatorId'] ?? '');
          final balance = state.uri.queryParameters['balance'];
          return CollectCashScreen(
            operatorId: operatorId,
            suggestedAmount: balance,
          );
        },
      ),
      GoRoute(
        path: '/municipality/recovery/my-collections',
        builder: (context, state) => const MyCollectionsScreen(),
      ),
      GoRoute(
        path: '/municipality/recovery/receipts',
        builder: (context, state) => const ReceiptHistoryScreen(),
      ),
      GoRoute(
        path: '/municipality/recovery/print-receipt/:receiptId',
        builder: (context, state) {
          final receiptId = int.parse(state.pathParameters['receiptId']!);
          final initial = state.extra;
          return PrintReceiptScreen(
            receiptId: receiptId,
            initialReceipt: initial is MunicipalReceiptModel ? initial : null,
          );
        },
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
