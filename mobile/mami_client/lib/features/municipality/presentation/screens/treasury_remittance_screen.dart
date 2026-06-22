import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../auth/presentation/providers/auth_provider.dart';
import '../../data/financial_governance_repository.dart';
import '../../domain/finance_home_access.dart';
import '../providers/financial_governance_providers.dart';

class TreasuryRemittanceScreen extends ConsumerWidget {
  const TreasuryRemittanceScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final remittancesAsync = ref.watch(treasuryRemittancesProvider);
    final user = ref.watch(authStateProvider).valueOrNull;
    final access = user != null ? FinanceHomeAccess(user) : null;

    return Scaffold(
      appBar: AppBar(title: const Text('Reversement Trésor Public')),
      floatingActionButton: access?.canPrepareRemittances == true
          ? FloatingActionButton.extended(
              onPressed: () => _generateFromPeriod(context, ref),
              icon: const Icon(Icons.add),
              label: const Text('Générer période'),
            )
          : null,
      body: remittancesAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (items) {
          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Text(
                'Cycle : brouillon → contrôlé → DAF → receveur → déposé → confirmé',
                style: Theme.of(context).textTheme.bodySmall,
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
                        '${item['amount_xaf']} XAF · ${item['status_label'] ?? item['status']}',
                      ),
                      trailing: const Icon(Icons.chevron_right),
                      onTap: () => context.push(FinanceHomeRoutes.remittanceDetail(item['id'] as int)),
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _generateFromPeriod(BuildContext context, WidgetRef ref) async {
    final now = DateTime.now();
    final start = DateTime(now.year, now.month, 1);
    final end = DateTime(now.year, now.month + 1, 0);

    try {
      final repo = ref.read(financialGovernanceRepositoryProvider);
      final created = await repo.generateRemittanceFromPeriod(
        periodStart: start.toIso8601String().substring(0, 10),
        periodEnd: end.toIso8601String().substring(0, 10),
      );
      ref.invalidate(treasuryRemittancesProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Brouillon ${created['reference']} créé')),
        );
        context.push(FinanceHomeRoutes.remittanceDetail(created['id'] as int));
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur : $e')));
      }
    }
  }
}
