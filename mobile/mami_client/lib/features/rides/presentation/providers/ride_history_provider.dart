import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/rides_repository.dart';
import '../../domain/models/ride_model.dart';

final rideHistoryProvider =
    FutureProvider.autoDispose<List<RideModel>>((ref) async {
  return ref.watch(ridesRepositoryProvider).fetchHistory();
});
