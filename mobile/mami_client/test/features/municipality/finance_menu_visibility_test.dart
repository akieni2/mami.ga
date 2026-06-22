import 'package:flutter_test/flutter_test.dart';
import 'package:mami_client/features/auth/domain/models/user_model.dart';
import 'package:mami_client/features/municipality/domain/finance_home_access.dart';

void main() {
  UserModel user({List<String> roles = const []}) {
    return UserModel(
      id: 1,
      name: 'Test',
      email: 'test@mami.ga',
      isDriver: false,
      roles: roles,
    );
  }

  group('finance_menu_visibility_test', () {
    test('daf sees full finance menu', () {
      final access = FinanceHomeAccess(user(roles: ['daf']));

      expect(access.showDashboard, isTrue);
      expect(access.showValidation, isTrue);
      expect(access.showMissions, isTrue);
      expect(access.showCashSupervision, isTrue);
      expect(access.showRemittances, isTrue);
      expect(access.showFutureModules, isTrue);
      expect(access.canAdminCloseCashSessions, isTrue);
    });

    test('daf_adjoint sees menu without admin close', () {
      final access = FinanceHomeAccess(user(roles: ['daf_adjoint']));

      expect(access.showDashboard, isTrue);
      expect(access.showValidation, isTrue);
      expect(access.showMissions, isTrue);
      expect(access.showCashSupervision, isTrue);
      expect(access.showRemittances, isTrue);
      expect(access.showFutureModules, isTrue);
      expect(access.canAdminCloseCashSessions, isFalse);
    });

    test('controleur sees validation-focused menu', () {
      final access = FinanceHomeAccess(user(roles: ['controleur_financier']));

      expect(access.showDashboard, isFalse);
      expect(access.showValidation, isTrue);
      expect(access.showMissions, isFalse);
      expect(access.showCashSupervision, isTrue);
      expect(access.showRemittances, isTrue);
      expect(access.canControlRemittances, isTrue);
      expect(access.showFutureModules, isFalse);
      expect(access.canAdminCloseCashSessions, isTrue);
    });

    test('receveur sees remittances only', () {
      final access = FinanceHomeAccess(user(roles: ['receveur_municipal']));

      expect(access.showDashboard, isFalse);
      expect(access.showValidation, isFalse);
      expect(access.showMissions, isFalse);
      expect(access.showCashSupervision, isFalse);
      expect(access.showRemittances, isTrue);
      expect(access.showFutureModules, isFalse);
    });

    test('caissier central sees cash supervision only', () {
      final access = FinanceHomeAccess(user(roles: ['caissier_central']));

      expect(access.showDashboard, isFalse);
      expect(access.showValidation, isFalse);
      expect(access.showMissions, isFalse);
      expect(access.showCashSupervision, isTrue);
      expect(access.showRemittances, isFalse);
      expect(access.showFutureModules, isFalse);
      expect(access.canAdminCloseCashSessions, isFalse);
    });

    test('municipal agent is not a finance user', () {
      expect(FinanceHomeAccess.hasFinanceRole(user(roles: ['municipal_agent'])), isFalse);
    });
  });
}
