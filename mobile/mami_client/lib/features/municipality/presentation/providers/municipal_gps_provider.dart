import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/municipal_gps_service.dart';

final municipalGpsServiceProvider = Provider<MunicipalGpsService>(
  (ref) => MunicipalGpsService(),
);
