import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../data/fiscal_collection_repository.dart';
import '../providers/fiscal_collection_providers.dart';
import '../widgets/qr_commerce_entry.dart';

class FiscalSummaryScreen extends ConsumerWidget {
  const FiscalSummaryScreen({super.key, this.operatorId});

  final int? operatorId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (operatorId != null) {
      return _FiscalSummaryBody(operatorId: operatorId!);
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Situation fiscale')),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: QrCommerceEntry(
          onManualFound: (id) => context.push('/municipality/recovery/fiscal-summary/$id'),
        ),
      ),
    );
  }
}

class _FiscalSummaryBody extends ConsumerStatefulWidget {
  const _FiscalSummaryBody({required this.operatorId});

  final int operatorId;

  @override
  ConsumerState<_FiscalSummaryBody> createState() => _FiscalSummaryBodyState();
}

class _FiscalSummaryBodyState extends ConsumerState<_FiscalSummaryBody> {
  final Set<int> _selectedIds = {};

  void _toggleReceivable(FiscalReceivableModel receivable, bool? selected) {
    setState(() {
      if (selected == true) {
        _selectedIds.add(receivable.id);
      } else {
        _selectedIds.remove(receivable.id);
      }
    });
  }

  double _selectedTotal(FiscalDetailedSummary summary) {
    return summary.allReceivables
        .where((item) => _selectedIds.contains(item.id))
        .fold<double>(0, (sum, item) => sum + (double.tryParse(item.balanceDue) ?? 0));
  }

  @override
  Widget build(BuildContext context) {
    final summaryAsync = ref.watch(fiscalDetailedSummaryProvider(widget.operatorId));

    return Scaffold(
      appBar: AppBar(title: const Text('Situation fiscale')),
      body: summaryAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (summary) {
          final selectedTotal = _selectedTotal(summary);

          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Text(summary.commercialName, style: Theme.of(context).textTheme.headlineSmall),
              Text('Réf. ${summary.publicId}'),
              Text('Activité : ${summary.activityLabel}'),
              Text('Quartier : ${summary.quartier}'),
              const Divider(height: 32),
              _BalanceCard(
                totalDue: summary.totalDue,
                totalPaid: summary.totalPaid,
                remainingBalance: summary.remainingBalance,
              ),
              const SizedBox(height: 16),
              _ReceivableSection(
                title: 'Taxes',
                items: summary.taxes,
                selectedIds: _selectedIds,
                onToggle: _toggleReceivable,
              ),
              _ReceivableSection(
                title: 'Pénalités',
                items: summary.penalties,
                selectedIds: _selectedIds,
                onToggle: _toggleReceivable,
              ),
              _ReceivableSection(
                title: 'Amendes',
                items: summary.fines,
                selectedIds: _selectedIds,
                onToggle: _toggleReceivable,
              ),
              if (summary.allReceivables.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 12),
                  child: Text('Aucune créance ouverte pour ce commerce.'),
                ),
              const SizedBox(height: 16),
              Text(
                'Total sélectionné : ${selectedTotal.toStringAsFixed(0)} XAF',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              const SizedBox(height: 12),
              FilledButton(
                onPressed: _selectedIds.isEmpty
                    ? null
                    : () {
                        final ids = _selectedIds.join(',');
                        context.push(
                          '/municipality/recovery/collect?operatorId=${widget.operatorId}&obligationIds=$ids',
                        );
                      },
                child: const Text('Encaisser la sélection'),
              ),
              const Divider(height: 32),
              const Text('Historique des paiements', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              if (summary.paymentHistory.isEmpty)
                const ListTile(title: Text('Aucun paiement enregistré'))
              else
                ...summary.paymentHistory.map(
                  (payment) => Card(
                    margin: const EdgeInsets.only(bottom: 8),
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '${payment.amountXaf} XAF',
                            style: const TextStyle(fontWeight: FontWeight.bold),
                          ),
                          Text('Date : ${payment.collectedAt}'),
                          Text('Réf. quittance : ${payment.receiptNumber.isEmpty ? '—' : payment.receiptNumber}'),
                          Text('Taxe : ${payment.taxConcerned.isEmpty ? '—' : payment.taxConcerned}'),
                          Text('Agent : ${payment.agentName.isEmpty ? '—' : payment.agentName}'),
                          Text('Mode : ${payment.paymentMethodLabel.isEmpty ? '—' : payment.paymentMethodLabel}'),
                        ],
                      ),
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

class _BalanceCard extends StatelessWidget {
  const _BalanceCard({
    required this.totalDue,
    required this.totalPaid,
    required this.remainingBalance,
  });

  final String totalDue;
  final String totalPaid;
  final String remainingBalance;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Montant dû : $totalDue XAF'),
            Text('Montant payé : $totalPaid XAF'),
            Text(
              'Reste à payer : $remainingBalance XAF',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ],
        ),
      ),
    );
  }
}

class _ReceivableSection extends StatelessWidget {
  const _ReceivableSection({
    required this.title,
    required this.items,
    required this.selectedIds,
    required this.onToggle,
  });

  final String title;
  final List<FiscalReceivableModel> items;
  final Set<int> selectedIds;
  final void Function(FiscalReceivableModel receivable, bool? selected) onToggle;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SizedBox(height: 8),
        Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
        ...items.map(
          (item) => CheckboxListTile(
            value: selectedIds.contains(item.id),
            onChanged: (value) => onToggle(item, value),
            title: Text(item.label),
            subtitle: Text('${item.reference} — solde ${item.balanceDue} XAF'),
            controlAffinity: ListTileControlAffinity.leading,
          ),
        ),
      ],
    );
  }
}

