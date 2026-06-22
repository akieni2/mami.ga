import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../auth/presentation/providers/auth_provider.dart';
import '../../data/financial_governance_repository.dart';
import '../../domain/finance_home_access.dart';
import '../providers/financial_governance_providers.dart';

class TreasuryRemittanceDetailScreen extends ConsumerWidget {
  const TreasuryRemittanceDetailScreen({required this.remittanceId, super.key});

  final int remittanceId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final remittanceAsync = ref.watch(treasuryRemittanceDetailProvider(remittanceId));
    final user = ref.watch(authStateProvider).valueOrNull;
    final access = user != null ? FinanceHomeAccess(user) : null;

    return Scaffold(
      appBar: AppBar(title: const Text('Détail reversement')),
      body: remittanceAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (item) => ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Text(item['reference']?.toString() ?? '—', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 8),
            Chip(label: Text(item['status_label']?.toString() ?? item['status']?.toString() ?? '—')),
            const SizedBox(height: 16),
            _InfoRow(label: 'Montant', value: '${item['amount_xaf']} XAF'),
            _InfoRow(label: 'Période', value: '${item['period_start'] ?? '—'} → ${item['period_end'] ?? '—'}'),
            _InfoRow(label: 'Encaissements', value: '${item['payment_count'] ?? 0}'),
            if (item['treasury_receipt_ref'] != null)
              _InfoRow(label: 'Reçu Trésor', value: item['treasury_receipt_ref'].toString()),
            if (item['rejection_reason'] != null)
              _InfoRow(label: 'Motif rejet', value: item['rejection_reason'].toString()),
            const SizedBox(height: 24),
            if (access != null) _WorkflowActions(remittanceId: remittanceId, item: item, access: access),
          ],
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(width: 110, child: Text(label, style: const TextStyle(fontWeight: FontWeight.w600))),
          Expanded(child: Text(value)),
        ],
      ),
    );
  }
}

class _WorkflowActions extends ConsumerWidget {
  const _WorkflowActions({required this.remittanceId, required this.item, required this.access});

  final int remittanceId;
  final Map<String, dynamic> item;
  final FinanceHomeAccess access;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final status = item['status']?.toString() ?? '';
    final repo = ref.watch(financialGovernanceRepositoryProvider);

    Future<void> refresh() async {
      ref.invalidate(treasuryRemittanceDetailProvider(remittanceId));
      ref.invalidate(treasuryRemittancesProvider);
      ref.invalidate(pendingRemittancesProvider);
    }

    Future<void> run(Future<Map<String, dynamic>> Function() action, String success) async {
      try {
        await action();
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(success)));
          await refresh();
        }
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur : $e')));
        }
      }
    }

    final actions = <Widget>[];

    if (status == 'draft' && access.canControlRemittances) {
      actions.add(FilledButton(
        onPressed: () => run(() => repo.controlRemittance(remittanceId), 'Reversement contrôlé'),
        child: const Text('Contrôler'),
      ));
    }
    if (status == 'controlled' && access.canValidateRemittanceDaf) {
      actions.add(FilledButton(
        onPressed: () => run(() => repo.validateRemittanceDaf(remittanceId), 'Validé DAF'),
        child: const Text('Valider DAF'),
      ));
    }
    if (status == 'daf_validated' && access.canValidateRemittanceReceveur) {
      actions.add(FilledButton(
        onPressed: () => run(() => repo.validateRemittanceReceveur(remittanceId), 'Validé receveur'),
        child: const Text('Valider receveur'),
      ));
    }
    if (status == 'receveur_validated' && access.canDepositRemittance) {
      actions.add(FilledButton(
        onPressed: () => _showDepositDialog(context, repo, refresh),
        child: const Text('Enregistrer dépôt'),
      ));
    }
    if (status == 'deposited' && access.canConfirmRemittance) {
      actions.add(FilledButton(
        onPressed: () => _showConfirmDialog(context, repo, refresh),
        child: const Text('Confirmer Trésor'),
      ));
    }
    if (['controlled', 'daf_validated', 'receveur_validated'].contains(status) &&
        (access.canValidateRemittanceDaf || access.canValidateRemittanceReceveur || access.canDepositRemittance)) {
      actions.add(OutlinedButton(
        onPressed: () => _showRejectDialog(context, repo, refresh),
        child: const Text('Rejeter'),
      ));
    }

    if (actions.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: actions.map((w) => Padding(padding: const EdgeInsets.only(bottom: 8), child: w)).toList(),
    );
  }

  Future<void> _showDepositDialog(BuildContext context, FinancialGovernanceRepository repo, Future<void> Function() refresh) async {
    final slip = TextEditingController();
    final bank = TextEditingController();
    final refDeposit = TextEditingController();

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Dépôt bancaire'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(controller: slip, decoration: const InputDecoration(labelText: 'N° bordereau')),
            TextField(controller: bank, decoration: const InputDecoration(labelText: 'Banque')),
            TextField(controller: refDeposit, decoration: const InputDecoration(labelText: 'Référence dépôt')),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Annuler')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Enregistrer')),
        ],
      ),
    );

    if (ok == true && context.mounted) {
      try {
        await repo.recordRemittanceDeposit(
          remittanceId,
          slipNumber: slip.text,
          bankName: bank.text,
          depositReference: refDeposit.text,
          depositedAt: DateTime.now().toIso8601String(),
        );
        await refresh();
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur : $e')));
        }
      }
    }
  }

  Future<void> _showConfirmDialog(BuildContext context, FinancialGovernanceRepository repo, Future<void> Function() refresh) async {
    final receiptRef = TextEditingController();

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Confirmation Trésor'),
        content: TextField(
          controller: receiptRef,
          decoration: const InputDecoration(labelText: 'Référence reçu Trésor'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Annuler')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Confirmer')),
        ],
      ),
    );

    if (ok == true && context.mounted) {
      try {
        await repo.confirmRemittance(remittanceId, treasuryReceiptRef: receiptRef.text);
        await refresh();
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur : $e')));
        }
      }
    }
  }

  Future<void> _showRejectDialog(BuildContext context, FinancialGovernanceRepository repo, Future<void> Function() refresh) async {
    final reason = TextEditingController();

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Rejeter le reversement'),
        content: TextField(
          controller: reason,
          decoration: const InputDecoration(labelText: 'Motif (min. 10 caractères)'),
          maxLines: 3,
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Annuler')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Rejeter')),
        ],
      ),
    );

    if (ok == true && context.mounted) {
      try {
        await repo.rejectRemittance(remittanceId, reason: reason.text);
        await refresh();
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur : $e')));
        }
      }
    }
  }
}
