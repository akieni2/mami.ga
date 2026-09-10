import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_exception.dart';
import 'auth_provider.dart';

class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});

  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _loading = true);
    await ref.read(authStateProvider.notifier).register(
          name: _name.text.trim(),
          email: _email.text.trim(),
          password: _password.text,
          phone: _phone.text.trim().isEmpty ? null : _phone.text.trim(),
        );
    if (!mounted) return;
    setState(() => _loading = false);
    if (ref.read(authStateProvider).valueOrNull != null) {
      context.go('/profile/setup');
    } else {
      final error = ref.read(authStateProvider).error;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(_errorMessage(error))),
      );
    }
  }

  String _errorMessage(Object? error) {
    if (error is DioException && error.error is ApiException) {
      return (error.error as ApiException).message;
    }

    if (error is ApiException) return error.message;

    final text = error?.toString();
    if (text == null || text.isEmpty) return 'Création du compte impossible';

    return text
        .replaceFirst('Exception: ', '')
        .replaceFirst('ApiException: ', '')
        .replaceFirst('DioException [bad response]: null\nError: ', '')
        .replaceFirst('DioException [connection error]: ', '')
        .replaceFirst('DioException [connection timeout]: ', '')
        .replaceFirst('DioException [receive timeout]: ', '')
        .replaceFirst('DioException [unknown]: null\nError: ', '');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Inscription')),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          TextField(
              controller: _name,
              decoration: const InputDecoration(labelText: 'Nom')),
          TextField(
              controller: _email,
              decoration: const InputDecoration(labelText: 'Email')),
          TextField(
              controller: _phone,
              decoration: const InputDecoration(labelText: 'Téléphone')),
          TextField(
            controller: _password,
            decoration: const InputDecoration(
              labelText: 'Mot de passe',
              helperText: '6 caractères minimum',
            ),
            obscureText: true,
          ),
          const SizedBox(height: 24),
          FilledButton(
              onPressed: _loading ? null : _submit,
              child: const Text('Créer le compte')),
        ],
      ),
    );
  }
}
