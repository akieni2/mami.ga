import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../auth/presentation/auth_provider.dart';
import '../data/jb_ludo_repository.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
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

  Future<void> _quick() async {
    final messenger = ScaffoldMessenger.of(context);
    messenger.showSnackBar(const SnackBar(content: Text('Recherche d\'adversaire…')));
    try {
      final result = await ref.read(jbLudoRepositoryProvider).quickMatch();
      if (!mounted) return;
      if (result['queued'] == true) {
        messenger.showSnackBar(const SnackBar(content: Text('En file d\'attente — réessayez dans un instant')));
        return;
      }
      final match = result['match'] as Map<String, dynamic>?;
      if (match != null) {
        context.push('/match/${match['id']}');
      }
    } catch (e) {
      messenger.showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('JB Ludo'),
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
          Text('Bonjour, ${_profile?['pseudo'] ?? ''}', style: Theme.of(context).textTheme.titleLarge),
          Text('${_profile?['points'] ?? 0} points · ${_profile?['city'] ?? ''}'),
          const SizedBox(height: 24),
          _tile(Icons.flash_on, 'Partie rapide', 'Adversaire de niveau proche', _quick),
          _tile(Icons.people_outline, 'Partie amicale', 'Inviter un joueur', () => context.push('/invite')),
          _tile(Icons.emoji_events_outlined, 'Classement', 'Classement général', () => context.push('/leaderboard')),
          _tile(Icons.history, 'Historique', 'Vos parties', () => context.push('/history')),
        ],
      ),
    );
  }

  Widget _tile(IconData icon, String title, String subtitle, VoidCallback onTap) {
    return Card(
      child: ListTile(
        leading: Icon(icon),
        title: Text(title),
        subtitle: Text(subtitle),
        trailing: const Icon(Icons.chevron_right),
        onTap: onTap,
      ),
    );
  }
}
