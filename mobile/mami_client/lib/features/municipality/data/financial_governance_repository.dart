import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';

class FinancialMissionModel {
  FinancialMissionModel({
    required this.id,
    required this.reference,
    required this.title,
    required this.status,
    required this.statusLabel,
    required this.workflowStatus,
    required this.workflowStatusLabel,
    required this.validFrom,
    required this.validUntil,
    this.agentName,
    this.zoneName,
    this.rejectionReason,
    this.notes,
  });

  factory FinancialMissionModel.fromJson(Map<String, dynamic> json) {
    final agent = json['agent'] as Map<String, dynamic>?;
    final zone = json['operational_zone'] as Map<String, dynamic>?;

    return FinancialMissionModel(
      id: json['id'] as int,
      reference: json['reference'] as String? ?? '',
      title: json['title'] as String? ?? '',
      status: json['status'] as String? ?? '',
      statusLabel: json['status_label'] as String? ?? '',
      workflowStatus: json['workflow_status'] as String? ?? json['status'] as String? ?? '',
      workflowStatusLabel: json['workflow_status_label'] as String? ?? json['status_label'] as String? ?? '',
      validFrom: json['valid_from'] as String? ?? '',
      validUntil: json['valid_until'] as String? ?? '',
      agentName: agent?['name'] as String?,
      zoneName: zone?['name'] as String?,
      rejectionReason: json['rejection_reason'] as String?,
      notes: json['notes'] as String?,
    );
  }

  final int id;
  final String reference;
  final String title;
  final String status;
  final String statusLabel;
  final String workflowStatus;
  final String workflowStatusLabel;
  final String validFrom;
  final String validUntil;
  final String? agentName;
  final String? zoneName;
  final String? rejectionReason;
  final String? notes;

  bool get isPendingController => workflowStatus == 'submitted';
  bool get isPendingDaf => workflowStatus == 'controller_review' || workflowStatus == 'daf_review';
  bool get isApproved => workflowStatus == 'approved';
  bool get isRejected => workflowStatus == 'rejected';
  bool get isDraft => workflowStatus == 'draft';
}

class FinancialMissionApprovalModel {
  FinancialMissionApprovalModel({
    required this.id,
    required this.action,
    required this.createdAt,
    this.performerName,
    this.comments,
    this.missionReference,
  });

  factory FinancialMissionApprovalModel.fromJson(Map<String, dynamic> json) {
    final performer = json['performer'] as Map<String, dynamic>?;
    final mission = json['mission'] as Map<String, dynamic>?;

    return FinancialMissionApprovalModel(
      id: json['id'] as int,
      action: json['action'] as String? ?? '',
      createdAt: json['created_at'] as String? ?? '',
      performerName: performer?['name'] as String?,
      comments: json['comments'] as String?,
      missionReference: mission?['reference'] as String?,
    );
  }

  final int id;
  final String action;
  final String createdAt;
  final String? performerName;
  final String? comments;
  final String? missionReference;
}

class DafDashboardModel {
  DafDashboardModel({
    required this.draftMissions,
    required this.pendingValidation,
    required this.approvedMissions,
    required this.rejectedMissions,
    required this.closedMissions,
    required this.openSessionsCount,
    required this.collectedTodayXaf,
    required this.pendingValidationAmountXaf,
    required this.remittanceDraftCount,
  });

  factory DafDashboardModel.fromJson(Map<String, dynamic> json) {
    final missions = json['missions'] as Map<String, dynamic>? ?? {};
    final validation = json['validation'] as Map<String, dynamic>? ?? missions;
    final cash = json['cash_supervision'] as Map<String, dynamic>? ?? {};
    final remittances = json['treasury_remittances'] as Map<String, dynamic>? ?? {};

    return DafDashboardModel(
      draftMissions: missions['draft_count'] as int? ?? 0,
      pendingValidation: validation['pending_count'] as int? ?? missions['pending_validation_count'] as int? ?? 0,
      approvedMissions: validation['approved_count'] as int? ?? missions['approved_count'] as int? ?? 0,
      rejectedMissions: validation['rejected_count'] as int? ?? missions['rejected_count'] as int? ?? 0,
      closedMissions: validation['closed_count'] as int? ?? missions['closed_count'] as int? ?? 0,
      openSessionsCount: cash['open_sessions_count'] as int? ?? 0,
      collectedTodayXaf: validation['collected_today_xaf']?.toString() ?? cash['collected_today_xaf']?.toString() ?? '0',
      pendingValidationAmountXaf: validation['pending_validation_amount_xaf']?.toString() ?? '0',
      remittanceDraftCount: remittances['draft_count'] as int? ?? 0,
    );
  }

  final int draftMissions;
  final int pendingValidation;
  final int approvedMissions;
  final int rejectedMissions;
  final int closedMissions;
  final int openSessionsCount;
  final String collectedTodayXaf;
  final String pendingValidationAmountXaf;
  final int remittanceDraftCount;
}

class OpenCashSessionSummary {
  OpenCashSessionSummary({
    required this.id,
    required this.reference,
    required this.agentName,
    required this.expectedAmountXaf,
    required this.openedAt,
  });

  factory OpenCashSessionSummary.fromJson(Map<String, dynamic> json) {
    return OpenCashSessionSummary(
      id: json['id'] as int,
      reference: json['reference'] as String? ?? '',
      agentName: json['agent_name'] as String? ?? '',
      expectedAmountXaf: json['expected_amount_xaf']?.toString() ?? '0',
      openedAt: json['opened_at'] as String? ?? '',
    );
  }

  final int id;
  final String reference;
  final String agentName;
  final String expectedAmountXaf;
  final String openedAt;
}

class FinancialGovernanceRepository {
  FinancialGovernanceRepository(this._dio);

  final Dio _dio;

  Future<DafDashboardModel> fetchDashboard() async {
    final response = await _dio.get('/municipality/finance/dashboard');
    final envelope = parseApiData(response.data);
    return DafDashboardModel.fromJson(envelope['data'] as Map<String, dynamic>);
  }

  Future<List<FinancialMissionModel>> fetchMissions({String? status, String? workflowStatus}) async {
    final response = await _dio.get(
      '/municipality/finance/missions',
      queryParameters: {
        if (status != null) 'status': status,
        if (workflowStatus != null) 'workflow_status': workflowStatus,
      },
    );
    final list = response.data['data'] as List<dynamic>;
    return list
        .map((e) => FinancialMissionModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<FinancialMissionModel> fetchMission(int id) async {
    final response = await _dio.get('/municipality/finance/missions/$id');
    final envelope = parseApiData(response.data);
    return FinancialMissionModel.fromJson(envelope['data'] as Map<String, dynamic>);
  }

  Future<FinancialMissionModel?> fetchCurrentMission() async {
    final response = await _dio.get('/municipality/finance/missions/current');
    final envelope = parseApiData(response.data);
    final data = envelope['data'];
    if (data == null) return null;
    return FinancialMissionModel.fromJson(data as Map<String, dynamic>);
  }

  Future<List<FinancialMissionModel>> fetchPendingApprovals() async {
    final response = await _dio.get('/municipality/finance/approvals/pending');
    final envelope = parseApiData(response.data);
    return (envelope['data'] as List<dynamic>)
        .map((e) => FinancialMissionModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<FinancialMissionApprovalModel>> fetchApprovalHistory({int? missionId}) async {
    final response = await _dio.get(
      '/municipality/finance/approvals/history',
      queryParameters: {if (missionId != null) 'mission_id': missionId},
    );
    final list = response.data['data'] as List<dynamic>;
    return list
        .map((e) => FinancialMissionApprovalModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<FinancialMissionApprovalModel>> fetchMissionWorkflowHistory(int missionId) async {
    final response = await _dio.get('/municipality/finance/workflow/$missionId/history');
    final envelope = parseApiData(response.data);
    return (envelope['data'] as List<dynamic>)
        .map((e) => FinancialMissionApprovalModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<FinancialMissionModel> authorizeMission(int missionId) async {
    final response = await _dio.post('/municipality/finance/missions/$missionId/authorize');
    final envelope = parseApiData(response.data);
    return FinancialMissionModel.fromJson(envelope['data'] as Map<String, dynamic>);
  }

  Future<FinancialMissionModel> submitMission(int missionId, {String? comments}) async {
    final response = await _dio.post(
      '/municipality/finance/workflow/$missionId/submit',
      data: {if (comments != null) 'comments': comments},
    );
    final envelope = parseApiData(response.data);
    return FinancialMissionModel.fromJson(envelope['data'] as Map<String, dynamic>);
  }

  Future<FinancialMissionModel> reviewMission(int missionId, {String? comments}) async {
    final response = await _dio.post(
      '/municipality/finance/workflow/$missionId/review',
      data: {if (comments != null) 'comments': comments},
    );
    final envelope = parseApiData(response.data);
    return FinancialMissionModel.fromJson(envelope['data'] as Map<String, dynamic>);
  }

  Future<FinancialMissionModel> approveMission(int missionId, {String? comments}) async {
    final response = await _dio.post(
      '/municipality/finance/workflow/$missionId/approve',
      data: {if (comments != null) 'comments': comments},
    );
    final envelope = parseApiData(response.data);
    return FinancialMissionModel.fromJson(envelope['data'] as Map<String, dynamic>);
  }

  Future<FinancialMissionModel> rejectMission(int missionId, {required String reason, String? comments}) async {
    final response = await _dio.post(
      '/municipality/finance/workflow/$missionId/reject',
      data: {
        'reason': reason,
        if (comments != null) 'comments': comments,
      },
    );
    final envelope = parseApiData(response.data);
    return FinancialMissionModel.fromJson(envelope['data'] as Map<String, dynamic>);
  }

  Future<FinancialMissionModel> closeMission(int missionId, {String? notes}) async {
    final response = await _dio.post(
      '/municipality/finance/workflow/$missionId/close',
      data: {if (notes != null) 'notes': notes},
    );
    final envelope = parseApiData(response.data);
    return FinancialMissionModel.fromJson(envelope['data'] as Map<String, dynamic>);
  }

  Future<List<OpenCashSessionSummary>> fetchOpenSessions() async {
    final response = await _dio.get(
      '/municipality/fiscal/cash-sessions',
      queryParameters: {'status': 'open'},
    );
    final list = response.data['data'] as List<dynamic>;
    return list.map((item) {
      final json = item as Map<String, dynamic>;
      final agent = json['agent'] as Map<String, dynamic>?;
      return OpenCashSessionSummary(
        id: json['id'] as int,
        reference: json['reference'] as String? ?? '',
        agentName: agent?['name'] as String? ?? '',
        expectedAmountXaf: json['expected_amount_xaf']?.toString() ?? '0',
        openedAt: json['opened_at'] as String? ?? '',
      );
    }).toList();
  }

  Future<void> adminCloseSession(int sessionId, {String? notes}) async {
    await _dio.post('/municipality/fiscal/cash-sessions/$sessionId/admin-close', data: {
      if (notes != null && notes.isNotEmpty) 'notes': notes,
    });
  }

  Future<List<Map<String, dynamic>>> fetchRemittances() async {
    final response = await _dio.get('/municipality/finance/remittances');
    final envelope = parseApiData(response.data);
    return (envelope['data'] as List<dynamic>).cast<Map<String, dynamic>>();
  }

  Future<List<Map<String, dynamic>>> fetchPendingRemittances() async {
    final response = await _dio.get('/municipality/finance/remittances/pending');
    final envelope = parseApiData(response.data);
    return (envelope['data'] as List<dynamic>).cast<Map<String, dynamic>>();
  }

  Future<Map<String, dynamic>> fetchRemittance(int id) async {
    final response = await _dio.get('/municipality/finance/remittances/$id');
    final envelope = parseApiData(response.data);
    return envelope['data'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> generateRemittanceFromPeriod({
    required String periodStart,
    required String periodEnd,
    String? notes,
  }) async {
    final response = await _dio.post('/municipality/finance/remittances/generate-from-period', data: {
      'period_start': periodStart,
      'period_end': periodEnd,
      if (notes != null) 'notes': notes,
    });
    final envelope = parseApiData(response.data);
    return envelope['data'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> controlRemittance(int id, {String? comments}) async {
    final response = await _dio.post('/municipality/finance/remittances/$id/submit-control', data: {
      if (comments != null) 'comments': comments,
    });
    final envelope = parseApiData(response.data);
    return envelope['data'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> validateRemittanceDaf(int id, {String? comments}) async {
    final response = await _dio.post('/municipality/finance/remittances/$id/validate-daf', data: {
      if (comments != null) 'comments': comments,
    });
    final envelope = parseApiData(response.data);
    return envelope['data'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> validateRemittanceReceveur(int id, {String? comments}) async {
    final response = await _dio.post('/municipality/finance/remittances/$id/validate-receveur', data: {
      if (comments != null) 'comments': comments,
    });
    final envelope = parseApiData(response.data);
    return envelope['data'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> recordRemittanceDeposit(
    int id, {
    required String slipNumber,
    required String bankName,
    required String depositReference,
    required String depositedAt,
    String? comments,
  }) async {
    final response = await _dio.post('/municipality/finance/remittances/$id/record-deposit', data: {
      'slip_number': slipNumber,
      'bank_name': bankName,
      'deposit_reference': depositReference,
      'deposited_at': depositedAt,
      if (comments != null) 'comments': comments,
    });
    final envelope = parseApiData(response.data);
    return envelope['data'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> confirmRemittance(int id, {required String treasuryReceiptRef, String? comments}) async {
    final response = await _dio.post('/municipality/finance/remittances/$id/confirm', data: {
      'treasury_receipt_ref': treasuryReceiptRef,
      if (comments != null) 'comments': comments,
    });
    final envelope = parseApiData(response.data);
    return envelope['data'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> rejectRemittance(int id, {required String reason, String? comments}) async {
    final response = await _dio.post('/municipality/finance/remittances/$id/reject', data: {
      'reason': reason,
      if (comments != null) 'comments': comments,
    });
    final envelope = parseApiData(response.data);
    return envelope['data'] as Map<String, dynamic>;
  }
}

final financialGovernanceRepositoryProvider = Provider<FinancialGovernanceRepository>(
  (ref) => FinancialGovernanceRepository(ref.watch(dioProvider)),
);
