import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import '../../features/location/domain/user_location_result.dart';
import 'lat_lng_utils.dart';
import 'route_utils.dart';

class MamiMap extends StatefulWidget {
  const MamiMap({
    super.key,
    this.user,
    this.pickup,
    this.destination,
    this.driver,
    this.client,
    this.route,
    this.height,
    this.fullScreen = false,
    this.onTap,
    this.interactive = true,
  });

  final LatLng? user;
  final LatLng? pickup;
  final LatLng? destination;
  final LatLng? driver;
  final LatLng? client;
  final List<LatLng>? route;
  final double? height;
  final bool fullScreen;
  final void Function(LatLng point)? onTap;
  final bool interactive;

  @override
  State<MamiMap> createState() => _MamiMapState();
}

class _MamiMapState extends State<MamiMap> {
  final _controller = MapController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _logMapInit());
  }

  @override
  void didUpdateWidget(MamiMap oldWidget) {
    super.didUpdateWidget(oldWidget);
    _syncCamera();
  }

  void _logMapInit() {
    final center = _resolveCenter();
    debugPrint(
      'MAP INIT: center=${center.latitude.toStringAsFixed(4)}, '
      '${center.longitude.toStringAsFixed(4)} zoom=14',
    );
  }

  LatLng _resolveCenter() {
    final markerPoints = LatLngUtils.validPoints([
      widget.user,
      widget.pickup,
      widget.destination,
      widget.driver,
      widget.client,
    ]);
    if (markerPoints.isEmpty) {
      return UserLocationResult.librevilleFallback;
    }
    return RouteUtils.boundsCenter(markerPoints);
  }

  void _syncCamera() {
    final points = LatLngUtils.distinctPoints(
      LatLngUtils.validPoints([
        widget.user,
        widget.pickup,
        widget.destination,
        widget.driver,
        widget.client,
        ...?widget.route,
      ]),
    );

    if (points.isEmpty) return;

    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      try {
        if (points.length == 1) {
          _controller.move(points.first, 15);
          return;
        }
        _controller.fitCamera(
          CameraFit.coordinates(
            coordinates: points,
            padding: const EdgeInsets.all(48),
            maxZoom: 17,
          ),
        );
      } catch (e) {
        debugPrint('MAP CAMERA SYNC FAILED: $e');
      }
    });
  }

  void _handleTap(TapPosition tapPosition, LatLng point) {
    if (!LatLngUtils.isValid(point)) {
      debugPrint(
        'DESTINATION INVALID: ${point.latitude}, ${point.longitude}',
      );
      return;
    }
    widget.onTap?.call(point);
  }

  @override
  Widget build(BuildContext context) {
    final markers = <Marker>[];

    void addMarker(LatLng? point, IconData icon, Color color, double size) {
      if (!LatLngUtils.isValid(point)) return;
      markers.add(_marker(point!, icon, color, size));
    }

    addMarker(widget.user, Icons.person_pin_circle, Colors.blue, 36);
    addMarker(widget.client, Icons.person, Colors.blue, 32);
    addMarker(widget.pickup, Icons.trip_origin, Colors.green, 34);
    addMarker(widget.destination, Icons.flag, Colors.red, 32);
    addMarker(widget.driver, Icons.local_taxi, Colors.amber, 34);

    final center = _resolveCenter();
    final routePoints = LatLngUtils.validPoints(widget.route ?? const []);

    final map = FlutterMap(
      mapController: _controller,
      options: MapOptions(
        initialCenter: center,
        initialZoom: 14,
        onTap: widget.onTap != null ? _handleTap : null,
        interactionOptions: InteractionOptions(
          flags: widget.interactive
              ? InteractiveFlag.all
              : InteractiveFlag.none,
        ),
      ),
      children: [
        TileLayer(
          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
          userAgentPackageName: 'ga.mami.client',
        ),
        if (routePoints.length > 1)
          PolylineLayer(
            polylines: [
              Polyline(
                points: routePoints,
                color: const Color(0xFFF8B803),
                strokeWidth: 4,
              ),
            ],
          ),
        MarkerLayer(markers: markers),
      ],
    );

    if (widget.fullScreen) {
      return map;
    }

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
