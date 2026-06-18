import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/fiscal_collection_repository.dart';
import '../../data/models/municipal_receipt_model.dart';
import '../../printing/bluetooth_printer_adapter.dart';
import '../../printing/printer_service.dart';

final bluetoothPrinterAdapterProvider = Provider<BluetoothPrinterAdapter>(
  (ref) => BluetoothPrinterAdapter(),
);

final printerServiceProvider = Provider<PrinterService>((ref) {
  return PrinterService(ref.watch(bluetoothPrinterAdapterProvider));
});

final myReceiptsProvider = FutureProvider<List<MunicipalReceiptModel>>((ref) async {
  final repo = ref.watch(fiscalCollectionRepositoryProvider);
  return repo.fetchMyReceipts();
});

final receiptDetailProvider =
    FutureProvider.family<MunicipalReceiptModel, int>((ref, receiptId) async {
  final repo = ref.watch(fiscalCollectionRepositoryProvider);
  return repo.fetchReceipt(receiptId);
});

final currentCashSessionProvider = FutureProvider<CashSessionModel?>((ref) async {
  final repo = ref.watch(fiscalCollectionRepositoryProvider);
  return repo.fetchCurrentSession();
});

final myCollectionsProvider = FutureProvider<List<MunicipalCollectionModel>>((ref) async {
  final repo = ref.watch(fiscalCollectionRepositoryProvider);
  return repo.fetchMyCollections();
});

final fiscalSummaryProvider =
    FutureProvider.family<FiscalOperatorSummary, int>((ref, operatorId) async {
  final repo = ref.watch(fiscalCollectionRepositoryProvider);
  return repo.fetchOperatorSummary(operatorId);
});
