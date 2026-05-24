class DriverModel {
  const DriverModel({
    required this.id,
    required this.licenseNumber,
    required this.isAvailable,
    required this.status,
    required this.presence,
    this.latitude,
    this.longitude,
    this.rating,
    this.vehicleLabel,
  });

  final int id;
  final String licenseNumber;
  final bool isAvailable;
  final String status;
  final String presence;
  final double? latitude;
  final double? longitude;
  final double? rating;
  final String? vehicleLabel;

  factory DriverModel.fromJson(Map<String, dynamic> json) {
    return DriverModel(
      id: json['id'] as int,
      licenseNumber: json['license_number'] as String? ?? '',
      isAvailable: json['is_available'] == true,
      status: json['status']?.toString() ?? 'offline',
      presence: json['presence']?.toString() ?? json['status']?.toString() ?? 'offline',
      latitude: (json['latitude'] as num?)?.toDouble(),
      longitude: (json['longitude'] as num?)?.toDouble(),
      rating: (json['rating'] as num?)?.toDouble(),
      vehicleLabel: json['vehicle'] != null
          ? _vehicleLabel(json['vehicle'])
          : null,
    );
  }

  static String? _vehicleLabel(dynamic vehicle) {
    if (vehicle is Map) {
      final brand = vehicle['brand'];
      final model = vehicle['model'];
      final plate = vehicle['plate_number'];
      return '$brand $model ($plate)';
    }
    return null;
  }

  DriverModel copyWith({
    bool? isAvailable,
    String? status,
    String? presence,
    double? latitude,
    double? longitude,
  }) {
    return DriverModel(
      id: id,
      licenseNumber: licenseNumber,
      isAvailable: isAvailable ?? this.isAvailable,
      status: status ?? this.status,
      presence: presence ?? this.presence,
      latitude: latitude ?? this.latitude,
      longitude: longitude ?? this.longitude,
      rating: rating,
      vehicleLabel: vehicleLabel,
    );
  }
}
