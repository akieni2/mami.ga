import '../../../../core/json/json_decoders.dart';

class MunicipalityReportModel {
  const MunicipalityReportModel({
    required this.id,
    required this.reference,
    required this.category,
    required this.categoryLabel,
    required this.title,
    required this.description,
    required this.latitude,
    required this.longitude,
    required this.status,
    required this.statusLabel,
    required this.statusColor,
    this.photoUrl,
    this.createdAt,
  });

  final int id;
  final String reference;
  final String category;
  final String categoryLabel;
  final String title;
  final String description;
  final double latitude;
  final double longitude;
  final String status;
  final String statusLabel;
  final String statusColor;
  final String? photoUrl;
  final String? createdAt;

  factory MunicipalityReportModel.fromJson(Map<String, dynamic> json) {
    return MunicipalityReportModel(
      id: json['id'] as int,
      reference: json['reference'] as String,
      category: json['category'] as String,
      categoryLabel: json['category_label'] as String? ?? json['category'] as String,
      title: json['title'] as String,
      description: json['description'] as String,
      latitude: readJsonDouble(json['latitude']),
      longitude: readJsonDouble(json['longitude']),
      status: json['status'] as String,
      statusLabel: json['status_label'] as String? ?? json['status'] as String,
      statusColor: json['status_color'] as String? ?? '#E53935',
      photoUrl: json['photo_url'] as String?,
      createdAt: json['created_at'] as String?,
    );
  }
}
