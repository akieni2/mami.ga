import 'package:flutter_test/flutter_test.dart';
import 'package:mami_client/features/municipality/data/financial_governance_repository.dart';

void main() {
  group('FinancialMissionModel', () {
    test('parses workflow fields from API payload', () {
      final mission = FinancialMissionModel.fromJson({
        'id': 1,
        'reference': 'OWE-FM-2026-000001',
        'title': 'Recouvrement SNI',
        'status': 'authorized',
        'status_label': 'Autorisée',
        'workflow_status': 'submitted',
        'workflow_status_label': 'Soumise',
        'valid_from': '2026-06-16',
        'valid_until': '2026-06-23',
        'agent': {'id': 2, 'name': 'Agent Terrain'},
        'operational_zone': {'id': 3, 'name': 'Centre SNI'},
      });

      expect(mission.workflowStatus, 'submitted');
      expect(mission.isPendingController, isTrue);
      expect(mission.isPendingDaf, isFalse);
    });

    test('pending DAF states are detected', () {
      final mission = FinancialMissionModel.fromJson({
        'id': 2,
        'reference': 'OWE-FM-2026-000002',
        'title': 'Mission DAF',
        'status': 'draft',
        'status_label': 'Brouillon',
        'workflow_status': 'daf_review',
        'workflow_status_label': 'Revue DAF',
        'valid_from': '2026-06-16',
        'valid_until': '2026-06-23',
      });

      expect(mission.isPendingDaf, isTrue);
    });
  });

  group('DafDashboardModel', () {
    test('parses validation counters', () {
      final dashboard = DafDashboardModel.fromJson({
        'missions': {
          'draft_count': 1,
          'pending_validation_count': 2,
          'approved_count': 3,
          'rejected_count': 1,
          'closed_count': 4,
        },
        'validation': {
          'pending_count': 2,
          'approved_count': 3,
          'rejected_count': 1,
          'closed_count': 4,
          'collected_today_xaf': '150000',
          'pending_validation_amount_xaf': '25000',
        },
        'cash_supervision': {'open_sessions_count': 2, 'collected_today_xaf': '150000'},
        'treasury_remittances': {'draft_count': 1},
      });

      expect(dashboard.pendingValidation, 2);
      expect(dashboard.approvedMissions, 3);
      expect(dashboard.pendingValidationAmountXaf, '25000');
    });
  });
}
