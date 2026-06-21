import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/financial_governance_providers.dart';

class FinancialMissionHistoryScreen extends ConsumerWidget {
  const FinancialMissionHistoryScreen({required this.missionId, super.key});

  final int missionId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final historyAsync = ref.watch(missionWorkflowHistoryProvider(missionId));
    final missionAsync = ref.watch(financialMissionDetailProvider(missionId));

    return Scaffold(
      appBar: AppBar(title: const Text('Historique validation')),
      body: historyAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (entries) {
          final missionRef = missionAsync.maybeWhen(data: (m) => m.reference, orElse: () => '—');

          if (entries.isEmpty) {
            return Center(child: Text('Aucun historique pour $missionRef'));
          }

          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: entries.length,
            separatorBuilder: (_, __) => const Divider(),
            itemBuilder: (context, index) {
              final entry = entries[index];
              return ListTile(
                leading: CircleAvatar(child: Text('${index + 1}')),
                title: Text(entry.action),
                subtitle: Text(
                  '${entry.performerName ?? '—'}\n${entry.createdAt}${entry.comments != null ? '\n${entry.comments}' : ''}',
                ),
                isThreeLine: true,
              );
            },
          );
        },
      ),
    );
  }
}
