import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/map/lat_lng_utils.dart';

class BookingDraft {
  const BookingDraft({
    this.pickup,
    this.destination,
  });

  final LatLng? pickup;
  final LatLng? destination;

  BookingDraft copyWith({LatLng? pickup, LatLng? destination}) {
    return BookingDraft(
      pickup: pickup ?? this.pickup,
      destination: destination ?? this.destination,
    );
  }
}

final bookingDraftProvider =
    StateNotifierProvider<BookingDraftNotifier, BookingDraft>(
  (ref) => BookingDraftNotifier(),
);

class BookingDraftNotifier extends StateNotifier<BookingDraft> {
  BookingDraftNotifier() : super(const BookingDraft());

  void setPickup(LatLng value) {
    if (!LatLngUtils.isValid(value)) return;
    state = state.copyWith(pickup: value);
  }

  void setDestination(LatLng value) {
    if (!LatLngUtils.isValid(value)) return;
    state = state.copyWith(destination: value);
  }

  void reset() => state = const BookingDraft();
}
