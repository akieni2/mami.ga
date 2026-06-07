class UserModel {
  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    required this.isDriver,
  });

  final int id;
  final String name;
  final String email;
  final String? phone;
  final bool isDriver;

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      phone: json['phone'] as String?,
      isDriver: json['is_driver'] == true,
    );
  }
}
