import 'package:flutter_test/flutter_test.dart';
import 'package:mami_client/core/json/json_decoders.dart';
import 'package:mami_client/features/municipality/data/models/economic_operator_model.dart';

void main() {
  group('readJsonDouble', () {
    test('parses numeric JSON values', () {
      expect(readJsonDouble(0.338), 0.338);
      expect(readJsonDouble(12), 12.0);
    });

    test('parses Laravel decimal strings', () {
      expect(readJsonDouble('0.3380000'), 0.338);
      expect(readJsonDouble('9.4710000'), 9.471);
      expect(readJsonDouble('12.50'), 12.5);
    });
  });

  group('EconomicOperatorModel.fromJson', () {
    test('parses enrollment response with string decimals', () {
      final operator = EconomicOperatorModel.fromJson({
        'id': 1,
        'public_id': 'OWE-COM-00000001',
        'commercial_name': 'Boutique SNI',
        'activity_label': 'Alimentation générale',
        'category_label': 'Boutique',
        'responsible_name': 'Jean Obame',
        'phone': '+24106000001',
        'email': null,
        'latitude': '0.3380000',
        'longitude': '9.4710000',
        'gps_accuracy_m': '12.50',
        'quartier': 'Cité SNI',
        'operational_zone': 'Centre SNI',
        'economic_zone': 'Zone A',
        'arrondissement': 'Owendo',
        'tax_status_label': 'À jour',
        'sync_status': 'synced',
        'created_at': '2026-06-16T10:00:00+00:00',
      });

      expect(operator.publicId, 'OWE-COM-00000001');
      expect(operator.latitude, 0.338);
      expect(operator.longitude, 9.471);
      expect(operator.commercialName, 'Boutique SNI');
    });

    test('parses numeric decimals for backward compatibility', () {
      final operator = EconomicOperatorModel.fromJson({
        'id': 2,
        'public_id': 'OWE-COM-000002',
        'commercial_name': 'Test',
        'activity_label': 'Test',
        'category_label': 'Boutique',
        'responsible_name': 'Agent',
        'phone': '+24106000002',
        'latitude': 0.338,
        'longitude': 9.471,
        'tax_status_label': 'À jour',
      });

      expect(operator.latitude, 0.338);
      expect(operator.longitude, 9.471);
    });
  });

  group('EconomicOperatorDashboardModel.fromJson', () {
    test('parses string coverage_percent', () {
      final dashboard = EconomicOperatorDashboardModel.fromJson({
        'registered_today': 3,
        'total_operators': 120,
        'coverage': {'coverage_percent': '45.50'},
      });

      expect(dashboard.coveragePercent, 45.5);
    });
  });
}
