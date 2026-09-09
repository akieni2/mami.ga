import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../auth/presentation/auth_provider.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _loading = true);
    await ref
        .read(authStateProvider.notifier)
        .login(_email.text.trim(), _password.text);
    if (!mounted) return;
    setState(() => _loading = false);
    final user = ref.read(authStateProvider).valueOrNull;
    if (user != null) {
      context.go('/home');
    } else {
      final err = ref.read(authStateProvider).error;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(err?.toString() ?? 'Connexion impossible')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const SizedBox(height: 40),
            Text('JB Games',
                style: Theme.of(context)
                    .textTheme
                    .headlineLarge
                    ?.copyWith(fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            const Text('Ludo et damier en ligne'),
            const SizedBox(height: 32),
            TextField(
                controller: _email,
                decoration: const InputDecoration(labelText: 'Email'),
                keyboardType: TextInputType.emailAddress),
            const SizedBox(height: 12),
            TextField(
                controller: _password,
                decoration: const InputDecoration(labelText: 'Mot de passe'),
                obscureText: true),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _loading ? null : _submit,
              child: _loading
                  ? const CircularProgressIndicator()
                  : const Text('Connexion'),
            ),
            TextButton(
              onPressed: () => context.push('/register'),
              child: const Text('Créer un compte'),
            ),
          ],
        ),
      ),
    );
  }
}
