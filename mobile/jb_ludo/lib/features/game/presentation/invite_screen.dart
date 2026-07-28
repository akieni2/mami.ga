import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../data/jb_ludo_repository.dart';

class InviteScreen extends ConsumerStatefulWidget {
  const InviteScreen({super.key});

  @override
  ConsumerState<InviteScreen> createState() => _InviteScreenState();
}

class _InviteScreenState extends ConsumerState<InviteScreen> {
  final _q = TextEditingController();
  List<Map<String, dynamic>> _results = [];

  @override
  void dispose() {
    _q.dispose();
    super.dispose();
  }

  Future<void> _search() async {
    final list = await ref.read(jbLudoRepositoryProvider).searchPlayers(_q.text.trim());
    if (mounted) setState(() => _results = list);
  }

  Future<void> _invite(int id) async {
    try {
      await ref.read(jbLudoRepositoryProvider).invite(id);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Invitation envoyée — l\'adversaire doit accepter')),
        );
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Inviter')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(child: TextField(controller: _q, decoration: const InputDecoration(labelText: 'Pseudo ou téléphone'))),
                IconButton(onPressed: _search, icon: const Icon(Icons.search)),
              ],
            ),
          ),
          Expanded(
            child: ListView.builder(
              itemCount: _results.length,
              itemBuilder: (context, i) {
                final p = _results[i];
                return ListTile(
                  title: Text(p['pseudo']?.toString() ?? ''),
                  subtitle: Text('${p['city'] ?? ''} · ${p['points']} pts'),
                  trailing: FilledButton(
                    onPressed: () => _invite(p['id'] as int),
                    child: const Text('Inviter'),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
