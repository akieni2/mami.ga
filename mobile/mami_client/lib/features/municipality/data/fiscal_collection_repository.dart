import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../data/models/municipal_receipt_model.dart';

class FiscalReceivableModel {
  FiscalReceivableModel({
    required this.id,
    required this.reference,
    required this.label,
    required this.taxCode,
    required this.taxName,
    required this.periodLabel,
    required this.balanceDue,
    required this.status,
    required this.obligationType,
  });

  factory FiscalReceivableModel.fromJson(Map<String, dynamic> json) {
    return FiscalReceivableModel(
      id: json['id'] as int,
      reference: json['reference'] as String? ?? '',
      label: json['label'] as String? ?? '',
      taxCode: json['tax_code'] as String? ?? '',
      taxName: json['tax_name'] as String? ?? '',
      periodLabel: json['period_label'] as String? ?? '',
      balanceDue: json['balance_due']?.toString() ?? '0',
      status: json['status'] as String? ?? '',
      obligationType: json['obligation_type'] as String? ?? 'tax',
    );
  }

  final int id;
  final String reference;
  final String label;
  final String taxCode;
  final String taxName;
  final String periodLabel;
  final String balanceDue;
  final String status;
  final String obligationType;
}

class FiscalPaymentHistoryModel {
  FiscalPaymentHistoryModel({
    required this.id,
    required this.collectedAt,
    required this.amountXaf,
    required this.agentName,
    required this.sessionReference,
    required this.receiptNumber,
    required this.paymentMethodLabel,
    required this.taxConcerned,
  });

  factory FiscalPaymentHistoryModel.fromJson(Map<String, dynamic> json) {
    return FiscalPaymentHistoryModel(
      id: json['id'] as int,
      collectedAt: json['collected_at'] as String? ?? '',
      amountXaf: json['amount_xaf']?.toString() ?? '0',
      agentName: json['agent_name'] as String? ?? '',
      sessionReference: json['cash_session_reference'] as String? ?? '',
      receiptNumber: json['receipt_number'] as String? ?? '',
      paymentMethodLabel: json['payment_method_label'] as String? ?? '',
      taxConcerned: json['tax_concerned'] as String? ?? '',
    );
  }

  final int id;
  final String collectedAt;
  final String amountXaf;
  final String agentName;
  final String sessionReference;
  final String receiptNumber;
  final String paymentMethodLabel;
  final String taxConcerned;
}

class FiscalDetailedSummary {
  FiscalDetailedSummary({
    required this.operatorId,
    required this.publicId,
    required this.commercialName,
    required this.activityLabel,
    required this.quartier,
    required this.taxes,
    required this.penalties,
    required this.fines,
    required this.totalDue,
    required this.totalPaid,
    required this.remainingBalance,
    required this.paymentHistory,
  });

  factory FiscalDetailedSummary.fromJson(Map<String, dynamic> json) {
    final operator = json['operator'] as Map<String, dynamic>;
    List<FiscalReceivableModel> parseList(String key) {
      return (json[key] as List<dynamic>? ?? [])
          .map((e) => FiscalReceivableModel.fromJson(e as Map<String, dynamic>))
          .toList();
    }

    return FiscalDetailedSummary(
      operatorId: operator['id'] as int,
      publicId: operator['public_id'] as String? ?? '',
      commercialName: operator['commercial_name'] as String? ?? '',
      activityLabel: operator['activity_label'] as String? ?? '',
      quartier: operator['quartier'] as String? ?? '',
      taxes: parseList('taxes'),
      penalties: parseList('penalties'),
      fines: parseList('fines'),
      totalDue: json['total_due']?.toString() ?? '0',
      totalPaid: json['total_paid']?.toString() ?? '0',
      remainingBalance: json['remaining_balance']?.toString() ?? '0',
      paymentHistory: (json['payment_history'] as List<dynamic>? ?? [])
          .map((e) => FiscalPaymentHistoryModel.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  final int operatorId;
  final String publicId;
  final String commercialName;
  final String activityLabel;
  final String quartier;
  final List<FiscalReceivableModel> taxes;
  final List<FiscalReceivableModel> penalties;
  final List<FiscalReceivableModel> fines;
  final String totalDue;
  final String totalPaid;
  final String remainingBalance;
  final List<FiscalPaymentHistoryModel> paymentHistory;

  List<FiscalReceivableModel> get allReceivables => [...taxes, ...penalties, ...fines];
}

class FiscalOperatorSummary {
  FiscalOperatorSummary({
    required this.operatorId,
    required this.publicId,
    required this.commercialName,
    required this.activityLabel,
    required this.quartier,
    required this.amountDue,
    required this.amountPaid,
    required this.balanceRemaining,
    required this.obligations,
  });

  factory FiscalOperatorSummary.fromJson(Map<String, dynamic> json) {
    final operator = json['operator'] as Map<String, dynamic>;
    final totals = json['totals'] as Map<String, dynamic>;
    final obligations = (json['obligations'] as List<dynamic>? ?? [])
        .map((e) => FiscalObligationSummary.fromJson(e as Map<String, dynamic>))
        .toList();

    return FiscalOperatorSummary(
      operatorId: operator['id'] as int,
      publicId: operator['public_id'] as String? ?? '',
      commercialName: operator['commercial_name'] as String? ?? '',
      activityLabel: operator['activity_label'] as String? ?? '',
      quartier: operator['quartier'] as String? ?? '',
      amountDue: totals['amount_due']?.toString() ?? '0',
      amountPaid: totals['amount_paid']?.toString() ?? '0',
      balanceRemaining: totals['balance_remaining']?.toString() ?? '0',
      obligations: obligations,
    );
  }

  final int operatorId;
  final String publicId;
  final String commercialName;
  final String activityLabel;
  final String quartier;
  final String amountDue;
  final String amountPaid;
  final String balanceRemaining;
  final List<FiscalObligationSummary> obligations;
}

class FiscalObligationSummary {
  FiscalObligationSummary({
    required this.reference,
    required this.taxCode,
    required this.balanceDue,
    required this.status,
  });

  factory FiscalObligationSummary.fromJson(Map<String, dynamic> json) {
    return FiscalObligationSummary(
      reference: json['reference'] as String? ?? '',
      taxCode: json['tax_code'] as String? ?? '',
      balanceDue: json['balance_due']?.toString() ?? '0',
      status: json['status'] as String? ?? '',
    );
  }

  final String reference;
  final String taxCode;
  final String balanceDue;
  final String status;
}

class CashSessionModel {
  CashSessionModel({
    required this.id,
    required this.reference,
    required this.status,
    required this.openingAmountXaf,
    required this.expectedAmountXaf,
  });

  factory CashSessionModel.fromJson(Map<String, dynamic> json) {
    return CashSessionModel(
      id: json['id'] as int,
      reference: json['reference'] as String? ?? '',
      status: json['status'] as String? ?? '',
      openingAmountXaf: json['opening_amount_xaf']?.toString() ?? '0',
      expectedAmountXaf: json['expected_amount_xaf']?.toString() ?? '0',
    );
  }

  final int id;
  final String reference;
  final String status;
  final String openingAmountXaf;
  final String expectedAmountXaf;

  bool get isOpen => status == 'open';
}

class MunicipalCollectionModel {
  MunicipalCollectionModel({
    required this.id,
    required this.amountXaf,
    required this.collectedAt,
    required this.operatorName,
    required this.sessionReference,
    this.receipt,
  });

  factory MunicipalCollectionModel.fromJson(Map<String, dynamic> json) {
    final operator = json['operator'] as Map<String, dynamic>?;
    final session = json['cash_session'] as Map<String, dynamic>?;
    final receiptJson = json['receipt'] as Map<String, dynamic>?;

    return MunicipalCollectionModel(
      id: json['id'] as int,
      amountXaf: json['amount_xaf']?.toString() ?? '0',
      collectedAt: json['collected_at'] as String? ?? '',
      operatorName: operator?['commercial_name'] as String? ?? '',
      sessionReference: session?['reference'] as String? ?? '',
      receipt: receiptJson != null ? MunicipalReceiptModel.fromJson(receiptJson) : null,
    );
  }

  final int id;
  final String amountXaf;
  final String collectedAt;
  final String operatorName;
  final String sessionReference;
  final MunicipalReceiptModel? receipt;
}

class FiscalCollectionRepository {
  FiscalCollectionRepository(this._dio);

  final Dio _dio;

  Future<CashSessionModel?> fetchCurrentSession() async {
    final response = await _dio.get('/municipality/fiscal/cash-sessions/current');
    final envelope = parseApiData(response.data);
    final data = envelope['data'];
    if (data == null) return null;
    return CashSessionModel.fromJson(data as Map<String, dynamic>);
  }

  Future<CashSessionModel> openSession({
    required double openingAmountXaf,
    required double latitude,
    required double longitude,
    double? gpsAccuracyM,
    String? deviceId,
  }) async {
    final response = await _dio.post('/municipality/fiscal/cash-sessions/open', data: {
      'opening_amount_xaf': openingAmountXaf,
      'latitude': latitude,
      'longitude': longitude,
      if (gpsAccuracyM != null) 'gps_accuracy_m': gpsAccuracyM,
      if (deviceId != null) 'device_id': deviceId,
    });

    final data = response.data['data'] as Map<String, dynamic>;
    return CashSessionModel.fromJson(data);
  }

  Future<CashSessionModel> closeSession({
    required int sessionId,
    required double actualAmountXaf,
    double? latitude,
    double? longitude,
  }) async {
    final response = await _dio.post(
      '/municipality/fiscal/cash-sessions/$sessionId/close',
      data: {
        'actual_amount_xaf': actualAmountXaf,
        if (latitude != null) 'latitude': latitude,
        if (longitude != null) 'longitude': longitude,
      },
    );

    final data = response.data['data'] as Map<String, dynamic>;
    return CashSessionModel.fromJson(data);
  }

  Future<FiscalOperatorSummary> fetchOperatorSummary(int operatorId) async {
    final response = await _dio.get('/municipality/fiscal/operator/$operatorId/summary');
    final envelope = parseApiData(response.data);
    return FiscalOperatorSummary.fromJson(envelope['data'] as Map<String, dynamic>);
  }

  Future<FiscalDetailedSummary> fetchDetailedFiscalSummary(int operatorId) async {
    final response = await _dio.get('/municipality/operators/$operatorId/fiscal-summary');
    final envelope = parseApiData(response.data);
    return FiscalDetailedSummary.fromJson(envelope['data'] as Map<String, dynamic>);
  }

  Future<Map<String, dynamic>> lookupOperatorByQr(String qrValue) async {
    final response = await _dio.get('/municipality/operators/by-qr/$qrValue');
    final envelope = parseApiData(response.data);
    return envelope['data'] as Map<String, dynamic>;
  }

  Future<MunicipalCollectionModel> collectCash({
    required int operatorId,
    required int cashSessionId,
    required double latitude,
    required double longitude,
    required double gpsAccuracyM,
    double? amountXaf,
    List<int>? obligationIds,
    String? notes,
  }) async {
    final response = await _dio.post('/municipality/fiscal/collections', data: {
      'operator_id': operatorId,
      if (amountXaf != null) 'amount_xaf': amountXaf,
      if (obligationIds != null && obligationIds.isNotEmpty) 'obligation_ids': obligationIds,
      'cash_session_id': cashSessionId,
      'latitude': latitude,
      'longitude': longitude,
      'gps_accuracy_m': gpsAccuracyM,
      if (notes != null && notes.isNotEmpty) 'notes': notes,
    });

    final data = response.data['data'] as Map<String, dynamic>;
    return MunicipalCollectionModel.fromJson(data);
  }

  Future<List<MunicipalCollectionModel>> fetchMyCollections() async {
    final response = await _dio.get('/municipality/fiscal/collections');
    final list = response.data['data'] as List<dynamic>;
    return list
        .map((e) => MunicipalCollectionModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<MunicipalReceiptModel>> fetchMyReceipts() async {
    final response = await _dio.get('/municipality/fiscal/receipts');
    final list = response.data['data'] as List<dynamic>;
    return list
        .map((e) => MunicipalReceiptModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<MunicipalReceiptModel> fetchReceipt(int receiptId) async {
    final response = await _dio.get('/municipality/fiscal/receipts/$receiptId');
    final data = response.data['data'] as Map<String, dynamic>;
    return MunicipalReceiptModel.fromJson(data);
  }

  Future<MunicipalReceiptModel> reprintReceipt(int receiptId) async {
    final response = await _dio.post('/municipality/fiscal/receipts/$receiptId/reprint');
    final data = response.data['data'] as Map<String, dynamic>;
    return MunicipalReceiptModel.fromJson(data);
  }

  Future<void> recordFieldVisit({
    required int operatorId,
    required String visitType,
    required double latitude,
    required double longitude,
    String? notes,
  }) async {
    await _dio.post('/municipality/operators/$operatorId/field-visits', data: {
      'visit_type': visitType,
      'latitude': latitude,
      'longitude': longitude,
      if (notes != null && notes.isNotEmpty) 'notes': notes,
    });
  }

  Future<MunicipalSyncStatusModel> fetchSyncStatus() async {
    final response = await _dio.get('/municipality/sync/status');
    final envelope = parseApiData(response.data);
    return MunicipalSyncStatusModel.fromJson(envelope['data'] as Map<String, dynamic>);
  }
}

class MunicipalSyncStatusModel {
  MunicipalSyncStatusModel({
    required this.serverTime,
    required this.apiStatus,
    required this.operatorsCount,
    required this.paymentsCount,
    required this.receiptsCount,
  });

  factory MunicipalSyncStatusModel.fromJson(Map<String, dynamic> json) {
    return MunicipalSyncStatusModel(
      serverTime: json['server_time'] as String? ?? '',
      apiStatus: json['api_status'] as String? ?? 'unknown',
      operatorsCount: json['operators_count'] as int? ?? 0,
      paymentsCount: json['payments_count'] as int? ?? 0,
      receiptsCount: json['receipts_count'] as int? ?? 0,
    );
  }

  final String serverTime;
  final String apiStatus;
  final int operatorsCount;
  final int paymentsCount;
  final int receiptsCount;

  bool get isApiOk => apiStatus == 'ok';
}

final fiscalCollectionRepositoryProvider = Provider<FiscalCollectionRepository>(
  (ref) => FiscalCollectionRepository(ref.watch(dioProvider)),
);
