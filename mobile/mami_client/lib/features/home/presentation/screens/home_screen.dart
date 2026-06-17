import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/config/app_features.dart';
import '../../../../core/config/app_features_provider.dart';
import '../../../../core/config/mami_service_module.dart';
import '../../../../core/map/mami_map.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../location/presentation/providers/user_location_provider.dart';
import '../widgets/service_portal_grid.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  @override
  void initState() {
    super.initState();
    debugPrint('HOME SCREEN OPENED — MAMI Super App portal');
  }

  void _onModuleTap(MamiServiceModule module, bool enabled) {
    if (!enabled) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('${module.title} — bientôt disponible')),
      );
      return;
    }

    if (module == MamiServiceModule.taxi) {
      context.push('/book');
    }
  }

  @override
  Widget build(BuildContext context) {
    final locationAsync = ref.watch(userLocationProvider);
    final featuresAsync = ref.watch(appFeaturesProvider);

    return Scaffold(
      body: locationAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (_, __) => const Center(child: Text('GPS indisponible')),
        data: (location) {
          final user = location.position;
          final modules = featuresAsync.maybeWhen(
            data: (f) => f.modules,
            orElse: () => AppFeatures.defaults().modules,
          );

          return Stack(
            children: [
              Positioned.fill(
                child: MamiMap(
                  fullScreen: true,
                  user: user,
                  interactive: true,
                ),
              ),
              SafeArea(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 8,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.92),
                      borderRadius: BorderRadius.circular(24),
                    ),
                    child: const Text(
                      'MAMI Super App',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                  ),
                ),
              ),
              Positioned(
                left: 16,
                right: 16,
                bottom: 24,
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.97),
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.08),
                        blurRadius: 12,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Text(
                        'Services',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Choisissez un service — Taxi disponible maintenant',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey.shade700,
                        ),
                      ),
                      const SizedBox(height: 12),
                      ServicePortalGrid(
                        modules: modules,
                        onModuleTap: _onModuleTap,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'MAMI.GA · Libreville',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 11,
                          color: AppTheme.primary.withValues(alpha: 0.85),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
