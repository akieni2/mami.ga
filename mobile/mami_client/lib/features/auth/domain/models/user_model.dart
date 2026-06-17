class UserModel {
  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    required this.isDriver,
    this.roles = const [],
    this.permissions = const [],
  });

  final int id;
  final String name;
  final String email;
  final String? phone;
  final bool isDriver;
  final List<String> roles;
  final List<String> permissions;

  bool get isMunicipalAgent => roles.contains('municipal_agent');

  bool hasPermission(String slug) => permissions.contains(slug);

  bool get canEnrollEconomicOperators =>
      hasPermission('economic_operator.create') || isMunicipalAgent;

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      phone: json['phone'] as String?,
      isDriver: json['is_driver'] == true,
      roles: (json['roles'] as List<dynamic>?)
              ?.map((e) => e as String)
              .toList() ??
          const [],
      permissions: (json['permissions'] as List<dynamic>?)
              ?.map((e) => e as String)
              .toList() ??
          const [],
    );
  }
}
