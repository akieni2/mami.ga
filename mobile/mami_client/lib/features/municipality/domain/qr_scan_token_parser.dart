import 'dart:convert';

/// Extrait le jeton API à partir du contenu brut d'un QR commerce.
///
/// Formats supportés (rétrocompatibles) :
/// - UUID v4 seul (QR actuels en production)
/// - JSON `{"uuid":"…","public_id":"OWE-COM-…"}`
/// - Libellé composite `QR-OWE-COM-00000001-A1B2C3D4`
/// - `public_id` seul `OWE-COM-00000001` (diagnostic terrain ; API backend à étendre)
String? parseQrScanToken(String raw) {
  final trimmed = raw.trim();
  if (trimmed.isEmpty) {
    return null;
  }

  if (_isUuid(trimmed)) {
    return trimmed;
  }

  if (_isCompositeLabel(trimmed)) {
    return trimmed;
  }

  if (_isPublicId(trimmed)) {
    return trimmed;
  }

  if (trimmed.startsWith('{')) {
    try {
      final decoded = jsonDecode(trimmed);
      if (decoded is Map<String, dynamic>) {
        final uuid = decoded['uuid'];
        if (uuid is String && _isUuid(uuid.trim())) {
          return uuid.trim();
        }
        final publicId = decoded['public_id'];
        if (publicId is String && _isPublicId(publicId.trim())) {
          return publicId.trim();
        }
      }
    } on FormatException {
      return null;
    }
  }

  return null;
}

bool _isUuid(String value) {
  return RegExp(
    r'^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
    caseSensitive: false,
  ).hasMatch(value);
}

bool _isCompositeLabel(String value) {
  return RegExp(r'^QR-OWE-COM-\d{6,8}-[0-9A-F]{8}$', caseSensitive: false)
      .hasMatch(value);
}

bool _isPublicId(String value) {
  return RegExp(r'^OWE-COM-\d{6,8}$', caseSensitive: false).hasMatch(value);
}
