import 'package:flutter_test/flutter_test.dart';
import 'package:mami_client/features/municipality/data/financial_governance_repository.dart';

void main() {
  group('FinancialMissionDetail parsing', () {
    test('approved mission exposes workflow and legacy status', () {
      final mission = FinancialMissionModel.fromJson({
        'id': 10,
        'reference': 'OWE-FM-2026-000010',
        'title': 'Mission approuvée',
        'status': 'authorized',
        'status_label': 'Autorisée',
        'workflow_status': 'approved',
        'workflow_status_label': 'Approuvée',
        'valid_from': '2026-06-16',
        'valid_until': '2026-06-30',
        'rejection_reason': null,
      });

      expect(mission.isApproved, isTrue);
      expect(mission.status, 'authorized');
      expect(mission.workflowStatus, 'approved');
    });

    test('rejected mission includes rejection reason', () {
      final mission = FinancialMissionModel.fromJson({
        'id': 11,
        'reference': 'OWE-FM-2026-000011',
        'title': 'Mission rejetée',
        'status': 'draft',
        'status_label': 'Brouillon',
        'workflow_status': 'rejected',
        'workflow_status_label': 'Rejetée',
        'valid_from': '2026-06-16',
        'valid_until': '2026-06-30',
        'rejection_reason': 'Documents incomplets pour validation',
      });

      expect(mission.isRejected, isTrue);
      expect(mission.rejectionReason, contains('Documents'));
    });
  });

  group('FinancialMissionApprovalModel', () {
    test('parses workflow history entry', () {
      final entry = FinancialMissionApprovalModel.fromJson({
        'id': 1,
        'action': 'submitted',
        'created_at': '2026-06-16T10:00:00+00:00',
        'performer': {'id': 5, 'name': 'DAF Adjoint'},
        'comments': 'Soumission initiale',
      });

      expect(entry.action, 'submitted');
      expect(entry.performerName, 'DAF Adjoint');
    });
  });
}
