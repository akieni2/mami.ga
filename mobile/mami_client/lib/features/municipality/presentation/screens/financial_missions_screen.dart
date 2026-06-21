import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../providers/financial_governance_providers.dart';

class FinancialMissionsScreen extends ConsumerWidget {
  const FinancialMissionsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final missionsAsync = ref.watch(financialMissionsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Missions terrain financières')),
      body: missionsAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (missions) {
          if (missions.isEmpty) {
            return const Center(child: Text('Aucune mission enregistrée'));
          }

          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: missions.length,
            itemBuilder: (context, index) {
              final mission = missions[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 8),
                child: ListTile(
                  title: Text(mission.title),
                  subtitle: Text(
                    '${mission.reference}\nAgent : ${mission.agentName ?? '—'} · '
                    '${mission.validFrom} → ${mission.validUntil}',
                  ),
                  isThreeLine: true,
                  trailing: Chip(label: Text(mission.workflowStatusLabel)),
                  onTap: () => context.push('/municipality/finance/missions/${mission.id}'),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
