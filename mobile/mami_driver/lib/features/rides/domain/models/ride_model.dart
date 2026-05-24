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
    required this.pickupLatitude,
    required this.pickupLongitude,
    required this.destinationLatitude,
    required this.destinationLongitude,
    this.estimatedPrice,
    this.distanceToPickupKm,
    this.client,
    this.createdAt,
  });

  final int id;
  final String status;
  final double pickupLatitude;
  final double pickupLongitude;
  final double destinationLatitude;
  final double destinationLongitude;
  final double? estimatedPrice;
  final double? distanceToPickupKm;
  final RideClient? client;
  final String? createdAt;

  bool get isPending => status == 'pending';
  bool get isActive => !['completed', 'cancelled'].contains(status);

  factory RideModel.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      throw ArgumentError('Ride json is null');
    }

    return RideModel(
      id: json['id'] as int,
      status: json['status'] as String,
      pickupLatitude: (json['pickup_latitude'] as num).toDouble(),
      pickupLongitude: (json['pickup_longitude'] as num).toDouble(),
      destinationLatitude: (json['destination_latitude'] as num).toDouble(),
      destinationLongitude: (json['destination_longitude'] as num).toDouble(),
      estimatedPrice: (json['estimated_price'] as num?)?.toDouble(),
      distanceToPickupKm: (json['distance_to_pickup_km'] as num?)?.toDouble(),
      client: RideClient.fromJson(json['client'] as Map<String, dynamic>?),
      createdAt: json['created_at'] as String?,
    );
  }
}
