import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/primary_button.dart';
import '../../../auth/presentation/providers/auth_provider.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authStateProvider).valueOrNull;
    final themeMode = ref.watch(themeModeProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Profil')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            child: ListTile(
              leading: CircleAvatar(
                child: Text(
                  (user?.name.isNotEmpty == true)
                      ? user!.name[0].toUpperCase()
                      : '?',
                ),
              ),
              title: Text(user?.name ?? '—'),
              subtitle: Text('${user?.email ?? ''}\n${user?.phone ?? ''}'),
              isThreeLine: user?.phone != null,
            ),
          ),
          const SizedBox(height: 8),
          Card(
            child: SwitchListTile(
              title: const Text('Mode sombre'),
              value: themeMode == ThemeMode.dark,
              onChanged: (v) {
                ref.read(themeModeProvider.notifier).state =
                    v ? ThemeMode.dark : ThemeMode.light;
              },
            ),
          ),
          ListTile(
            title: const Text('API'),
            subtitle: Text(AppConfig.apiBaseUrl),
          ),
          const SizedBox(height: 24),
          PrimaryButton(
            label: 'Déconnexion',
            color: Colors.red.shade700,
            onPressed: () async {
              await ref.read(authStateProvider.notifier).logout();
              if (context.mounted) context.go('/login');
            },
          ),
        ],
      ),
    );
  }
}
