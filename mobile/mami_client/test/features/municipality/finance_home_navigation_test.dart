import 'package:flutter_test/flutter_test.dart';
import 'package:mami_client/features/municipality/domain/finance_home_access.dart';

void main() {
  group('finance_home_navigation_test', () {
    test('finance home route is defined', () {
      expect(FinanceHomeRoutes.home, '/municipality/finance/home');
    });

    test('active module routes are registered', () {
      expect(FinanceHomeRoutes.dashboard, '/municipality/finance/dashboard');
      expect(FinanceHomeRoutes.approvals, '/municipality/finance/approvals');
      expect(FinanceHomeRoutes.missions, '/municipality/finance/missions');
      expect(FinanceHomeRoutes.cashSupervision, '/municipality/finance/cash-supervision');
      expect(FinanceHomeRoutes.remittances, '/municipality/finance/remittances');
    });

    test('legacy finance root redirects to home portal', () {
      expect(FinanceHomeRoutes.legacyRoot, '/municipality/finance');
      expect(FinanceHomeRoutes.homeRedirectTarget, '/municipality/finance/home');
    });

    test('placeholder modules have no active route yet', () {
      expect(FinanceHomeRoutes.accounting, isNull);
      expect(FinanceHomeRoutes.budget, isNull);
      expect(FinanceHomeRoutes.humanResources, isNull);
      expect(FinanceHomeRoutes.suppliers, isNull);
    });
  });
}
