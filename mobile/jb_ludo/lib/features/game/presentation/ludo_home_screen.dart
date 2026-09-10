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
  int _humanColors = 1;

  Future<void> _quick() async {
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _busy = true);
    messenger.showSnackBar(
        const SnackBar(content: Text('Recherche d\'adversaire Ludo...')));
    try {
      final result =
          await ref.read(jbLudoRepositoryProvider).quickMatch(gameType: 'ludo');
      if (!mounted) return;
      if (result['queued'] == true) {
        messenger.showSnackBar(
            const SnackBar(content: Text('En file d\'attente Ludo')));
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
    messenger.showSnackBar(SnackBar(
        content: Text(_humanColors == 2
            ? 'Entrainement : 2 couleurs (Rouge + Vert)...'
            : 'Entrainement : 1 couleur (Rouge)...')));
    try {
      final match = await ref.read(jbLudoRepositoryProvider).soloMatch(
            gameType: 'ludo',
            humanColors: _humanColors,
          );
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
                  child:
                      Image.asset('assets/games/ludo.png', fit: BoxFit.cover),
                ),
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Ludo 4 joueurs',
                          style: Theme.of(context).textTheme.titleLarge),
                      const SizedBox(height: 6),
                      const Text(
                          'Choisissez combien de couleurs vous jouez. Les tours se jouent l\'un après l\'autre.'),
                      const SizedBox(height: 16),
                      Text('Mode entrainement',
                          style: Theme.of(context).textTheme.titleSmall),
                      const SizedBox(height: 8),
                      SegmentedButton<int>(
                        segments: const [
                          ButtonSegment(
                              value: 1,
                              label: Text('1 couleur'),
                              icon: Icon(Icons.person_outline)),
                          ButtonSegment(
                              value: 2,
                              label: Text('2 couleurs'),
                              icon: Icon(Icons.group_outlined)),
                        ],
                        selected: {_humanColors},
                        onSelectionChanged: _busy
                            ? null
                            : (value) =>
                                setState(() => _humanColors = value.first),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        _humanColors == 2
                            ? 'Vous jouez Rouge puis Vert, tour à tour. IA : Bleu et Jaune.'
                            : 'Vous jouez Rouge. IA : Bleu, Vert et Jaune (un siège à la fois).',
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
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
