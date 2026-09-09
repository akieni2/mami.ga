import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../auth/presentation/auth_provider.dart';
import '../data/jb_ludo_repository.dart';

class GameSelectionScreen extends ConsumerStatefulWidget {
  const GameSelectionScreen({super.key});

  @override
  ConsumerState<GameSelectionScreen> createState() => _GameSelectionScreenState();
}

class _GameSelectionScreenState extends ConsumerState<GameSelectionScreen> {
  Map<String, dynamic>? _profile;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final profile = await ref.read(jbLudoRepositoryProvider).fetchMyProfile();
      if (!mounted) return;
      if (profile == null) {
        context.go('/profile/setup');
        return;
      }
      setState(() {
        _profile = profile;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('JB Games'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () async {
              await ref.read(authStateProvider.notifier).logout();
              if (context.mounted) context.go('/login');
            },
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text('Bonjour, ${_profile?['pseudo'] ?? ''}', style: theme.textTheme.titleLarge),
          Text('${_profile?['points'] ?? 0} points · ${_profile?['city'] ?? ''}'),
          const SizedBox(height: 24),
          Text('Choisissez votre jeu', style: theme.textTheme.titleMedium),
          const SizedBox(height: 12),
          _gameCard(
            image: 'assets/games/damier.jpg',
            title: 'Damier',
            subtitle: 'Parties rapides, invitations, classement et championnats',
            action: 'Jouer',
            onTap: () => context.push('/damier'),
          ),
          const SizedBox(height: 16),
          _gameCard(
            image: 'assets/games/ludo.png',
            title: 'Ludo',
            subtitle: 'Jeu distinct avec ses propres parties et competitions',
            action: 'Jouer',
            onTap: () => context.push('/ludo'),
          ),
        ],
      ),
    );
  }

  Widget _gameCard({
    required String image,
    required String title,
    required String subtitle,
    required String action,
    required VoidCallback onTap,
  }) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            AspectRatio(
              aspectRatio: 16 / 9,
              child: Image.asset(image, fit: BoxFit.cover),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(title, style: Theme.of(context).textTheme.titleLarge),
                        const SizedBox(height: 4),
                        Text(subtitle),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  FilledButton(
                    onPressed: onTap,
                    child: Text(action),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
