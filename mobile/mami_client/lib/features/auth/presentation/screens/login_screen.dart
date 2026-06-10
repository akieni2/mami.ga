import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/primary_button.dart';
import '../providers/auth_provider.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _identifier = TextEditingController(text: 'client@mami.ga');
  final _password = TextEditingController(text: 'password');
  bool _obscure = true;

  @override
  void dispose() {
    _identifier.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    debugPrint('BUTTON PRESSED');
    if (!_formKey.currentState!.validate()) {
      debugPrint('FORM INVALID');
      return;
    }
    debugPrint('CALLING LOGIN');
    await ref.read(authStateProvider.notifier).login(
          _identifier.text.trim(),
          _password.text,
        );
    debugPrint('LOGIN FINISHED');
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authStateProvider);

    ref.listen(authStateProvider, (prev, next) {
      if (next.hasValue && next.value != null && !next.isLoading) {
        context.go('/');
      }
      if (next.hasError && !next.isLoading) {
        final err = next.error;
        final message =
            err is ApiException ? err.message : 'Connexion impossible';
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message)),
        );
      }
    });

    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const SizedBox(height: 32),
                Icon(Icons.local_taxi, size: 72, color: AppTheme.primary),
                const SizedBox(height: 12),
                Text(
                  'Connexion',
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 32),
                TextFormField(
                  controller: _identifier,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                    labelText: 'Email ou téléphone',
                    prefixIcon: Icon(Icons.person_outline),
                    border: OutlineInputBorder(),
                    helperText: 'Connexion API : email pour le moment',
                  ),
                  validator: (v) =>
                      v == null || v.isEmpty ? 'Identifiant requis' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _password,
                  obscureText: _obscure,
                  decoration: InputDecoration(
                    labelText: 'Mot de passe',
                    prefixIcon: const Icon(Icons.lock_outline),
                    border: const OutlineInputBorder(),
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscure ? Icons.visibility : Icons.visibility_off,
                      ),
                      onPressed: () => setState(() => _obscure = !_obscure),
                    ),
                  ),
                  validator: (v) =>
                      v == null || v.length < 6 ? 'Mot de passe invalide' : null,
                ),
                const SizedBox(height: 24),
                PrimaryButton(
                  label: 'Se connecter',
                  loading: auth.isLoading,
                  onPressed: _submit,
                ),
                const SizedBox(height: 16),
                TextButton(
                  onPressed: () => context.push('/register'),
                  child: const Text('Créer un compte'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
