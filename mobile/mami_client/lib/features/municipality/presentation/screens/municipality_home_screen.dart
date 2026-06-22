import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../auth/presentation/providers/auth_provider.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/account_menu_button.dart';

class MunicipalityHomeScreen extends ConsumerWidget {
  const MunicipalityHomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authStateProvider).valueOrNull;
    final isAgent = user?.canEnrollEconomicOperators ?? false;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Mairie d\'Owendo'),
        backgroundColor: AppTheme.primary,
        foregroundColor: Colors.white,
        actions: const [AccountMenuButton()],
      ),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (isAgent) ...[
              const Text(
                'Espace agent municipal',
                style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              Text(
                'Recensement économique sur le terrain.',
                style: TextStyle(color: Colors.grey.shade700),
              ),
              const SizedBox(height: 24),
              FilledButton.icon(
                onPressed: () => context.push('/municipality/agent'),
                icon: const Icon(Icons.storefront_outlined),
                label: const Text('Recensement économique'),
                style: FilledButton.styleFrom(
                  backgroundColor: AppTheme.primary,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
              ),
              const SizedBox(height: 24),
              const Divider(),
              const SizedBox(height: 16),
            ],
            const Text(
              'Signalements citoyens',
              style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Text(
              'Signalez un problème dans votre quartier. La mairie suit votre dossier.',
              style: TextStyle(color: Colors.grey.shade700),
            ),
            const SizedBox(height: 24),
            if (!isAgent)
              FilledButton.icon(
                onPressed: () => context.push('/municipality/report/new'),
                icon: const Icon(Icons.add_location_alt_outlined),
                label: const Text('Signaler un problème'),
                style: FilledButton.styleFrom(
                  backgroundColor: AppTheme.primary,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
              ),
            if (!isAgent) const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: () => context.push('/municipality/reports'),
              icon: const Icon(Icons.list_alt_outlined),
              label: const Text('Mes signalements'),
              style: OutlinedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 14),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
