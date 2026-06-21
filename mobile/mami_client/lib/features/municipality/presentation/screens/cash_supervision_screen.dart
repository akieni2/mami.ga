import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../auth/presentation/providers/auth_provider.dart';
import '../../data/financial_governance_repository.dart';
import '../../domain/finance_home_access.dart';
import '../providers/financial_governance_providers.dart';

class CashSupervisionScreen extends ConsumerStatefulWidget {
  const CashSupervisionScreen({super.key});

  @override
  ConsumerState<CashSupervisionScreen> createState() => _CashSupervisionScreenState();
}

class _CashSupervisionScreenState extends ConsumerState<CashSupervisionScreen> {
  int? _closingId;

  Future<void> _adminClose(int sessionId) async {
    setState(() => _closingId = sessionId);
    try {
      await ref.read(financialGovernanceRepositoryProvider).adminCloseSession(
            sessionId,
            notes: 'Clôture administrative depuis supervision',
          );
      ref.invalidate(openCashSessionsProvider);
      ref.invalidate(dafDashboardProvider);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Caisse clôturée administrativement')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur : $e')),
        );
      }
    } finally {
      if (mounted) setState(() => _closingId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final sessionsAsync = ref.watch(openCashSessionsProvider);
    final user = ref.watch(authStateProvider).valueOrNull;
    final canAdminClose = user != null && FinanceHomeAccess(user).canAdminCloseCashSessions;

    return Scaffold(
      appBar: AppBar(title: const Text('Supervision des caisses')),
      body: sessionsAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (sessions) {
          if (sessions.isEmpty) {
            return const Center(child: Text('Aucune caisse ouverte'));
          }

          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: sessions.length,
            itemBuilder: (context, index) {
              final session = sessions[index];
              final closing = _closingId == session.id;

              return Card(
                margin: const EdgeInsets.only(bottom: 8),
                child: ListTile(
                  title: Text(session.reference),
                  subtitle: Text(
                    'Agent : ${session.agentName}\n'
                    'Attendu : ${session.expectedAmountXaf} XAF\n'
                    'Ouverte : ${session.openedAt}',
                  ),
                  isThreeLine: true,
                  trailing: canAdminClose
                      ? (closing
                          ? const SizedBox(
                              width: 24,
                              height: 24,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : IconButton(
                              icon: const Icon(Icons.lock_outline),
                              onPressed: () => _adminClose(session.id),
                            ))
                      : null,
                ),
              );
            },
          );
        },
      ),
    );
  }
}
