import '../../../driver/domain/models/driver_model.dart';

class UserModel {
  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    required this.isDriver,
    this.driver,
  });

  final int id;
  final String name;
  final String email;
  final String? phone;
  final bool isDriver;
  final DriverModel? driver;

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      phone: json['phone'] as String?,
      isDriver: json['is_driver'] == true,
      driver: json['driver'] != null
          ? DriverModel.fromJson(json['driver'] as Map<String, dynamic>)
          : null,
    );
  }

  UserModel copyWith({DriverModel? driver}) {
    return UserModel(
      id: id,
      name: name,
      email: email,
      phone: phone,
      isDriver: isDriver,
      driver: driver ?? this.driver,
    );
  }
}
