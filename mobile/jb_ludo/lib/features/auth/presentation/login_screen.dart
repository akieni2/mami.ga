import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/config/app_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
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
  bool _probing = false;

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
        SnackBar(content: Text(_errorMessage(err))),
      );
    }
  }

  Future<void> _probe() async {
    setState(() => _probing = true);
    try {
      final result = await probeApiConnectivity();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result)),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(_errorMessage(e))),
      );
    } finally {
      if (mounted) setState(() => _probing = false);
    }
  }

  String _errorMessage(Object? error) {
    if (error is DioException && error.error is ApiException) {
      return (error.error as ApiException).message;
    }

    if (error is ApiException) return error.message;

    final text = error?.toString();
    if (text == null || text.isEmpty) return 'Connexion impossible';

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
            const SizedBox(height: 6),
            Text(
              'v${AppConfig.appVersion} · ${AppConfig.apiBaseUrl}',
              style: Theme.of(context).textTheme.bodySmall,
            ),
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
              onPressed: (_loading || _probing) ? null : _probe,
              child: Text(_probing ? 'Test en cours…' : 'Tester la connexion API'),
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
