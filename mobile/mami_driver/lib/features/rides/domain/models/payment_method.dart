enum RidePaymentMethod {
  cash('cash', 'Espèces'),
  airtelMoney('airtel_money', 'Airtel Money'),
  moovMoney('moov_money', 'Moov Money');

  const RidePaymentMethod(this.apiValue, this.label);

  final String apiValue;
  final String label;

  static RidePaymentMethod? fromApi(String? value) {
    if (value == null) return null;
    return RidePaymentMethod.values.firstWhere(
      (m) => m.apiValue == value,
      orElse: () => RidePaymentMethod.cash,
    );
  }
}
