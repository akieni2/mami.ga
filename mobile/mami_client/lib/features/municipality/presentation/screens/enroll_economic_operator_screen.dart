import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/map/mami_map.dart';
import '../../../../core/theme/app_theme.dart';
import '../../data/economic_operator_repository.dart';
import '../../data/models/economic_operator_category_model.dart';
import '../providers/economic_operator_providers.dart';

class EnrollEconomicOperatorScreen extends ConsumerStatefulWidget {
  const EnrollEconomicOperatorScreen({super.key});

  @override
  ConsumerState<EnrollEconomicOperatorScreen> createState() =>
      _EnrollEconomicOperatorScreenState();
}

class _EnrollEconomicOperatorScreenState
    extends ConsumerState<EnrollEconomicOperatorScreen> {
  static const _maxAccuracyM = 20.0;

  final _formKey = GlobalKey<FormState>();
  final _commercialNameController = TextEditingController();
  final _activityController = TextEditingController();
  final _responsibleController = TextEditingController();
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();

  int? _categoryId;
  Position? _position;
  bool _loadingGps = true;
  String? _gpsMessage;
  bool _locationConfirmed = false;
  bool _submitting = false;
  Timer? _gpsTimer;

  File? _facadePhoto;
  File? _tradeRegistryPhoto;
  File? _businessLicensePhoto;
  File? _municipalAuthorizationPhoto;

  @override
  void initState() {
    super.initState();
    _startGpsWatch();
  }

  @override
  void dispose() {
    _gpsTimer?.cancel();
    _commercialNameController.dispose();
    _activityController.dispose();
    _responsibleController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  void _startGpsWatch() {
    _captureGps();
    _gpsTimer = Timer.periodic(const Duration(seconds: 3), (_) => _captureGps());
  }

  Future<void> _captureGps() async {
    try {
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        if (mounted) {
          setState(() {
            _loadingGps = false;
            _gpsMessage = 'Autorisation GPS requise pour l\'enrôlement.';
          });
        }
        return;
      }

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
        ),
      );

      if (!mounted) return;

      setState(() {
        _position = position;
        _loadingGps = false;
        _gpsMessage = position.accuracy <= _maxAccuracyM
            ? null
            : 'Position GPS insuffisamment précise. Veuillez patienter.';
        if (position.accuracy > _maxAccuracyM) {
          _locationConfirmed = false;
        }
      });
    } catch (_) {
      if (mounted) {
        setState(() {
          _loadingGps = false;
          _gpsMessage = 'Impossible de capturer le GPS.';
        });
      }
    }
  }

  Future<void> _pickPhoto(String purpose) async {
    final picker = ImagePicker();
    final image = await picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 80,
    );
    if (image == null) return;

    setState(() {
      final file = File(image.path);
      switch (purpose) {
        case 'facade':
          _facadePhoto = file;
        case 'trade_registry':
          _tradeRegistryPhoto = file;
        case 'business_license':
          _businessLicensePhoto = file;
        case 'municipal_authorization':
          _municipalAuthorizationPhoto = file;
      }
    });
  }

  bool get _gpsReady =>
      _position != null && _position!.accuracy <= _maxAccuracyM;

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    if (!_gpsReady) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Position GPS insuffisamment précise. Veuillez patienter.',
          ),
        ),
      );
      return;
    }

    if (_facadePhoto == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('La photo de façade est obligatoire.')),
      );
      return;
    }

    if (!_locationConfirmed) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Confirmez l\'emplacement sur la carte avant d\'enregistrer.',
          ),
        ),
      );
      return;
    }

    if (_categoryId == null) return;

    setState(() => _submitting = true);

    try {
      final operator =
          await ref.read(economicOperatorRepositoryProvider).enrollOperator(
                commercialName: _commercialNameController.text.trim(),
                activityLabel: _activityController.text.trim(),
                categoryId: _categoryId!,
                responsibleName: _responsibleController.text.trim(),
                phone: _phoneController.text.trim(),
                email: _emailController.text.trim(),
                latitude: _position!.latitude,
                longitude: _position!.longitude,
                gpsAccuracyM: _position!.accuracy,
                gpsCapturedAt: _position!.timestamp,
                locationConfirmed: true,
                facadePhoto: _facadePhoto!,
                tradeRegistryPhoto: _tradeRegistryPhoto,
                businessLicensePhoto: _businessLicensePhoto,
                municipalAuthorizationPhoto: _municipalAuthorizationPhoto,
              );

      ref.invalidate(economicOperatorDashboardProvider);

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Commerce ${operator.publicId} enregistré')),
      );
      context.pop();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur : $e')),
      );
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final categoriesAsync = ref.watch(economicOperatorCategoriesProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Recensement économique'),
        backgroundColor: AppTheme.primary,
        foregroundColor: Colors.white,
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            _buildGpsCard(),
            const SizedBox(height: 16),
            if (_gpsReady) ...[
              _buildMapPreview(),
              const SizedBox(height: 12),
              CheckboxListTile(
                value: _locationConfirmed,
                onChanged: (v) => setState(() => _locationConfirmed = v ?? false),
                title: const Text(
                  'Je confirme que ce commerce se trouve à cet emplacement.',
                ),
                controlAffinity: ListTileControlAffinity.leading,
                contentPadding: EdgeInsets.zero,
              ),
              const SizedBox(height: 16),
            ],
            categoriesAsync.when(
              data: (categories) => _buildCategoryField(categories),
              loading: () => const LinearProgressIndicator(),
              error: (e, _) => Text('Erreur catégories : $e'),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _commercialNameController,
              decoration: const InputDecoration(
                labelText: 'Nom commercial',
                border: OutlineInputBorder(),
              ),
              validator: (v) =>
                  (v == null || v.trim().length < 2) ? 'Nom requis' : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _activityController,
              decoration: const InputDecoration(
                labelText: 'Activité',
                border: OutlineInputBorder(),
              ),
              validator: (v) =>
                  (v == null || v.trim().length < 2) ? 'Activité requise' : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _responsibleController,
              decoration: const InputDecoration(
                labelText: 'Nom du responsable',
                border: OutlineInputBorder(),
              ),
              validator: (v) => (v == null || v.trim().length < 2)
                  ? 'Responsable requis'
                  : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _phoneController,
              decoration: const InputDecoration(
                labelText: 'Téléphone',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.phone,
              validator: (v) =>
                  (v == null || v.trim().length < 8) ? 'Téléphone requis' : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _emailController,
              decoration: const InputDecoration(
                labelText: 'Email (optionnel)',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.emailAddress,
            ),
            const SizedBox(height: 20),
            const Text(
              'Photos',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            _photoButton('Façade du commerce *', _facadePhoto, 'facade'),
            _photoButton('Registre de commerce', _tradeRegistryPhoto, 'trade_registry'),
            _photoButton('Patente', _businessLicensePhoto, 'business_license'),
            _photoButton(
              'Autorisation municipale',
              _municipalAuthorizationPhoto,
              'municipal_authorization',
            ),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _submitting ? null : _submit,
              style: FilledButton.styleFrom(
                backgroundColor: AppTheme.primary,
                padding: const EdgeInsets.symmetric(vertical: 14),
              ),
              child: _submitting
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Text('Enregistrer le commerce'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildGpsCard() {
    if (_loadingGps && _position == null) {
      return const Card(
        child: Padding(
          padding: EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Capture GPS en cours…',
                  style: TextStyle(fontWeight: FontWeight.bold)),
              SizedBox(height: 8),
              LinearProgressIndicator(),
            ],
          ),
        ),
      );
    }

    if (_position == null) {
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                _gpsMessage ?? 'GPS indisponible',
                style: const TextStyle(color: Colors.red),
              ),
              TextButton(onPressed: _captureGps, child: const Text('Réessayer')),
            ],
          ),
        ),
      );
    }

    final p = _position!;
    return Card(
      color: _gpsReady ? Colors.green.shade50 : Colors.orange.shade50,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'GPS capturé',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Text('Latitude : ${p.latitude.toStringAsFixed(6)}'),
            Text('Longitude : ${p.longitude.toStringAsFixed(6)}'),
            Text('Précision : ${p.accuracy.toStringAsFixed(0)} mètres'),
            Text('Date : ${p.timestamp.toLocal()}'),
            if (_gpsMessage != null) ...[
              const SizedBox(height: 8),
              Text(
                _gpsMessage!,
                style: const TextStyle(color: Colors.deepOrange),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildMapPreview() {
    final point = LatLng(_position!.latitude, _position!.longitude);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Prévisualisation carte',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 8),
        ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: MamiMap(
            user: point,
            pickup: point,
            height: 180,
            interactive: false,
          ),
        ),
      ],
    );
  }

  Widget _buildCategoryField(List<EconomicOperatorCategoryModel> categories) {
    return DropdownButtonFormField<int>(
      value: _categoryId,
      decoration: const InputDecoration(
        labelText: 'Catégorie',
        border: OutlineInputBorder(),
      ),
      items: categories
          .map(
            (c) => DropdownMenuItem(value: c.id, child: Text(c.name)),
          )
          .toList(),
      onChanged: (v) => setState(() => _categoryId = v),
      validator: (v) => v == null ? 'Catégorie requise' : null,
    );
  }

  Widget _photoButton(String label, File? file, String purpose) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: OutlinedButton.icon(
        onPressed: () => _pickPhoto(purpose),
        icon: Icon(file == null ? Icons.camera_alt_outlined : Icons.check_circle),
        label: Text(file == null ? label : '$label — OK'),
      ),
    );
  }
}
