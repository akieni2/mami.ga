import 'package:flutter/material.dart';

import '../../../../core/config/mami_service_module.dart';
import '../../../../core/theme/app_theme.dart';

class ServicePortalGrid extends StatelessWidget {
  const ServicePortalGrid({
    super.key,
    required this.modules,
    required this.onModuleTap,
  });

  final Map<String, bool> modules;
  final void Function(MamiServiceModule module, bool enabled) onModuleTap;

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 12,
      crossAxisSpacing: 12,
      childAspectRatio: 1.15,
      children: MamiServiceModule.values.map((module) {
        final enabled = modules[module.slug] == true || module.slug == 'taxi';

        return _ServiceTile(
          module: module,
          enabled: enabled,
          onTap: () => onModuleTap(module, enabled),
        );
      }).toList(),
    );
  }
}

class _ServiceTile extends StatelessWidget {
  const _ServiceTile({
    required this.module,
    required this.enabled,
    required this.onTap,
  });

  final MamiServiceModule module;
  final bool enabled;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: enabled
          ? Colors.white.withValues(alpha: 0.96)
          : Colors.white.withValues(alpha: 0.65),
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(
                module.icon,
                size: 32,
                color: enabled ? AppTheme.primary : Colors.grey,
              ),
              const Spacer(),
              Text(
                module.title,
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                  color: enabled ? Colors.black87 : Colors.grey.shade600,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                enabled ? module.subtitle : 'Bientôt disponible',
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey.shade600,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
