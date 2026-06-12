import 'package:flutter/foundation.dart';

/// Logs diagnostic visibilité offres P3 chauffeur.
class OffersLogger {
  static void fetchStart(int driverId) {
    debugPrint('[OFFERS_FETCH_START] driverId=$driverId');
  }

  static void fetchSuccess(int count) {
    debugPrint('[OFFERS_FETCH_SUCCESS] count=$count');
  }

  static void fetchCount(int count) {
    debugPrint('[OFFERS_FETCH_COUNT] $count');
  }

  static void fetchError(Object error, [StackTrace? stack]) {
    debugPrint('[OFFERS_FETCH_ERROR] $error');
    if (stack != null && kDebugMode) {
      debugPrint(stack.toString());
    }
  }

  static void reverbOfferReceived(String event, Map<String, dynamic> payload) {
    debugPrint(
      '[REVERB_OFFER_RECEIVED] event=$event offerId=${payload['offer_id']} rideId=${payload['ride_id']}',
    );
  }

  static void reverbSubscribe(int driverId) {
    debugPrint('[REVERB_SUBSCRIBE] channel=private-driver-$driverId');
  }

  static void parseError(Object error, Map<String, dynamic> json) {
    debugPrint('[OFFERS_PARSE_ERROR] $error json=$json');
  }
}
