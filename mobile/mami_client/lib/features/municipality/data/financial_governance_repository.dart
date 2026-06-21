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
    required this.validFrom,
    required this.validUntil,
    this.agentName,
    this.zoneName,
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
      validFrom: json['valid_from'] as String? ?? '',
      validUntil: json['valid_until'] as String? ?? '',
      agentName: agent?['name'] as String?,
      zoneName: zone?['name'] as String?,
    );
  }

  final int id;
  final String reference;
  final String title;
  final String status;
  final String statusLabel;
  final String validFrom;
  final String validUntil;
  final String? agentName;
  final String? zoneName;
}

class DafDashboardModel {
  DafDashboardModel({
    required this.draftMissions,
    required this.authorizedMissions,
    required this.openSessionsCount,
    required this.collectedTodayXaf,
    required this.remittanceDraftCount,
  });

  factory DafDashboardModel.fromJson(Map<String, dynamic> json) {
    final missions = json['missions'] as Map<String, dynamic>;
    final cash = json['cash_supervision'] as Map<String, dynamic>;
    final remittances = json['treasury_remittances'] as Map<String, dynamic>;

    return DafDashboardModel(
      draftMissions: missions['draft_count'] as int? ?? 0,
      authorizedMissions: missions['authorized_count'] as int? ?? 0,
      openSessionsCount: cash['open_sessions_count'] as int? ?? 0,
      collectedTodayXaf: cash['collected_today_xaf']?.toString() ?? '0',
      remittanceDraftCount: remittances['draft_count'] as int? ?? 0,
    );
  }

  final int draftMissions;
  final int authorizedMissions;
  final int openSessionsCount;
  final String collectedTodayXaf;
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

  Future<List<FinancialMissionModel>> fetchMissions({String? status}) async {
    final response = await _dio.get(
      '/municipality/finance/missions',
      queryParameters: {if (status != null) 'status': status},
    );
    final list = response.data['data'] as List<dynamic>;
    return list
        .map((e) => FinancialMissionModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<FinancialMissionModel?> fetchCurrentMission() async {
    final response = await _dio.get('/municipality/finance/missions/current');
    final envelope = parseApiData(response.data);
    final data = envelope['data'];
    if (data == null) return null;
    return FinancialMissionModel.fromJson(data as Map<String, dynamic>);
  }

  Future<FinancialMissionModel> authorizeMission(int missionId) async {
    final response = await _dio.post('/municipality/finance/missions/$missionId/authorize');
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
}

final financialGovernanceRepositoryProvider = Provider<FinancialGovernanceRepository>(
  (ref) => FinancialGovernanceRepository(ref.watch(dioProvider)),
);
