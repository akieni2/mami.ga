import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../data/financial_governance_repository.dart';
import '../providers/financial_governance_providers.dart';

class FinancialMissionDetailScreen extends ConsumerWidget {
  const FinancialMissionDetailScreen({required this.missionId, super.key});

  final int missionId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final missionAsync = ref.watch(financialMissionDetailProvider(missionId));
    final historyAsync = ref.watch(missionWorkflowHistoryProvider(missionId));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Détail mission'),
        actions: [
          IconButton(
            icon: const Icon(Icons.history),
            onPressed: () => context.push('/municipality/finance/missions/$missionId/history'),
          ),
        ],
      ),
      body: missionAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (mission) => ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Text(mission.title, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 8),
            Chip(label: Text(mission.workflowStatusLabel)),
            const SizedBox(height: 16),
            _InfoRow(label: 'Référence', value: mission.reference),
            _InfoRow(label: 'Agent', value: mission.agentName ?? '—'),
            _InfoRow(label: 'Zone', value: mission.zoneName ?? '—'),
            _InfoRow(label: 'Période', value: '${mission.validFrom} → ${mission.validUntil}'),
            if (mission.rejectionReason != null)
              _InfoRow(label: 'Motif rejet', value: mission.rejectionReason!),
            const SizedBox(height: 16),
            Text('Historique récent', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 8),
            historyAsync.when(
              loading: () => const LinearProgressIndicator(),
              error: (e, _) => Text('Journal : $e'),
              data: (entries) {
                if (entries.isEmpty) {
                  return const Text('Aucune entrée');
                }
                return Column(
                  children: entries
                      .map(
                        (entry) => ListTile(
                          dense: true,
                          contentPadding: EdgeInsets.zero,
                          title: Text(entry.action),
                          subtitle: Text('${entry.performerName ?? '—'} · ${entry.createdAt}'),
                        ),
                      )
                      .toList(),
                );
              },
            ),
            const SizedBox(height: 24),
            _WorkflowActions(mission: mission),
          ],
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(width: 100, child: Text(label, style: const TextStyle(fontWeight: FontWeight.w600))),
          Expanded(child: Text(value)),
        ],
      ),
    );
  }
}

class _WorkflowActions extends ConsumerWidget {
  const _WorkflowActions({required this.mission});

  final FinancialMissionModel mission;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final repo = ref.watch(financialGovernanceRepositoryProvider);

    Future<void> refresh() async {
      ref.invalidate(financialMissionDetailProvider(mission.id));
      ref.invalidate(missionWorkflowHistoryProvider(mission.id));
      ref.invalidate(pendingApprovalsProvider);
      ref.invalidate(financialMissionsProvider);
    }

    Future<void> run(Future<FinancialMissionModel> Function() action, String success) async {
      try {
        await action();
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(success)));
          await refresh();
        }
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur : $e')));
        }
      }
    }

    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        if (mission.isDraft)
          FilledButton(
            onPressed: () => run(() => repo.submitMission(mission.id), 'Mission soumise'),
            child: const Text('Soumettre'),
          ),
        if (mission.isPendingController || mission.isPendingDaf)
          FilledButton.tonal(
            onPressed: () => run(() => repo.reviewMission(mission.id), 'Mission revue'),
            child: const Text('Valider'),
          ),
        if (mission.workflowStatus == 'daf_review')
          FilledButton(
            onPressed: () => run(() => repo.approveMission(mission.id), 'Mission approuvée'),
            child: const Text('Approuver'),
          ),
        if (!mission.isRejected && !mission.isDraft && mission.workflowStatus != 'closed')
          OutlinedButton(
            onPressed: () async {
              final reason = await _promptRejectReason(context);
              if (reason == null) return;
              await run(() => repo.rejectMission(mission.id, reason: reason), 'Mission rejetée');
            },
            child: const Text('Rejeter'),
          ),
        if (mission.isApproved)
          FilledButton.tonal(
            onPressed: () => run(() => repo.closeMission(mission.id), 'Mission clôturée'),
            child: const Text('Clôturer'),
          ),
      ],
    );
  }

  Future<String?> _promptRejectReason(BuildContext context) async {
    final controller = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Motif de rejet'),
        content: TextField(
          controller: controller,
          maxLines: 3,
          decoration: const InputDecoration(hintText: 'Minimum 10 caractères'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Annuler')),
          FilledButton(
            onPressed: () {
              final text = controller.text.trim();
              if (text.length < 10) return;
              Navigator.pop(context, text);
            },
            child: const Text('Confirmer'),
          ),
        ],
      ),
    );
  }
}
