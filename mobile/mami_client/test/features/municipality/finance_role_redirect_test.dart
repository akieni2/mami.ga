import 'package:flutter_test/flutter_test.dart';
import 'package:mami_client/features/auth/domain/models/user_model.dart';

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

  group('finance_role_redirect_test', () {
    test('daf redirects to finance home', () {
      expect(user(roles: ['daf']).postAuthRoute, '/municipality/finance/home');
    });

    test('daf_adjoint redirects to finance home', () {
      expect(user(roles: ['daf_adjoint']).postAuthRoute, '/municipality/finance/home');
    });

    test('controleur_financier redirects to finance home', () {
      expect(user(roles: ['controleur_financier']).postAuthRoute, '/municipality/finance/home');
    });

    test('receveur_municipal redirects to finance home', () {
      expect(user(roles: ['receveur_municipal']).postAuthRoute, '/municipality/finance/home');
    });

    test('caissier_central redirects to finance home', () {
      expect(user(roles: ['caissier_central']).postAuthRoute, '/municipality/finance/home');
    });

    test('municipal_agent without finance role redirects to agent hub', () {
      expect(user(roles: ['municipal_agent']).postAuthRoute, '/municipality/agent');
    });

    test('finance role takes priority over municipal_agent', () {
      expect(
        user(roles: ['municipal_agent', 'daf']).postAuthRoute,
        '/municipality/finance/home',
      );
    });

    test('citizen redirects to home portal', () {
      expect(user(roles: ['citizen']).postAuthRoute, '/');
    });
  });
}
