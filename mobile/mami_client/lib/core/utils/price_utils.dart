import '../config/app_config.dart';
import 'geo_utils.dart';

class PriceUtils {
  static double estimateTripPrice(
    double pickupLat,
    double pickupLng,
    double destLat,
    double destLng,
  ) {
    final km = GeoUtils.distanceKm(pickupLat, pickupLng, destLat, destLng);
    return AppConfig.rideBasePrice + (km * AppConfig.ridePricePerKm);
  }
}
