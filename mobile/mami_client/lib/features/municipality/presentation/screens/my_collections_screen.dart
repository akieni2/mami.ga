import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/fiscal_collection_providers.dart';

class MyCollectionsScreen extends ConsumerWidget {
  const MyCollectionsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final collectionsAsync = ref.watch(myCollectionsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mes encaissements')),
      body: collectionsAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (items) {
          if (items.isEmpty) {
            return const Center(child: Text('Aucun encaissement'));
          }

          return ListView.separated(
            padding: const EdgeInsets.all(12),
            itemCount: items.length,
            separatorBuilder: (_, __) => const Divider(height: 1),
            itemBuilder: (context, index) {
              final item = items[index];
              return ListTile(
                title: Text('${item.amountXaf} XAF'),
                subtitle: Text(
                  '${item.operatorName.isNotEmpty ? item.operatorName : 'Commerce'} — ${item.sessionReference}',
                ),
                trailing: Text(item.collectedAt.length > 10
                    ? item.collectedAt.substring(0, 10)
                    : item.collectedAt),
              );
            },
          );
        },
      ),
    );
  }
}
