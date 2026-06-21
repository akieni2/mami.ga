import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/financial_governance_repository.dart';

final dafDashboardProvider = FutureProvider.autoDispose<DafDashboardModel>((ref) async {
  return ref.watch(financialGovernanceRepositoryProvider).fetchDashboard();
});

final financialMissionsProvider = FutureProvider.autoDispose<List<FinancialMissionModel>>((ref) async {
  return ref.watch(financialGovernanceRepositoryProvider).fetchMissions();
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
