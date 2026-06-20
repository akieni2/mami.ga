/// Decode API scalar values (Laravel `decimal` casts often serialize as strings).
double readJsonDouble(dynamic value) {
  if (value is num) return value.toDouble();
  if (value is String) return double.parse(value);
  throw FormatException('Expected num or String for double, got ${value.runtimeType}');
}

double? readJsonDoubleOrNull(dynamic value, {double? fallback}) {
  if (value == null) return fallback;
  return readJsonDouble(value);
}
