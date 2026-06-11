import 'package:flutter/material.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/map/lat_lng_utils.dart';
import '../../../../core/map/mami_map.dart';
import '../../../../core/map/route_utils.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/primary_button.dart';
import '../../../location/domain/user_location_result.dart';

enum _MapPickTarget { pickup, destination }

/// Résultat sélection carte P2B.
class RideMapPickerResult {
  const RideMapPickerResult({
    this.pickup,
    this.destination,
  });

  final LatLng? pickup;
  final LatLng? destination;
}

/// Modal carte optionnelle — affiner départ et/ou destination.
class RideMapPickerSheet extends StatefulWidget {
  const RideMapPickerSheet({
    super.key,
    this.initialPickup,
    this.initialDestination,
  });

  final LatLng? initialPickup;
  final LatLng? initialDestination;

  static Future<RideMapPickerResult?> show(
    BuildContext context, {
    LatLng? initialPickup,
    LatLng? initialDestination,
  }) {
    return showModalBottomSheet<RideMapPickerResult>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => RideMapPickerSheet(
        initialPickup: initialPickup,
        initialDestination: initialDestination,
      ),
    );
  }

  @override
  State<RideMapPickerSheet> createState() => _RideMapPickerSheetState();
}

class _RideMapPickerSheetState extends State<RideMapPickerSheet> {
  _MapPickTarget _target = _MapPickTarget.pickup;
  LatLng? _pickup;
  LatLng? _destination;

  @override
  void initState() {
    super.initState();
    _pickup = widget.initialPickup;
    _destination = widget.initialDestination;
  }

  void _onMapTap(LatLng point) {
    if (!LatLngUtils.isValid(point)) return;
    setState(() {
      if (_target == _MapPickTarget.pickup) {
        _pickup = point;
      } else {
        _destination = point;
      }
    });
  }

  void _confirm() {
    Navigator.of(context).pop(
      RideMapPickerResult(pickup: _pickup, destination: _destination),
    );
  }

  @override
  Widget build(BuildContext context) {
    final center = LatLngUtils.orFallback(
      _pickup ?? _destination ?? UserLocationResult.librevilleFallback,
    );

    final route = (_pickup != null && _destination != null)
        ? RouteUtils.straightLine(_pickup!, _destination!)
        : null;

    return SizedBox(
      height: MediaQuery.of(context).size.height * 0.88,
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 8, 8),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    'Choisir sur la carte',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.of(context).pop(),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: SegmentedButton<_MapPickTarget>(
              segments: const [
                ButtonSegment(
                  value: _MapPickTarget.pickup,
                  label: Text('Départ'),
                  icon: Icon(Icons.trip_origin),
                ),
                ButtonSegment(
                  value: _MapPickTarget.destination,
                  label: Text('Destination'),
                  icon: Icon(Icons.flag),
                ),
              ],
              selected: {_target},
              onSelectionChanged: (s) => setState(() => _target = s.first),
            ),
          ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Text(
              _target == _MapPickTarget.pickup
                  ? 'Touchez la carte pour placer le départ'
                  : 'Touchez la carte pour placer la destination',
              style: TextStyle(fontSize: 13, color: Colors.grey.shade700),
            ),
          ),
          const SizedBox(height: 8),
          Expanded(
            child: MamiMap(
              fullScreen: true,
              user: center,
              pickup: _pickup,
              destination: _destination,
              route: route,
              onTap: _onMapTap,
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (_pickup != null)
                  Text(
                    'Départ : ${LatLngUtils.format(_pickup)}',
                    style: const TextStyle(fontSize: 13),
                  ),
                if (_destination != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    'Destination : ${LatLngUtils.format(_destination)}',
                    style: const TextStyle(fontSize: 13),
                  ),
                ],
                const SizedBox(height: 12),
                PrimaryButton(
                  label: 'Confirmer les points',
                  color: AppTheme.primary,
                  onPressed: (_pickup != null || _destination != null)
                      ? _confirm
                      : null,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
