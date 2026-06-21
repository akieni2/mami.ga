import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

/// Point d'entrée QR commerce réutilisable (situation fiscale, encaissement, contrôles).
class QrCommerceEntry extends StatelessWidget {
  const QrCommerceEntry({
    super.key,
    this.showManualFallback = true,
    this.onManualFound,
    this.manualHint = 'Identifiant interne de l\'opérateur (après scan QR)',
    this.cameraRedirect = 'fiscal-summary',
  });

  final bool showManualFallback;
  final ValueChanged<int>? onManualFound;
  final String manualHint;
  final String cameraRedirect;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        FilledButton.icon(
          onPressed: () => context.push(
            '/municipality/recovery/scan/camera?redirect=$cameraRedirect',
          ),
          icon: const Text('📷', style: TextStyle(fontSize: 18)),
          label: const Text('Scanner QR commerce'),
        ),
        if (showManualFallback && onManualFound != null) ...[
          const SizedBox(height: 24),
          const Row(
            children: [
              Expanded(child: Divider()),
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 12),
                child: Text('OU'),
              ),
              Expanded(child: Divider()),
            ],
          ),
          const SizedBox(height: 24),
          Text(manualHint),
          const SizedBox(height: 12),
          _ManualOperatorLookup(onFound: onManualFound!),
        ],
      ],
    );
  }
}

class _ManualOperatorLookup extends StatefulWidget {
  const _ManualOperatorLookup({required this.onFound});

  final ValueChanged<int> onFound;

  @override
  State<_ManualOperatorLookup> createState() => _ManualOperatorLookupState();
}

class _ManualOperatorLookupState extends State<_ManualOperatorLookup> {
  final _controller = TextEditingController();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        TextField(
          controller: _controller,
          keyboardType: TextInputType.number,
          decoration: const InputDecoration(
            labelText: 'ID opérateur',
            border: OutlineInputBorder(),
          ),
        ),
        const SizedBox(height: 16),
        FilledButton(
          onPressed: () {
            final id = int.tryParse(_controller.text.trim());
            if (id != null) widget.onFound(id);
          },
          child: const Text('Consulter'),
        ),
      ],
    );
  }
}
