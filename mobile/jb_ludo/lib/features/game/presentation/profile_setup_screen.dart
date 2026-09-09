import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../data/jb_ludo_repository.dart';

class ProfileSetupScreen extends ConsumerStatefulWidget {
  const ProfileSetupScreen({super.key});

  @override
  ConsumerState<ProfileSetupScreen> createState() => _ProfileSetupScreenState();
}

class _ProfileSetupScreenState extends ConsumerState<ProfileSetupScreen> {
  final _firstName = TextEditingController();
  final _lastName = TextEditingController();
  final _pseudo = TextEditingController();
  final _phone = TextEditingController();
  final _country = TextEditingController(text: 'Gabon');
  final _city = TextEditingController();
  final _neighborhood = TextEditingController();
  final _imagePicker = ImagePicker();
  XFile? _photo;
  String _level = 'intermediate';
  bool _loading = false;

  @override
  void dispose() {
    _firstName.dispose();
    _lastName.dispose();
    _pseudo.dispose();
    _phone.dispose();
    _country.dispose();
    _city.dispose();
    _neighborhood.dispose();
    super.dispose();
  }

  Future<void> _pickPhoto() async {
    final photo = await _imagePicker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 1200,
      imageQuality: 85,
    );

    if (photo != null && mounted) {
      setState(() => _photo = photo);
    }
  }

  Future<void> _save() async {
    setState(() => _loading = true);
    try {
      await ref.read(jbLudoRepositoryProvider).saveProfile({
        'first_name': _firstName.text.trim(),
        'last_name': _lastName.text.trim(),
        'pseudo': _pseudo.text.trim(),
        'phone': _phone.text.trim(),
        'country': _country.text.trim().isEmpty ? 'Gabon' : _country.text.trim(),
        'city': _city.text.trim(),
        'neighborhood': _neighborhood.text.trim(),
        'level': _level,
      }, photoPath: _photo?.path);
      if (mounted) context.go('/home');
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Profil joueur')),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          Center(
            child: Column(
              children: [
                CircleAvatar(
                  radius: 46,
                  backgroundImage: _photo == null ? null : FileImage(File(_photo!.path)),
                  child: _photo == null ? const Icon(Icons.person, size: 44) : null,
                ),
                const SizedBox(height: 12),
                OutlinedButton.icon(
                  onPressed: _loading ? null : _pickPhoto,
                  icon: const Icon(Icons.add_a_photo_outlined),
                  label: Text(_photo == null ? 'Ajouter une photo' : 'Changer la photo'),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          TextField(controller: _firstName, decoration: const InputDecoration(labelText: 'Prenom')),
          TextField(controller: _lastName, decoration: const InputDecoration(labelText: 'Nom')),
          TextField(controller: _pseudo, decoration: const InputDecoration(labelText: 'Screen name / Alias')),
          TextField(controller: _phone, decoration: const InputDecoration(labelText: 'Telephone (unique)')),
          TextField(controller: _country, decoration: const InputDecoration(labelText: 'Pays')),
          TextField(controller: _city, decoration: const InputDecoration(labelText: 'Ville')),
          TextField(controller: _neighborhood, decoration: const InputDecoration(labelText: 'Quartier')),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            initialValue: _level,
            decoration: const InputDecoration(labelText: 'Niveau'),
            items: const [
              DropdownMenuItem(value: 'beginner', child: Text('Débutant')),
              DropdownMenuItem(value: 'intermediate', child: Text('Intermédiaire')),
              DropdownMenuItem(value: 'advanced', child: Text('Avancé')),
              DropdownMenuItem(value: 'expert', child: Text('Expert')),
            ],
            onChanged: (v) => setState(() => _level = v ?? 'intermediate'),
          ),
          const SizedBox(height: 24),
          FilledButton(onPressed: _loading ? null : _save, child: const Text('Enregistrer')),
        ],
      ),
    );
  }
}
