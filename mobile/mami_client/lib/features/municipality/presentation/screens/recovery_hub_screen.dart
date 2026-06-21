import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/theme/app_theme.dart';

class RecoveryHubScreen extends StatelessWidget {
  const RecoveryHubScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Recouvrement'),
        backgroundColor: AppTheme.primary,
        foregroundColor: Colors.white,
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          _tile(
            context,
            icon: Icons.point_of_sale_outlined,
            title: 'Ouvrir caisse',
            route: '/municipality/recovery/open-session',
          ),
          _tile(
            context,
            icon: Icons.qr_code_scanner,
            title: 'Scanner QR commerce',
            route: '/municipality/recovery/scan',
          ),
          _tile(
            context,
            icon: Icons.account_balance_wallet_outlined,
            title: 'Situation fiscale',
            route: '/municipality/recovery/fiscal-summary',
          ),
          _tile(
            context,
            icon: Icons.payments_outlined,
            title: 'Encaisser',
            route: '/municipality/recovery/collect',
          ),
          _tile(
            context,
            icon: Icons.receipt_long_outlined,
            title: 'Mes encaissements',
            route: '/municipality/recovery/my-collections',
          ),
          _tile(
            context,
            icon: Icons.print_outlined,
            title: 'Imprimante Bluetooth',
            route: '/municipality/recovery/printer',
          ),
          _tile(
            context,
            icon: Icons.receipt_long_outlined,
            title: 'Mes quittances',
            route: '/municipality/recovery/receipts',
          ),
          _tile(
            context,
            icon: Icons.account_balance_outlined,
            title: 'Gouvernance financière',
            route: '/municipality/finance',
          ),
          _tile(
            context,
            icon: Icons.lock_outline,
            title: 'Fermer caisse',
            route: '/municipality/recovery/close-session',
          ),
        ],
      ),
    );
  }

  Widget _tile(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String route,
  }) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        leading: Icon(icon, color: AppTheme.primary),
        title: Text(title),
        trailing: const Icon(Icons.chevron_right),
        onTap: () => context.push(route),
      ),
    );
  }
}
