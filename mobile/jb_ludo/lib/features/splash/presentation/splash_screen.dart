import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../auth/presentation/auth_provider.dart';

class SplashScreen extends ConsumerWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authStateProvider);

    ref.listen(authStateProvider, (previous, next) {
      if (next.isLoading) return;
      final user = next.valueOrNull;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!context.mounted) return;
        context.go(user == null ? '/login' : '/home');
      });
    });

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: auth.when(
              loading: () => const Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Image(
                    image: AssetImage('assets/games/ludo.png'),
                    width: 112,
                    height: 112,
                  ),
                  SizedBox(height: 18),
                  CircularProgressIndicator(),
                  SizedBox(height: 18),
                  Text('Chargement JB Games...'),
                ],
              ),
              error: (error, stackTrace) => Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.wifi_off_rounded, size: 42),
                  const SizedBox(height: 14),
                  const Text(
                    'Connexion impossible',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    error.toString(),
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: Colors.black54),
                  ),
                  const SizedBox(height: 18),
                  FilledButton(
                    onPressed: () => context.go('/login'),
                    child: const Text('Aller a la connexion'),
                  ),
                ],
              ),
              data: (_) => const Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Image(
                    image: AssetImage('assets/games/ludo.png'),
                    width: 112,
                    height: 112,
                  ),
                  SizedBox(height: 18),
                  CircularProgressIndicator(),
                  SizedBox(height: 18),
                  Text('Ouverture...'),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
