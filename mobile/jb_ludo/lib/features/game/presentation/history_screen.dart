import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../data/jb_ludo_repository.dart';

class HistoryScreen extends ConsumerWidget {
  const HistoryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      appBar: AppBar(title: const Text('Historique')),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: ref.read(jbLudoRepositoryProvider).history(),
        builder: (context, snap) {
          if (!snap.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final items = snap.data!;
          if (items.isEmpty) {
            return const Center(child: Text('Aucune partie'));
          }
          return ListView.builder(
            itemCount: items.length,
            itemBuilder: (context, i) {
              final m = items[i];
              return ListTile(
                title: Text(m['reference']?.toString() ?? ''),
                subtitle: Text('${m['mode']} · ${m['status']} · ${m['result'] ?? ''}'),
                onTap: () => context.push('/match/${m['id']}'),
              );
            },
          );
        },
      ),
    );
  }
}
