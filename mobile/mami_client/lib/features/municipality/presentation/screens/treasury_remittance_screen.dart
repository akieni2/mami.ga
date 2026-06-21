import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/financial_governance_providers.dart';

class TreasuryRemittanceScreen extends ConsumerWidget {
  const TreasuryRemittanceScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final remittancesAsync = ref.watch(treasuryRemittancesProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Reversement Trésor Public')),
      body: remittancesAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (items) {
          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              const Text(
                'Module en préparation — brouillons de reversement vers le Trésor Public.',
                style: TextStyle(color: Colors.grey),
              ),
              const SizedBox(height: 16),
              if (items.isEmpty)
                const Text('Aucun reversement enregistré')
              else
                ...items.map(
                  (item) => Card(
                    child: ListTile(
                      title: Text(item['reference']?.toString() ?? '—'),
                      subtitle: Text(
                        '${item['amount_xaf']} XAF — ${item['status_label'] ?? item['status']}',
                      ),
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}
