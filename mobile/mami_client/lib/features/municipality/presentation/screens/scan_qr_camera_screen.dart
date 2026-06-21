import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../data/fiscal_collection_repository.dart';
import '../../domain/operator_qr_lookup.dart';

class ScanQrCameraScreen extends ConsumerStatefulWidget {
  const ScanQrCameraScreen({super.key, this.redirect = 'fiscal-summary'});

  final String redirect;

  @override
  ConsumerState<ScanQrCameraScreen> createState() => _ScanQrCameraScreenState();
}

class _ScanQrCameraScreenState extends ConsumerState<ScanQrCameraScreen> {
  final _controller = MobileScannerController(
    detectionSpeed: DetectionSpeed.noDuplicates,
    facing: CameraFacing.back,
  );

  bool _processing = false;
  String? _error;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    if (_processing) return;

    final raw = capture.barcodes
        .map((barcode) => barcode.rawValue?.trim())
        .firstWhere((value) => value != null && value.isNotEmpty, orElse: () => null);

    if (raw == null) return;

    setState(() {
      _processing = true;
      _error = null;
    });

    try {
      final repo = ref.read(fiscalCollectionRepositoryProvider);
      final operatorId = await lookupOperatorIdByQr(
        lookup: repo.lookupOperatorByQr,
        rawPayload: raw,
      );

      if (!mounted) return;
      await _controller.stop();
      if (!mounted) return;
      final target = switch (widget.redirect) {
        'field-control' => '/municipality/field-control/$operatorId',
        _ => '/municipality/recovery/fiscal-summary/$operatorId',
      };
      context.push(target);
    } on OperatorQrLookupException catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.message;
        _processing = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _error = 'Connexion réseau indisponible';
        _processing = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Scanner QR commerce'),
        actions: [
          IconButton(
            icon: ValueListenableBuilder(
              valueListenable: _controller,
              builder: (context, state, _) {
                switch (state.torchState) {
                  case TorchState.off:
                    return const Icon(Icons.flash_off);
                  case TorchState.on:
                    return const Icon(Icons.flash_on);
                  case TorchState.unavailable:
                    return const Icon(Icons.flash_off);
                  case TorchState.auto:
                    return const Icon(Icons.flash_auto);
                }
              },
            ),
            onPressed: () => _controller.toggleTorch(),
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: Stack(
              fit: StackFit.expand,
              children: [
                MobileScanner(
                  controller: _controller,
                  onDetect: _onDetect,
                ),
                if (_processing)
                  const ColoredBox(
                    color: Color(0x88000000),
                    child: Center(child: CircularProgressIndicator()),
                  ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(
                  'Pointez la caméra vers le QR du commerce.',
                  textAlign: TextAlign.center,
                ),
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  Text(
                    _error!,
                    style: const TextStyle(color: Colors.red),
                    textAlign: TextAlign.center,
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
