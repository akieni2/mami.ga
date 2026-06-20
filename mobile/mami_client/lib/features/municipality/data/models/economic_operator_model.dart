import '../../../../core/json/json_decoders.dart';

class EconomicOperatorModel {
  const EconomicOperatorModel({
    required this.id,
    required this.publicId,
    required this.commercialName,
    required this.activityLabel,
    required this.categoryLabel,
    required this.responsibleName,
    required this.phone,
    this.email,
    required this.latitude,
    required this.longitude,
    this.quartier,
    this.operationalZone,
    this.economicZone,
    this.arrondissement,
    required this.taxStatusLabel,
    this.syncStatus,
    this.createdAt,
  });

  final int id;
  final String publicId;
  final String commercialName;
  final String activityLabel;
  final String categoryLabel;
  final String responsibleName;
  final String phone;
  final String? email;
  final double latitude;
  final double longitude;
  final String? quartier;
  final String? operationalZone;
  final String? economicZone;
  final String? arrondissement;
  final String taxStatusLabel;
  final String? syncStatus;
  final String? createdAt;

  factory EconomicOperatorModel.fromJson(Map<String, dynamic> json) {
    return EconomicOperatorModel(
      id: json['id'] as int,
      publicId: json['public_id'] as String,
      commercialName: json['commercial_name'] as String,
      activityLabel: json['activity_label'] as String,
      categoryLabel: json['category_label'] as String? ?? '',
      responsibleName: json['responsible_name'] as String,
      phone: json['phone'] as String,
      email: json['email'] as String?,
      latitude: readJsonDouble(json['latitude']),
      longitude: readJsonDouble(json['longitude']),
      quartier: json['quartier'] as String?,
      operationalZone: json['operational_zone'] as String?,
      economicZone: json['economic_zone'] as String?,
      arrondissement: json['arrondissement'] as String?,
      taxStatusLabel: json['tax_status_label'] as String? ?? '',
      syncStatus: json['sync_status'] as String?,
      createdAt: json['created_at'] as String?,
    );
  }
}

class EconomicOperatorDashboardModel {
  const EconomicOperatorDashboardModel({
    required this.registeredToday,
    required this.totalOperators,
    required this.coveragePercent,
  });

  final int registeredToday;
  final int totalOperators;
  final double coveragePercent;

  factory EconomicOperatorDashboardModel.fromJson(Map<String, dynamic> json) {
    final coverage = json['coverage'] as Map<String, dynamic>? ?? {};

    return EconomicOperatorDashboardModel(
      registeredToday: json['registered_today'] as int? ?? 0,
      totalOperators: json['total_operators'] as int? ?? 0,
      coveragePercent:
          readJsonDoubleOrNull(coverage['coverage_percent'], fallback: 0) ?? 0,
    );
  }
}
