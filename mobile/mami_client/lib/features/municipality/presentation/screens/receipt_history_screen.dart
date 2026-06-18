import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../providers/fiscal_collection_providers.dart';

class ReceiptHistoryScreen extends ConsumerWidget {
  const ReceiptHistoryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final receiptsAsync = ref.watch(myReceiptsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mes quittances')),
      body: receiptsAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (items) {
          if (items.isEmpty) {
            return const Center(child: Text('Aucune quittance émise'));
          }

          return ListView.separated(
            padding: const EdgeInsets.all(12),
            itemCount: items.length,
            separatorBuilder: (_, __) => const Divider(height: 1),
            itemBuilder: (context, index) {
              final receipt = items[index];
              return ListTile(
                title: Text(receipt.receiptNumber),
                subtitle: Text(
                  '${receipt.printPayload.commercialName} — ${receipt.printPayload.amountXaf} XAF\n'
                  '${receipt.statusLabel}',
                ),
                isThreeLine: true,
                trailing: const Icon(Icons.print_outlined),
                onTap: () => context.push(
                  '/municipality/recovery/print-receipt/${receipt.id}',
                ),
              );
            },
          );
        },
      ),
    );
  }
}
