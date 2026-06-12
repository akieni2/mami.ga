import 'payment_method.dart';

class RideDriverInfo {
  const RideDriverInfo({
    required this.name,
    this.phone,
    this.brand,
    this.model,
    this.plateNumber,
    this.color,
    this.latitude,
    this.longitude,
    this.rating,
  });

  final String name;
  final String? phone;
  final String? brand;
  final String? model;
  final String? plateNumber;
  final String? color;
  final double? latitude;
  final double? longitude;
  final double? rating;

  String get vehicleLabel {
    final parts = <String>[];
    if (brand != null) parts.add(brand!);
    if (model != null) parts.add(model!);
    if (plateNumber != null) parts.add('($plateNumber)');
    return parts.isEmpty ? 'Véhicule' : parts.join(' ');
  }

  factory RideDriverInfo.fromJson(dynamic json) {
    if (json is! Map<String, dynamic>) {
      return const RideDriverInfo(name: 'Chauffeur');
    }

    final user = json['user'] as Map<String, dynamic>?;
    final vehicle = json['vehicle'] as Map<String, dynamic>?;

    return RideDriverInfo(
      name: user?['name'] as String? ?? 'Chauffeur',
      phone: user?['phone'] as String?,
      brand: vehicle?['brand'] as String?,
      model: vehicle?['model'] as String?,
      plateNumber: vehicle?['plate_number'] as String?,
      color: vehicle?['color'] as String?,
      latitude: (json['latitude'] as num?)?.toDouble(),
      longitude: (json['longitude'] as num?)?.toDouble(),
      rating: (json['rating'] as num?)?.toDouble(),
    );
  }
}

class RideModel {
  const RideModel({
    required this.id,
    required this.status,
    this.pickupLabel,
    this.destinationLabel,
    this.pickupSource,
    this.destinationSource,
    this.pickupLatitude,
    this.pickupLongitude,
    this.destinationLatitude,
    this.destinationLongitude,
    this.estimatedPrice,
    this.suggestedPrice,
    this.proposedPrice,
    this.paymentMethod,
    this.driver,
    this.createdAt,
  });

  final int id;
  final String status;
  final String? pickupLabel;
  final String? destinationLabel;
  final String? pickupSource;
  final String? destinationSource;
  final double? pickupLatitude;
  final double? pickupLongitude;
  final double? destinationLatitude;
  final double? destinationLongitude;
  final double? estimatedPrice;
  final double? suggestedPrice;
  final double? proposedPrice;
  final RidePaymentMethod? paymentMethod;
  final RideDriverInfo? driver;
  final String? createdAt;

  bool get isPending => status == 'pending';
  bool get isSearching => status == 'searching';
  bool get isExpired => status == 'expired';
  bool get isAccepted =>
      status == 'accepted' || status == 'arrived' || status == 'started';
  bool get isActive =>
      !['completed', 'cancelled', 'expired'].contains(status);
  bool get isCompleted => status == 'completed';

  bool get hasPickupCoordinates =>
      pickupLatitude != null && pickupLongitude != null;

  bool get hasDestinationCoordinates =>
      destinationLatitude != null && destinationLongitude != null;

  String get pickupDisplay =>
      pickupLabel ?? (hasPickupCoordinates
          ? '${pickupLatitude!.toStringAsFixed(4)}, ${pickupLongitude!.toStringAsFixed(4)}'
          : '—');

  String get destinationDisplay =>
      destinationLabel ?? (hasDestinationCoordinates
          ? '${destinationLatitude!.toStringAsFixed(4)}, ${destinationLongitude!.toStringAsFixed(4)}'
          : '—');

  factory RideModel.fromJson(Map<String, dynamic> json) {
    final paymentRaw = json['payment_method'] as String?;

    return RideModel(
      id: json['id'] as int,
      status: json['status'] as String,
      pickupLabel: json['pickup_label'] as String?,
      destinationLabel: json['destination_label'] as String?,
      pickupSource: json['pickup_source'] as String?,
      destinationSource: json['destination_source'] as String?,
      pickupLatitude: (json['pickup_latitude'] as num?)?.toDouble(),
      pickupLongitude: (json['pickup_longitude'] as num?)?.toDouble(),
      destinationLatitude: (json['destination_latitude'] as num?)?.toDouble(),
      destinationLongitude: (json['destination_longitude'] as num?)?.toDouble(),
      estimatedPrice: (json['estimated_price'] as num?)?.toDouble(),
      suggestedPrice: (json['suggested_price'] as num?)?.toDouble(),
      proposedPrice: (json['proposed_price'] as num?)?.toDouble(),
      paymentMethod: paymentRaw != null
          ? RidePaymentMethod.fromApi(paymentRaw)
          : null,
      driver: json['driver'] != null
          ? RideDriverInfo.fromJson(json['driver'])
          : null,
      createdAt: json['created_at'] as String?,
    );
  }
}
