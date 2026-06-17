import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../../core/theme/app_theme.dart';
import '../providers/municipality_providers.dart';

class MyMunicipalityReportsScreen extends ConsumerWidget {
  const MyMunicipalityReportsScreen({super.key});

  Color _parseColor(String hex) {
    final value = hex.replaceFirst('#', '');
    return Color(int.parse('FF$value', radix: 16));
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final reportsAsync = ref.watch(myMunicipalityReportsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes signalements'),
        backgroundColor: AppTheme.primary,
        foregroundColor: Colors.white,
      ),
      body: reportsAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (reports) {
          if (reports.isEmpty) {
            return const Center(child: Text('Aucun signalement pour le moment.'));
          }

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(myMunicipalityReportsProvider),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: reports.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final report = reports[index];
                final date = report.createdAt != null
                    ? DateFormat('dd/MM/yyyy HH:mm').format(DateTime.parse(report.createdAt!))
                    : '—';

                return Card(
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                report.reference,
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontFamily: 'monospace',
                                ),
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: _parseColor(report.statusColor).withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                report.statusLabel,
                                style: TextStyle(
                                  color: _parseColor(report.statusColor),
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        Text(report.categoryLabel, style: TextStyle(color: Colors.grey.shade700)),
                        const SizedBox(height: 4),
                        Text(report.title, style: const TextStyle(fontWeight: FontWeight.w600)),
                        const SizedBox(height: 4),
                        Text(date, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                        if (report.photoUrl != null) ...[
                          const SizedBox(height: 10),
                          ClipRRect(
                            borderRadius: BorderRadius.circular(8),
                            child: Image.network(
                              report.photoUrl!,
                              height: 120,
                              width: double.infinity,
                              fit: BoxFit.cover,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
