class MunicipalReceiptPrintPayload {
  MunicipalReceiptPrintPayload({
    required this.commune,
    required this.receiptNumber,
    required this.commercialName,
    required this.publicId,
    required this.amountXaf,
    required this.issuedAt,
    required this.agentName,
    required this.verificationUrl,
    required this.documentHashShort,
  });

  factory MunicipalReceiptPrintPayload.fromJson(Map<String, dynamic> json) {
    return MunicipalReceiptPrintPayload(
      commune: json['commune'] as String? ?? "Commune d'Owendo",
      receiptNumber: json['receipt_number'] as String? ?? '',
      commercialName: json['commercial_name'] as String? ?? '',
      publicId: json['public_id'] as String? ?? '',
      amountXaf: json['amount_xaf']?.toString() ?? '0',
      issuedAt: json['issued_at'] as String? ?? '',
      agentName: json['agent_name'] as String? ?? '',
      verificationUrl: json['verification_url'] as String? ?? '',
      documentHashShort: json['document_hash_short'] as String? ?? '',
    );
  }

  final String commune;
  final String receiptNumber;
  final String commercialName;
  final String publicId;
  final String amountXaf;
  final String issuedAt;
  final String agentName;
  final String verificationUrl;
  final String documentHashShort;
}

class MunicipalReceiptModel {
  MunicipalReceiptModel({
    required this.id,
    required this.receiptNumber,
    required this.status,
    required this.statusLabel,
    required this.verificationUrl,
    required this.documentHash,
    required this.reprintCount,
    required this.printPayload,
  });

  factory MunicipalReceiptModel.fromJson(Map<String, dynamic> json) {
    final payload = json['print_payload'] as Map<String, dynamic>? ?? {};

    return MunicipalReceiptModel(
      id: json['id'] as int,
      receiptNumber: json['receipt_number'] as String? ?? '',
      status: json['status'] as String? ?? 'valid',
      statusLabel: json['status_label'] as String? ?? '',
      verificationUrl: json['verification_url'] as String? ?? '',
      documentHash: json['document_hash'] as String? ?? '',
      reprintCount: json['reprint_count'] as int? ?? 0,
      printPayload: MunicipalReceiptPrintPayload.fromJson(payload),
    );
  }

  final int id;
  final String receiptNumber;
  final String status;
  final String statusLabel;
  final String verificationUrl;
  final String documentHash;
  final int reprintCount;
  final MunicipalReceiptPrintPayload printPayload;

  bool get isValid => status == 'valid';
}
