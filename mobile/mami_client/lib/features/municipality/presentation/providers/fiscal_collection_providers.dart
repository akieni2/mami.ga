import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/fiscal_collection_repository.dart';

final currentCashSessionProvider = FutureProvider<CashSessionModel?>((ref) async {
  final repo = ref.watch(fiscalCollectionRepositoryProvider);
  return repo.fetchCurrentSession();
});

final myCollectionsProvider = FutureProvider<List<MunicipalCollectionModel>>((ref) async {
  final repo = ref.watch(fiscalCollectionRepositoryProvider);
  return repo.fetchMyCollections();
});

final fiscalSummaryProvider =
    FutureProvider.family<FiscalOperatorSummary, int>((ref, operatorId) async {
  final repo = ref.watch(fiscalCollectionRepositoryProvider);
  return repo.fetchOperatorSummary(operatorId);
});
