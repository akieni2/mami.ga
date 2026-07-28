import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../data/jb_ludo_repository.dart';

class ProfileSetupScreen extends ConsumerStatefulWidget {
  const ProfileSetupScreen({super.key});

  @override
  ConsumerState<ProfileSetupScreen> createState() => _ProfileSetupScreenState();
}

class _ProfileSetupScreenState extends ConsumerState<ProfileSetupScreen> {
  final _pseudo = TextEditingController();
  final _phone = TextEditingController();
  final _city = TextEditingController();
  String _level = 'intermediate';
  bool _loading = false;

  @override
  void dispose() {
    _pseudo.dispose();
    _phone.dispose();
    _city.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    setState(() => _loading = true);
    try {
      await ref.read(jbLudoRepositoryProvider).saveProfile({
        'pseudo': _pseudo.text.trim(),
        'phone': _phone.text.trim(),
        'city': _city.text.trim(),
        'level': _level,
        'country': 'Gabon',
      });
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
          TextField(controller: _pseudo, decoration: const InputDecoration(labelText: 'Pseudonyme')),
          TextField(controller: _phone, decoration: const InputDecoration(labelText: 'Téléphone (unique)')),
          TextField(controller: _city, decoration: const InputDecoration(labelText: 'Ville')),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            value: _level,
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
