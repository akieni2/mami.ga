import 'payment_method.dart';

class RideClient {
  const RideClient({required this.name, this.phone});

  final String name;
  final String? phone;

  factory RideClient.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const RideClient(name: 'Client');
    return RideClient(
      name: json['name'] as String? ?? 'Client',
      phone: json['phone'] as String?,
    );
  }
}

class RideModel {
  const RideModel({
    required this.id,
    required this.status,
    this.pickupLabel,
    this.destinationLabel,
    this.pickupLatitude,
    this.pickupLongitude,
    this.destinationLatitude,
    this.destinationLongitude,
    this.estimatedPrice,
    this.proposedPrice,
    this.agreedPrice,
    this.paymentMethod,
    this.distanceToPickupKm,
    this.client,
    this.createdAt,
  });

  final int id;
  final String status;
  final String? pickupLabel;
  final String? destinationLabel;
  final double? pickupLatitude;
  final double? pickupLongitude;
  final double? destinationLatitude;
  final double? destinationLongitude;
  final double? estimatedPrice;
  final double? proposedPrice;
  final double? agreedPrice;
  final RidePaymentMethod? paymentMethod;
  final double? distanceToPickupKm;
  final RideClient? client;
  final String? createdAt;

  bool get isPending => status == 'pending';
  bool get isSearching => status == 'searching';
  bool get isAccepted =>
      status == 'accepted' || status == 'arrived' || status == 'started';
  bool get isActive => !['completed', 'cancelled', 'expired'].contains(status);

  bool get hasPickupCoordinates =>
      pickupLatitude != null && pickupLongitude != null;

  bool get hasDestinationCoordinates =>
      destinationLatitude != null && destinationLongitude != null;

  String get pickupDisplay =>
      pickupLabel ??
      (hasPickupCoordinates
          ? '${pickupLatitude!.toStringAsFixed(4)}, ${pickupLongitude!.toStringAsFixed(4)}'
          : '—');

  String get destinationDisplay =>
      destinationLabel ??
      (hasDestinationCoordinates
          ? '${destinationLatitude!.toStringAsFixed(4)}, ${destinationLongitude!.toStringAsFixed(4)}'
          : '—');

  double? get displayPrice => agreedPrice ?? proposedPrice ?? estimatedPrice;

  factory RideModel.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      throw ArgumentError('Ride json is null');
    }

    return RideModel(
      id: (json['id'] as num).toInt(),
      status: json['status'] as String,
      pickupLabel: json['pickup_label'] as String?,
      destinationLabel: json['destination_label'] as String?,
      pickupLatitude: (json['pickup_latitude'] as num?)?.toDouble(),
      pickupLongitude: (json['pickup_longitude'] as num?)?.toDouble(),
      destinationLatitude: (json['destination_latitude'] as num?)?.toDouble(),
      destinationLongitude: (json['destination_longitude'] as num?)?.toDouble(),
      estimatedPrice: (json['estimated_price'] as num?)?.toDouble(),
      proposedPrice: (json['proposed_price'] as num?)?.toDouble(),
      agreedPrice: (json['agreed_price'] as num?)?.toDouble(),
      paymentMethod:
          RidePaymentMethod.fromApi(json['payment_method'] as String?),
      distanceToPickupKm: (json['distance_to_pickup_km'] as num?)?.toDouble(),
      client: RideClient.fromJson(json['client'] as Map<String, dynamic>?),
      createdAt: json['created_at'] as String?,
    );
  }
}
