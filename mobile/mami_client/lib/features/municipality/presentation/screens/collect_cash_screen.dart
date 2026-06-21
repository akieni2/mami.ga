import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/api_error_message.dart';
import '../../data/fiscal_collection_repository.dart';
import '../../domain/municipal_gps_service.dart';
import '../providers/fiscal_collection_providers.dart';
import '../providers/municipal_gps_provider.dart';
import '../widgets/qr_commerce_entry.dart';

class CollectCashScreen extends ConsumerStatefulWidget {
  const CollectCashScreen({
    super.key,
    this.operatorId,
    this.suggestedAmount,
    this.obligationIds = const [],
  });

  final int? operatorId;
  final String? suggestedAmount;
  final List<int> obligationIds;

  @override
  ConsumerState<CollectCashScreen> createState() => _CollectCashScreenState();
}

class _CollectCashScreenState extends ConsumerState<CollectCashScreen> {
  bool _loading = false;
  String? _error;
  String? _success;

  bool get _selectionMode =>
      widget.obligationIds.isNotEmpty && widget.operatorId != null;

  Future<void> _collect() async {
    final operatorId = widget.operatorId;
    if (operatorId == null) {
      setState(() => _error = 'Scannez le QR commerce pour encaisser');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
      _success = null;
    });

    try {
      final session = await ref.read(fiscalCollectionRepositoryProvider).fetchCurrentSession();
      if (session == null || !session.isOpen) {
        setState(() => _error = 'Ouvrez une session de caisse avant d\'encaisser');
        return;
      }

      final gps = ref.read(municipalGpsServiceProvider);
      final position = await gps.capturePosition();
      final repo = ref.read(fiscalCollectionRepositoryProvider);
      final payment = await repo.collectCash(
        operatorId: operatorId,
        obligationIds: widget.obligationIds,
        cashSessionId: session.id,
        latitude: position.latitude,
        longitude: position.longitude,
        gpsAccuracyM: position.accuracy,
      );

      ref.invalidate(currentCashSessionProvider);
      ref.invalidate(myCollectionsProvider);
      ref.invalidate(myReceiptsProvider);
      if (widget.operatorId != null) {
        ref.invalidate(fiscalDetailedSummaryProvider(widget.operatorId!));
      }
      setState(() => _success = 'Encaissement ${payment.amountXaf} XAF enregistré');

      final receipt = payment.receipt;
      if (receipt != null && mounted) {
        context.push(
          '/municipality/recovery/print-receipt/${receipt.id}',
          extra: receipt,
        );
      }
    } on MunicipalGpsException catch (e) {
      setState(() => _error = e.message);
    } on DioException catch (e) {
      setState(() => _error = resolveApiErrorMessage(e));
    } catch (e) {
      setState(() => _error = resolveApiErrorMessage(e));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_selectionMode) {
      return Scaffold(
        appBar: AppBar(title: const Text('Encaisser')),
        body: const Padding(
          padding: EdgeInsets.all(20),
          child: QrCommerceEntry(showManualFallback: false),
        ),
      );
    }

    final sessionAsync = ref.watch(currentCashSessionProvider);
    final summaryAsync = ref.watch(fiscalDetailedSummaryProvider(widget.operatorId!));

    final summary = summaryAsync.valueOrNull;
    final selectionTotal = summary == null
        ? 0.0
        : summary.allReceivables
            .where((item) => widget.obligationIds.contains(item.id))
            .fold<double>(0, (sum, item) => sum + (double.tryParse(item.balanceDue) ?? 0));

    return Scaffold(
      appBar: AppBar(title: const Text('Encaisser')),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            sessionAsync.when(
              data: (s) => Text(
                s?.isOpen == true
                    ? 'Session : ${s!.reference}'
                    : 'Aucune session ouverte',
              ),
              loading: () => const LinearProgressIndicator(),
              error: (_, __) => const SizedBox.shrink(),
            ),
            const SizedBox(height: 16),
            if (summary != null) ...[
              Text(summary.commercialName, style: Theme.of(context).textTheme.titleLarge),
              Text('Réf. ${summary.publicId}'),
              const SizedBox(height: 12),
            ],
            Text(
              'Créances sélectionnées (${widget.obligationIds.length})',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Text(
              'Montant sélectionné : ${selectionTotal.toStringAsFixed(0)} XAF',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
            ),
            if (summaryAsync.isLoading) ...[
              const SizedBox(height: 12),
              const LinearProgressIndicator(),
            ],
            if (_error != null) ...[
              const SizedBox(height: 12),
              Text(_error!, style: const TextStyle(color: Colors.red)),
            ],
            if (_success != null) ...[
              const SizedBox(height: 12),
              Text(_success!, style: const TextStyle(color: Colors.green)),
            ],
            const Spacer(),
            FilledButton(
              onPressed: _loading ? null : _collect,
              child: _loading
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Encaisser'),
            ),
          ],
        ),
      ),
    );
  }
}
