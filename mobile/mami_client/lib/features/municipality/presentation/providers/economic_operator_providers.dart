import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/economic_operator_repository.dart';
import '../../data/models/economic_operator_category_model.dart';
import '../../data/models/economic_operator_model.dart';

final economicOperatorCategoriesProvider =
    FutureProvider.autoDispose<List<EconomicOperatorCategoryModel>>((ref) async {
  return ref.watch(economicOperatorRepositoryProvider).fetchCategories();
});

final economicOperatorDashboardProvider =
    FutureProvider.autoDispose<EconomicOperatorDashboardModel>((ref) async {
  return ref.watch(economicOperatorRepositoryProvider).fetchDashboard();
});
