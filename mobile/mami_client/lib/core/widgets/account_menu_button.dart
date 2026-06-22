import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/providers/auth_provider.dart';

/// Menu compte (profil / déconnexion) pour les parcours hors shell taxi.
class AccountMenuButton extends ConsumerWidget {
  const AccountMenuButton({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authStateProvider).valueOrNull;

    return PopupMenuButton<String>(
      tooltip: 'Compte',
      icon: const Icon(Icons.account_circle_outlined),
      onSelected: (value) async {
        switch (value) {
          case 'profile':
            if (context.mounted) context.push('/account');
          case 'logout':
            final confirmed = await showDialog<bool>(
              context: context,
              builder: (ctx) => AlertDialog(
                title: const Text('Déconnexion'),
                content: const Text('Changer de compte sur cet appareil ?'),
                actions: [
                  TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Annuler')),
                  FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Déconnexion')),
                ],
              ),
            );
            if (confirmed == true) {
              await ref.read(authStateProvider.notifier).logout();
              if (context.mounted) context.go('/login');
            }
        }
      },
      itemBuilder: (context) => [
        if (user != null)
          PopupMenuItem<String>(
            enabled: false,
            child: Text(user.name, style: const TextStyle(fontWeight: FontWeight.w600)),
          ),
        const PopupMenuItem(value: 'profile', child: Text('Mon profil')),
        const PopupMenuItem(value: 'logout', child: Text('Déconnexion')),
      ],
    );
  }
}
