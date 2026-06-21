import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../data/financial_governance_repository.dart';
import '../providers/financial_governance_providers.dart';

class FinancialApprovalQueueScreen extends ConsumerStatefulWidget {
  const FinancialApprovalQueueScreen({super.key});

  @override
  ConsumerState<FinancialApprovalQueueScreen> createState() => _FinancialApprovalQueueScreenState();
}

class _FinancialApprovalQueueScreenState extends ConsumerState<FinancialApprovalQueueScreen> with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final pendingAsync = ref.watch(pendingApprovalsProvider);
    final missionsAsync = ref.watch(financialMissionsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('File de validation'),
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          tabs: const [
            Tab(text: 'Contrôleur'),
            Tab(text: 'DAF'),
            Tab(text: 'Approuvées'),
            Tab(text: 'Rejetées'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _MissionList(
            asyncValue: pendingAsync,
            filter: (m) => m.isPendingController,
            emptyLabel: 'Aucune mission en attente contrôleur',
          ),
          _MissionList(
            asyncValue: pendingAsync,
            filter: (m) => m.isPendingDaf,
            emptyLabel: 'Aucune mission en attente DAF',
          ),
          _MissionList(
            asyncValue: missionsAsync,
            filter: (m) => m.isApproved,
            emptyLabel: 'Aucune mission approuvée',
          ),
          _MissionList(
            asyncValue: missionsAsync,
            filter: (m) => m.isRejected,
            emptyLabel: 'Aucune mission rejetée',
          ),
        ],
      ),
    );
  }
}

class _MissionList extends StatelessWidget {
  const _MissionList({
    required this.asyncValue,
    required this.filter,
    required this.emptyLabel,
  });

  final AsyncValue<List<FinancialMissionModel>> asyncValue;
  final bool Function(FinancialMissionModel mission) filter;
  final String emptyLabel;

  @override
  Widget build(BuildContext context) {
    return asyncValue.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, _) => Center(child: Text('Erreur : $e')),
      data: (missions) {
        final filtered = missions.where(filter).toList();
        if (filtered.isEmpty) {
          return Center(child: Text(emptyLabel));
        }

        return ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: filtered.length,
          itemBuilder: (context, index) {
            final mission = filtered[index];
            return Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ListTile(
                title: Text(mission.title),
                subtitle: Text(
                  '${mission.reference}\n${mission.agentName ?? '—'} · ${mission.validFrom} → ${mission.validUntil}',
                ),
                isThreeLine: true,
                trailing: Chip(label: Text(mission.workflowStatusLabel)),
                onTap: () => context.push('/municipality/finance/missions/${mission.id}'),
              ),
            );
          },
        );
      },
    );
  }
}
