import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

class RideMap extends StatelessWidget {
  const RideMap({
    super.key,
    required this.pickup,
    this.destination,
    this.driver,
    this.height = 200,
  });

  final LatLng pickup;
  final LatLng? destination;
  final LatLng? driver;
  final double height;

  @override
  Widget build(BuildContext context) {
    final points = <LatLng>[pickup];
    if (destination != null) points.add(destination!);
    if (driver != null) points.add(driver!);

    final center = LatLng(
      points.map((p) => p.latitude).reduce((a, b) => a + b) / points.length,
      points.map((p) => p.longitude).reduce((a, b) => a + b) / points.length,
    );

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
              userAgentPackageName: 'ga.mami.driver',
            ),
            MarkerLayer(
              markers: [
                Marker(
                  point: pickup,
                  width: 40,
                  height: 40,
                  child: const Icon(Icons.location_on, color: Colors.green, size: 36),
                ),
                if (destination != null)
                  Marker(
                    point: destination!,
                    width: 40,
                    height: 40,
                    child: const Icon(Icons.flag, color: Colors.red, size: 32),
                  ),
                if (driver != null)
                  Marker(
                    point: driver!,
                    width: 40,
                    height: 40,
                    child: const Icon(Icons.local_taxi, color: Colors.amber, size: 32),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
