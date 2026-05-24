import 'package:flutter/material.dart';

import '../../features/driver/presentation/providers/driver_status_provider.dart';

class StatusChip extends StatelessWidget {
  const StatusChip({super.key, required this.status});

  final DriverUiStatus status;

  @override
  Widget build(BuildContext context) {
    final (label, color) = switch (status) {
      DriverUiStatus.online => ('En ligne', Colors.green),
      DriverUiStatus.busy => ('Occupé', Colors.orange),
      DriverUiStatus.offline => ('Hors ligne', Colors.grey),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.circle, size: 10, color: color),
          const SizedBox(width: 6),
          Text(label, style: TextStyle(color: color, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}
