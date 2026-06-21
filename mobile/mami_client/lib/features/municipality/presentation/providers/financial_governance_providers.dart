import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/financial_governance_repository.dart';

final dafDashboardProvider = FutureProvider.autoDispose<DafDashboardModel>((ref) async {
  return ref.watch(financialGovernanceRepositoryProvider).fetchDashboard();
});

final financialMissionsProvider = FutureProvider.autoDispose<List<FinancialMissionModel>>((ref) async {
  return ref.watch(financialGovernanceRepositoryProvider).fetchMissions();
});

final pendingApprovalsProvider = FutureProvider.autoDispose<List<FinancialMissionModel>>((ref) async {
  return ref.watch(financialGovernanceRepositoryProvider).fetchPendingApprovals();
});

final financialMissionDetailProvider = FutureProvider.autoDispose.family<FinancialMissionModel, int>((ref, id) async {
  return ref.watch(financialGovernanceRepositoryProvider).fetchMission(id);
});

final missionWorkflowHistoryProvider = FutureProvider.autoDispose.family<List<FinancialMissionApprovalModel>, int>((ref, id) async {
  return ref.watch(financialGovernanceRepositoryProvider).fetchMissionWorkflowHistory(id);
});

final approvalHistoryProvider = FutureProvider.autoDispose<List<FinancialMissionApprovalModel>>((ref) async {
  return ref.watch(financialGovernanceRepositoryProvider).fetchApprovalHistory();
});

final currentFinancialMissionProvider = FutureProvider.autoDispose<FinancialMissionModel?>((ref) async {
  return ref.watch(financialGovernanceRepositoryProvider).fetchCurrentMission();
});

final openCashSessionsProvider = FutureProvider.autoDispose<List<OpenCashSessionSummary>>((ref) async {
  return ref.watch(financialGovernanceRepositoryProvider).fetchOpenSessions();
});

final treasuryRemittancesProvider = FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  return ref.watch(financialGovernanceRepositoryProvider).fetchRemittances();
});
