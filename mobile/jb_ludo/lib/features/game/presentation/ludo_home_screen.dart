import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../data/jb_ludo_repository.dart';

class LudoHomeScreen extends ConsumerStatefulWidget {
  const LudoHomeScreen({super.key});

  @override
  ConsumerState<LudoHomeScreen> createState() => _LudoHomeScreenState();
}

class _LudoHomeScreenState extends ConsumerState<LudoHomeScreen> {
  bool _busy = false;

  Future<void> _quick() async {
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _busy = true);
    messenger.showSnackBar(const SnackBar(content: Text('Recherche d\'adversaire Ludo...')));
    try {
      final result = await ref.read(jbLudoRepositoryProvider).quickMatch(gameType: 'ludo');
      if (!mounted) return;
      if (result['queued'] == true) {
        messenger.showSnackBar(const SnackBar(content: Text('En file d\'attente Ludo')));
        return;
      }
      final match = result['match'] as Map<String, dynamic>?;
      if (match != null) {
        context.push('/match/${match['id']}');
      }
    } catch (e) {
      messenger.showSnackBar(SnackBar(content: Text('$e')));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _solo() async {
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _busy = true);
    messenger.showSnackBar(const SnackBar(content: Text('Preparation de l\'entrainement Ludo...')));
    try {
      final match = await ref.read(jbLudoRepositoryProvider).soloMatch(gameType: 'ludo');
      if (!mounted) return;
      context.push('/match/${match['id']}');
    } catch (e) {
      messenger.showSnackBar(SnackBar(content: Text('$e')));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Ludo')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Card(
            clipBehavior: Clip.antiAlias,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                AspectRatio(
                  aspectRatio: 16 / 9,
                  child: Image.asset('assets/games/ludo.png', fit: BoxFit.cover),
                ),
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Ludo 4 joueurs', style: Theme.of(context).textTheme.titleLarge),
                      const SizedBox(height: 6),
                      const Text('Rouge, Bleu, Vert et Jaune avec de serveur et progression separee du Damier.'),
                      const SizedBox(height: 16),
                      FilledButton.icon(
                        onPressed: _busy ? null : _solo,
                        icon: const Icon(Icons.psychology_outlined),
                        label: const Text('Jouer seul contre IA'),
                      ),
                      const SizedBox(height: 10),
                      OutlinedButton.icon(
                        onPressed: _busy ? null : _quick,
                        icon: const Icon(Icons.casino_outlined),
                        label: const Text('Partie rapide Ludo'),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
