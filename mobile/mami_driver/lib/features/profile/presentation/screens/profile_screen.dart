import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/widgets/primary_button.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../../location/presentation/providers/location_tracker_provider.dart';

final themeModeProvider = StateProvider<ThemeMode>((ref) => ThemeMode.system);

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
                  (user?.name.isNotEmpty == true) ? user!.name[0].toUpperCase() : '?',
                ),
              ),
              title: Text(user?.name ?? '—'),
              subtitle: Text(user?.email ?? ''),
            ),
          ),
          const SizedBox(height: 8),
          if (user?.driver != null) ...[
            Card(
              child: Column(
                children: [
                  ListTile(
                    title: const Text('Permis'),
                    trailing: Text(user!.driver!.licenseNumber),
                  ),
                  if (user.driver!.vehicleLabel != null)
                    ListTile(
                      title: const Text('Véhicule'),
                      trailing: Text(user.driver!.vehicleLabel!),
                    ),
                  if (user.driver!.rating != null)
                    ListTile(
                      title: const Text('Note'),
                      trailing: Text(user.driver!.rating!.toStringAsFixed(1)),
                    ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 8),
          Card(
            child: SwitchListTile(
              title: const Text('Mode sombre'),
              subtitle: const Text('Compatible clair / sombre'),
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
              ref.read(locationTrackerProvider.notifier).stop();
              await ref.read(authStateProvider.notifier).logout();
              if (context.mounted) context.go('/login');
            },
          ),
        ],
      ),
    );
  }
}
