import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../providers/financial_governance_providers.dart';

class DafDashboardScreen extends ConsumerWidget {
  const DafDashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboardAsync = ref.watch(dafDashboardProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Tableau de bord DAF')),
      body: dashboardAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (dashboard) => ListView(
          padding: const EdgeInsets.all(20),
          children: [
            _StatCard(label: 'Missions à valider', value: '${dashboard.pendingValidation}'),
            _StatCard(label: 'Missions approuvées', value: '${dashboard.approvedMissions}'),
            _StatCard(label: 'Missions rejetées', value: '${dashboard.rejectedMissions}'),
            _StatCard(label: 'Missions clôturées', value: '${dashboard.closedMissions}'),
            _StatCard(label: 'Montant recouvré (jour)', value: '${dashboard.collectedTodayXaf} XAF'),
            _StatCard(label: 'Montant attente validation', value: '${dashboard.pendingValidationAmountXaf} XAF'),
            _StatCard(label: 'Caisses ouvertes', value: '${dashboard.openSessionsCount}'),
            _StatCard(label: 'Reversements brouillon', value: '${dashboard.remittanceDraftCount}'),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: () => context.push('/municipality/finance/approvals'),
              child: const Text('File de validation'),
            ),
            const SizedBox(height: 8),
            FilledButton.tonal(
              onPressed: () => context.push('/municipality/finance/missions'),
              child: const Text('Missions terrain financières'),
            ),
            const SizedBox(height: 8),
            FilledButton.tonal(
              onPressed: () => context.push('/municipality/finance/cash-supervision'),
              child: const Text('Supervision des caisses'),
            ),
            const SizedBox(height: 8),
            OutlinedButton(
              onPressed: () => context.push('/municipality/finance/remittances'),
              child: const Text('Reversement Trésor Public'),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        title: Text(label),
        trailing: Text(value, style: const TextStyle(fontWeight: FontWeight.bold)),
      ),
    );
  }
}
