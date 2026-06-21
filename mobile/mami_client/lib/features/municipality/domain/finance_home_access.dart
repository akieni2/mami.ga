import '../../auth/domain/models/user_model.dart';

/// Visibilité du portail gouvernance financière (Sprint 4.1.1).
class FinanceHomeAccess {
  const FinanceHomeAccess(this.user);

  final UserModel user;

  static const financeRoleSlugs = {
    'daf',
    'daf_adjoint',
    'controleur_financier',
    'receveur_municipal',
    'caissier_central',
  };

  static bool hasFinanceRole(UserModel user) {
    return user.roles.any(financeRoleSlugs.contains);
  }

  bool get isDaf => user.roles.contains('daf');
  bool get isDafAdjoint => user.roles.contains('daf_adjoint');
  bool get isControleur => user.roles.contains('controleur_financier');
  bool get isReceveur => user.roles.contains('receveur_municipal');
  bool get isCaissierCentral => user.roles.contains('caissier_central');

  bool get showDashboard => isDaf || isDafAdjoint;

  bool get showValidation => isDaf || isDafAdjoint || isControleur;

  bool get showMissions => isDaf || isDafAdjoint;

  bool get showCashSupervision =>
      isDaf || isDafAdjoint || isControleur || isCaissierCentral;

  bool get showRemittances => isDaf || isDafAdjoint || isReceveur;

  bool get showFutureModules => isDaf || isDafAdjoint;

  bool get canAdminCloseCashSessions => isDaf || isControleur;

  String get welcomeSubtitle {
    if (isDaf) return 'Directeur des Affaires Financières';
    if (isDafAdjoint) return 'DAF adjoint';
    if (isControleur) return 'Contrôleur financier';
    if (isReceveur) return 'Receveur municipal';
    if (isCaissierCentral) return 'Caissier central';
    return 'Gouvernance financière';
  }
}

/// Routes du portail finance (tests + navigation).
abstract final class FinanceHomeRoutes {
  static const home = '/municipality/finance/home';
  static const legacyRoot = '/municipality/finance';
  static const homeRedirectTarget = home;
  static const dashboard = '/municipality/finance/dashboard';
  static const approvals = '/municipality/finance/approvals';
  static const missions = '/municipality/finance/missions';
  static const cashSupervision = '/municipality/finance/cash-supervision';
  static const remittances = '/municipality/finance/remittances';

  static const String? accounting = null;
  static const String? budget = null;
  static const String? humanResources = null;
  static const String? suppliers = null;
}
