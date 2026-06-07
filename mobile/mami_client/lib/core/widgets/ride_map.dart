import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

class RideMap extends StatelessWidget {
  const RideMap({
    super.key,
    required this.center,
    this.pickup,
    this.destination,
    this.driver,
    this.height = 220,
  });

  final LatLng center;
  final LatLng? pickup;
  final LatLng? destination;
  final LatLng? driver;
  final double height;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(16),
      child: SizedBox(
        height: height,
        child: FlutterMap(
          options: MapOptions(
            initialCenter: center,
            initialZoom: 14,
            interactionOptions: const InteractionOptions(
              flags: InteractiveFlag.pinchZoom | InteractiveFlag.drag,
            ),
          ),
          children: [
            TileLayer(
              urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
              userAgentPackageName: 'ga.mami.client',
            ),
            MarkerLayer(
              markers: [
                Marker(
                  point: center,
                  width: 40,
                  height: 40,
                  child: const Icon(Icons.person_pin_circle,
                      color: Colors.blue, size: 36),
                ),
                if (pickup != null)
                  Marker(
                    point: pickup!,
                    width: 40,
                    height: 40,
                    child: const Icon(Icons.trip_origin,
                        color: Colors.green, size: 32),
                  ),
                if (destination != null)
                  Marker(
                    point: destination!,
                    width: 40,
                    height: 40,
                    child:
                        const Icon(Icons.flag, color: Colors.red, size: 32),
                  ),
                if (driver != null)
                  Marker(
                    point: driver!,
                    width: 40,
                    height: 40,
                    child: const Icon(Icons.local_taxi,
                        color: Colors.amber, size: 32),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
