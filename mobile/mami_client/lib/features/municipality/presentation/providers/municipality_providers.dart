import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/municipality_repository.dart';
import '../../data/models/municipality_report_model.dart';

final myMunicipalityReportsProvider =
    FutureProvider.autoDispose<List<MunicipalityReportModel>>((ref) async {
  return ref.watch(municipalityRepositoryProvider).fetchMyReports();
});
