import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../network/api_client.dart';
import 'app_features.dart';

final appFeaturesProvider = FutureProvider<AppFeatures>((ref) async {
  try {
    final dio = ref.watch(dioProvider);
    final response = await dio.get('/app/features');
    final data = response.data;
    if (data is Map && data['data'] is Map) {
      return AppFeatures.fromJson(Map<String, dynamic>.from(data['data'] as Map));
    }
  } on DioException {
    // API indisponible — valeurs locales.
  }

  return AppFeatures.defaults();
});
