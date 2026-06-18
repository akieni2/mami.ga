import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/theme/app_theme.dart';
import '../providers/economic_operator_providers.dart';

class MunicipalityAgentHomeScreen extends ConsumerWidget {
  const MunicipalityAgentHomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboardAsync = ref.watch(economicOperatorDashboardProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Mairie — Agent terrain'),
        backgroundColor: AppTheme.primary,
        foregroundColor: Colors.white,
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const Text(
            'Accueil agent municipal',
            style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          Text(
            'Module économique communal — Owendo',
            style: TextStyle(color: Colors.grey.shade700),
          ),
          const SizedBox(height: 20),
          dashboardAsync.when(
            data: (kpis) => Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Aujourd\'hui : ${kpis.registeredToday} commerce(s)'),
                    Text('Total enregistrés : ${kpis.totalOperators}'),
                    Text(
                      'Couverture quartiers : ${kpis.coveragePercent.toStringAsFixed(1)} %',
                    ),
                  ],
                ),
              ),
            ),
            loading: () => const LinearProgressIndicator(),
            error: (_, __) => const SizedBox.shrink(),
          ),
          const SizedBox(height: 24),
          _AgentMenuTile(
            icon: Icons.storefront_outlined,
            title: 'Recensement économique',
            subtitle: 'Enrôlement terrain GPS',
            enabled: true,
            onTap: () => context.push('/municipality/enrollment/new'),
          ),
          _AgentMenuTile(
            icon: Icons.qr_code_scanner,
            title: 'Scanner QR Commerce',
            subtitle: 'Identification commerce',
            enabled: true,
            onTap: () => context.push('/municipality/recovery/scan'),
          ),
          _AgentMenuTile(
            icon: Icons.fact_check_outlined,
            title: 'Contrôles terrain',
            subtitle: 'Bientôt disponible',
            enabled: false,
            onTap: () => _showSoon(context),
          ),
          _AgentMenuTile(
            icon: Icons.payments_outlined,
            title: 'Recouvrement',
            subtitle: 'Caisse, consultation, encaissement',
            enabled: true,
            onTap: () => context.push('/municipality/recovery'),
          ),
          _AgentMenuTile(
            icon: Icons.history,
            title: 'Historique',
            subtitle: 'Bientôt disponible',
            enabled: false,
            onTap: () => _showSoon(context),
          ),
          _AgentMenuTile(
            icon: Icons.cloud_sync_outlined,
            title: 'Synchronisation',
            subtitle: 'Bientôt disponible — V2.1 offline',
            enabled: false,
            onTap: () => _showSoon(context),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: () => context.push('/municipality'),
            icon: const Icon(Icons.people_alt_outlined),
            label: const Text('Signalements citoyens'),
          ),
        ],
      ),
    );
  }

  void _showSoon(BuildContext context) {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Bientôt disponible')),
    );
  }
}

class _AgentMenuTile extends StatelessWidget {
  const _AgentMenuTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.enabled,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final bool enabled;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        leading: Icon(icon, color: enabled ? AppTheme.primary : Colors.grey),
        title: Text(title),
        subtitle: Text(subtitle),
        trailing: enabled
            ? const Icon(Icons.chevron_right)
            : Chip(
                label: const Text('Bientôt', style: TextStyle(fontSize: 11)),
                visualDensity: VisualDensity.compact,
              ),
        onTap: onTap,
      ),
    );
  }
}
