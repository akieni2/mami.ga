import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import 'route_utils.dart';

class MamiMap extends StatefulWidget {
  const MamiMap({
    super.key,
    this.driver,
    this.client,
    this.pickup,
    this.destination,
    this.route,
    this.height,
    this.fullScreen = false,
    this.interactive = true,
  });

  final LatLng? driver;
  final LatLng? client;
  final LatLng? pickup;
  final LatLng? destination;
  final List<LatLng>? route;
  final double? height;
  final bool fullScreen;
  final bool interactive;

  @override
  State<MamiMap> createState() => _MamiMapState();
}

class _MamiMapState extends State<MamiMap> {
  final _controller = MapController();

  @override
  void didUpdateWidget(MamiMap oldWidget) {
    super.didUpdateWidget(oldWidget);
    _fitBounds();
  }

  void _fitBounds() {
    final points = <LatLng>[
      if (widget.driver != null) widget.driver!,
      if (widget.client != null) widget.client!,
      if (widget.pickup != null) widget.pickup!,
      if (widget.destination != null) widget.destination!,
      ...?widget.route,
    ];
    if (points.length < 2) return;

    WidgetsBinding.instance.addPostFrameCallback((_) {
      try {
        _controller.fitCamera(
          CameraFit.coordinates(
            coordinates: points,
            padding: const EdgeInsets.all(48),
          ),
        );
      } catch (_) {}
    });
  }

  @override
  Widget build(BuildContext context) {
    final markers = <Marker>[];
    if (widget.client != null) {
      markers.add(_marker(widget.client!, Icons.person, Colors.blue, 32));
    }
    if (widget.pickup != null) {
      markers.add(_marker(widget.pickup!, Icons.trip_origin, Colors.green, 34));
    }
    if (widget.destination != null) {
      markers.add(_marker(widget.destination!, Icons.flag, Colors.red, 32));
    }
    if (widget.driver != null) {
      markers.add(_marker(widget.driver!, Icons.local_taxi, Colors.amber, 36));
    }

    final center = RouteUtils.boundsCenter(
      markers.isEmpty
          ? [const LatLng(0.4162, 9.4673)]
          : markers.map((m) => m.point).toList(),
    );

    final map = FlutterMap(
      mapController: _controller,
      options: MapOptions(
        initialCenter: center,
        initialZoom: 14,
        interactionOptions: InteractionOptions(
          flags: widget.interactive
              ? InteractiveFlag.all
              : InteractiveFlag.none,
        ),
      ),
      children: [
        TileLayer(
          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
          userAgentPackageName: 'ga.mami.driver',
        ),
        if (widget.route != null && widget.route!.length > 1)
          PolylineLayer(
            polylines: [
              Polyline(
                points: widget.route!,
                color: const Color(0xFFF8B803),
                strokeWidth: 4,
              ),
            ],
          ),
        MarkerLayer(markers: markers),
      ],
    );

    if (widget.fullScreen) return map;

    return ClipRRect(
      borderRadius: BorderRadius.circular(16),
      child: SizedBox(height: widget.height ?? 220, child: map),
    );
  }

  Marker _marker(LatLng point, IconData icon, Color color, double size) {
    return Marker(
      point: point,
      width: 44,
      height: 44,
      child: Icon(icon, color: color, size: size),
    );
  }
}
