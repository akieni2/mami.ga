class EconomicOperatorCategoryModel {
  const EconomicOperatorCategoryModel({
    required this.id,
    required this.slug,
    required this.name,
    this.icon,
  });

  final int id;
  final String slug;
  final String name;
  final String? icon;

  factory EconomicOperatorCategoryModel.fromJson(Map<String, dynamic> json) {
    return EconomicOperatorCategoryModel(
      id: json['id'] as int,
      slug: json['slug'] as String,
      name: json['name'] as String,
      icon: json['icon'] as String?,
    );
  }
}
