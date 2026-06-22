import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/account_menu_button.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../domain/finance_home_access.dart';

class FinanceHomeScreen extends ConsumerWidget {
  const FinanceHomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authStateProvider).valueOrNull;

    if (user == null || !FinanceHomeAccess.hasFinanceRole(user)) {
      return Scaffold(
        appBar: AppBar(title: const Text('Gouvernance financière')),
        body: const Center(child: Text('Accès réservé aux rôles financiers')),
      );
    }

    final access = FinanceHomeAccess(user);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Gouvernance financière'),
        backgroundColor: AppTheme.primary,
        foregroundColor: Colors.white,
        actions: const [AccountMenuButton()],
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(
            'Bonjour, ${user.name}',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 4),
          Text(access.welcomeSubtitle, style: TextStyle(color: Colors.grey.shade700)),
          const SizedBox(height: 20),
          if (access.showDashboard)
            _FinanceTile(
              icon: Icons.dashboard_outlined,
              title: 'Tableau de bord DAF',
              subtitle: 'Synthèse missions, caisses et validation',
              onTap: () => context.push(FinanceHomeRoutes.dashboard),
            ),
          if (access.showValidation)
            _FinanceTile(
              icon: Icons.fact_check_outlined,
              title: 'Validation des missions',
              subtitle: 'File contrôleur et DAF',
              onTap: () => context.push(FinanceHomeRoutes.approvals),
            ),
          if (access.showMissions)
            _FinanceTile(
              icon: Icons.assignment_outlined,
              title: 'Missions financières',
              subtitle: 'Missions terrain et périodes',
              onTap: () => context.push(FinanceHomeRoutes.missions),
            ),
          if (access.showCashSupervision)
            _FinanceTile(
              icon: Icons.point_of_sale_outlined,
              title: 'Supervision des caisses',
              subtitle: 'Caisses ouvertes et suivi',
              onTap: () => context.push(FinanceHomeRoutes.cashSupervision),
            ),
          if (access.showRemittances)
            _FinanceTile(
              icon: Icons.account_balance_outlined,
              title: 'Reversements Trésor',
              subtitle: 'Préparation et suivi reversements',
              onTap: () => context.push(FinanceHomeRoutes.remittances),
            ),
          if (access.showFutureModules) ...[
            _FinanceTile(
              icon: Icons.menu_book_outlined,
              title: 'Comptabilité',
              subtitle: 'Sprint 4.3 — bientôt disponible',
              enabled: false,
              onTap: () => _showPlaceholder(context, 'Comptabilité municipale (Sprint 4.3)'),
            ),
            _FinanceTile(
              icon: Icons.pie_chart_outline,
              title: 'Budget',
              subtitle: 'Sprint 4.4 — bientôt disponible',
              enabled: false,
              onTap: () => _showPlaceholder(context, 'Budget municipal (Sprint 4.4)'),
            ),
            _FinanceTile(
              icon: Icons.groups_outlined,
              title: 'Ressources humaines',
              subtitle: 'Sprint 4.5 — bientôt disponible',
              enabled: false,
              onTap: () => _showPlaceholder(context, 'RH municipales (Sprint 4.5)'),
            ),
            _FinanceTile(
              icon: Icons.handshake_outlined,
              title: 'Prestataires',
              subtitle: 'Sprint 4.6 — bientôt disponible',
              enabled: false,
              onTap: () => _showPlaceholder(context, 'Gestion prestataires (Sprint 4.6)'),
            ),
          ],
        ],
      ),
    );
  }

  void _showPlaceholder(BuildContext context, String label) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('$label — prochainement')),
    );
  }
}

class _FinanceTile extends StatelessWidget {
  const _FinanceTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
    this.enabled = true,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        leading: Icon(icon, color: enabled ? AppTheme.primary : Colors.grey),
        title: Text(title),
        subtitle: Text(subtitle),
        trailing: const Icon(Icons.chevron_right),
        enabled: enabled,
        onTap: enabled ? onTap : null,
      ),
    );
  }
}
