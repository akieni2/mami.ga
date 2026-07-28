import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/jb_ludo_repository.dart';

class LeaderboardScreen extends ConsumerWidget {
  const LeaderboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      appBar: AppBar(title: const Text('Classement')),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: ref.read(jbLudoRepositoryProvider).leaderboard(),
        builder: (context, snap) {
          if (!snap.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final items = snap.data!;
          return ListView.builder(
            itemCount: items.length,
            itemBuilder: (context, i) {
              final p = items[i];
              return ListTile(
                leading: CircleAvatar(child: Text('${i + 1}')),
                title: Text(p['pseudo']?.toString() ?? ''),
                subtitle: Text(p['city']?.toString() ?? ''),
                trailing: Text('${p['points']} pts', style: const TextStyle(fontWeight: FontWeight.bold)),
              );
            },
          );
        },
      ),
    );
  }
}
