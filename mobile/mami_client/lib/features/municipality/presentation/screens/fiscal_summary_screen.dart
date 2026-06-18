import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../providers/fiscal_collection_providers.dart';

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
        child: _OperatorIdLookup(
          onFound: (id) => context.push('/municipality/recovery/fiscal-summary/$id'),
        ),
      ),
    );
  }
}

class _FiscalSummaryBody extends ConsumerWidget {
  const _FiscalSummaryBody({required this.operatorId});

  final int operatorId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final summaryAsync = ref.watch(fiscalSummaryProvider(operatorId));

    return Scaffold(
      appBar: AppBar(title: const Text('Situation fiscale')),
      body: summaryAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (summary) => ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Text(summary.commercialName, style: Theme.of(context).textTheme.headlineSmall),
            Text('Réf. ${summary.publicId}'),
            Text('Activité : ${summary.activityLabel}'),
            Text('Quartier : ${summary.quartier}'),
            const Divider(height: 32),
            Text('Montant dû : ${summary.amountDue} XAF'),
            Text('Montant payé : ${summary.amountPaid} XAF'),
            Text(
              'Solde restant : ${summary.balanceRemaining} XAF',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            const Text('Obligations ouvertes', style: TextStyle(fontWeight: FontWeight.bold)),
            ...summary.obligations.map(
              (o) => ListTile(
                title: Text('${o.taxCode} — ${o.reference}'),
                subtitle: Text('Solde : ${o.balanceDue} XAF'),
                trailing: Text(o.status),
              ),
            ),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: () => context.push(
                '/municipality/recovery/collect?operatorId=$operatorId&balance=${summary.balanceRemaining}',
              ),
              child: const Text('Encaisser'),
            ),
          ],
        ),
      ),
    );
  }
}

class _OperatorIdLookup extends StatefulWidget {
  const _OperatorIdLookup({required this.onFound});

  final ValueChanged<int> onFound;

  @override
  State<_OperatorIdLookup> createState() => _OperatorIdLookupState();
}

class _OperatorIdLookupState extends State<_OperatorIdLookup> {
  final _controller = TextEditingController();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const Text('Identifiant interne de l\'opérateur (après scan QR)'),
        const SizedBox(height: 12),
        TextField(
          controller: _controller,
          keyboardType: TextInputType.number,
          decoration: const InputDecoration(
            labelText: 'ID opérateur',
            border: OutlineInputBorder(),
          ),
        ),
        const SizedBox(height: 16),
        FilledButton(
          onPressed: () {
            final id = int.tryParse(_controller.text.trim());
            if (id != null) widget.onFound(id);
          },
          child: const Text('Consulter'),
        ),
      ],
    );
  }
}
