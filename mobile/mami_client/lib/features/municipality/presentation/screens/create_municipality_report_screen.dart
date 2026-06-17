import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../../../core/theme/app_theme.dart';
import '../../data/municipality_repository.dart';
import '../providers/municipality_providers.dart';

class CreateMunicipalityReportScreen extends ConsumerStatefulWidget {
  const CreateMunicipalityReportScreen({super.key});

  @override
  ConsumerState<CreateMunicipalityReportScreen> createState() =>
      _CreateMunicipalityReportScreenState();
}

class _CreateMunicipalityReportScreenState
    extends ConsumerState<CreateMunicipalityReportScreen> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _addressController = TextEditingController();

  String _category = 'voirie';
  File? _photo;
  Position? _position;
  bool _loadingGps = true;
  bool _submitting = false;

  static const _categories = {
    'voirie': 'Voirie',
    'eclairage': 'Éclairage public',
    'dechets': 'Déchets',
    'inondations': 'Inondations',
    'marches': 'Marchés',
    'securite': 'Sécurité',
    'environnement': 'Environnement',
  };

  @override
  void initState() {
    super.initState();
    _loadGps();
  }

  Future<void> _loadGps() async {
    try {
      final permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        await Geolocator.requestPermission();
      }
      final position = await Geolocator.getCurrentPosition();
      if (mounted) {
        setState(() {
          _position = position;
          _loadingGps = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() => _loadingGps = false);
      }
    }
  }

  Future<void> _pickPhoto() async {
    final picker = ImagePicker();
    final image = await picker.pickImage(source: ImageSource.camera, imageQuality: 80);
    if (image != null) {
      setState(() => _photo = File(image.path));
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _position == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('GPS requis pour envoyer le signalement')),
      );
      return;
    }

    setState(() => _submitting = true);

    try {
      final report = await ref.read(municipalityRepositoryProvider).createReport(
            category: _category,
            title: _titleController.text.trim(),
            description: _descriptionController.text.trim(),
            latitude: _position!.latitude,
            longitude: _position!.longitude,
            address: _addressController.text.trim(),
            photo: _photo,
          );

      ref.invalidate(myMunicipalityReportsProvider);

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Signalement ${report.reference} enregistré')),
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
  void dispose() {
    _titleController.dispose();
    _descriptionController.dispose();
    _addressController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Signaler un problème'),
        backgroundColor: AppTheme.primary,
        foregroundColor: Colors.white,
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            if (_loadingGps)
              const LinearProgressIndicator()
            else if (_position != null)
              Text(
                'GPS : ${_position!.latitude.toStringAsFixed(5)}, ${_position!.longitude.toStringAsFixed(5)}',
                style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
              )
            else
              TextButton(onPressed: _loadGps, child: const Text('Activer le GPS')),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              value: _category,
              decoration: const InputDecoration(labelText: 'Catégorie', border: OutlineInputBorder()),
              items: _categories.entries
                  .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value)))
                  .toList(),
              onChanged: (v) => setState(() => _category = v ?? 'voirie'),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _titleController,
              decoration: const InputDecoration(labelText: 'Titre', border: OutlineInputBorder()),
              validator: (v) => (v == null || v.trim().length < 3) ? 'Titre requis' : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _descriptionController,
              decoration: const InputDecoration(labelText: 'Description', border: OutlineInputBorder()),
              maxLines: 4,
              validator: (v) => (v == null || v.trim().length < 10) ? 'Description requise' : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _addressController,
              decoration: const InputDecoration(labelText: 'Adresse (optionnel)', border: OutlineInputBorder()),
            ),
            const SizedBox(height: 16),
            OutlinedButton.icon(
              onPressed: _pickPhoto,
              icon: const Icon(Icons.camera_alt_outlined),
              label: Text(_photo == null ? 'Ajouter une photo' : 'Photo sélectionnée'),
            ),
            if (_photo != null) ...[
              const SizedBox(height: 8),
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Image.file(_photo!, height: 160, width: double.infinity, fit: BoxFit.cover),
              ),
            ],
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
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Text('Envoyer'),
            ),
          ],
        ),
      ),
    );
  }
}
