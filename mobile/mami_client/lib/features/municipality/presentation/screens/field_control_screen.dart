import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_error_message.dart';
import '../../data/fiscal_collection_repository.dart';
import '../../domain/municipal_gps_service.dart';
import '../providers/fiscal_collection_providers.dart';
import '../providers/municipal_gps_provider.dart';
import '../widgets/qr_commerce_entry.dart';

class FieldControlType {
  const FieldControlType({
    required this.value,
    required this.label,
  });

  final String value;
  final String label;
}

const fieldControlTypes = [
  FieldControlType(value: 'presence_control', label: 'Contrôle de présence'),
  FieldControlType(value: 'license_control', label: 'Contrôle licence'),
  FieldControlType(value: 'patent_control', label: 'Contrôle patente'),
  FieldControlType(value: 'municipal_control', label: 'Contrôle municipal'),
];

class FieldControlScreen extends ConsumerWidget {
  const FieldControlScreen({super.key, this.operatorId});

  final int? operatorId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (operatorId == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Contrôles terrain')),
        body: const Padding(
          padding: EdgeInsets.all(20),
          child: QrCommerceEntry(
            showManualFallback: false,
            cameraRedirect: 'field-control',
          ),
        ),
      );
    }

    return _FieldControlForm(operatorId: operatorId!);
  }
}

class _FieldControlForm extends ConsumerStatefulWidget {
  const _FieldControlForm({required this.operatorId});

  final int operatorId;

  @override
  ConsumerState<_FieldControlForm> createState() => _FieldControlFormState();
}

class _FieldControlFormState extends ConsumerState<_FieldControlForm> {
  String? _selectedType;
  final _notesController = TextEditingController();
  bool _loading = false;
  String? _error;
  String? _success;

  @override
  void dispose() {
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final visitType = _selectedType;
    if (visitType == null) {
      setState(() => _error = 'Sélectionnez un type de contrôle');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
      _success = null;
    });

    try {
      final gps = ref.read(municipalGpsServiceProvider);
      final position = await gps.capturePosition();
      await ref.read(fiscalCollectionRepositoryProvider).recordFieldVisit(
            operatorId: widget.operatorId,
            visitType: visitType,
            latitude: position.latitude,
            longitude: position.longitude,
            notes: _notesController.text.trim(),
          );

      if (!mounted) return;
      setState(() => _success = 'Contrôle terrain enregistré');
    } on MunicipalGpsException catch (e) {
      setState(() => _error = e.message);
    } on DioException catch (e) {
      setState(() => _error = resolveApiErrorMessage(e));
    } catch (e) {
      setState(() => _error = resolveApiErrorMessage(e));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final summaryAsync = ref.watch(fiscalDetailedSummaryProvider(widget.operatorId));

    return Scaffold(
      appBar: AppBar(title: const Text('Contrôle terrain')),
      body: summaryAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erreur : $e')),
        data: (summary) {
          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Text('Fiche commerce', style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 8),
              Text(summary.commercialName, style: Theme.of(context).textTheme.headlineSmall),
              Text('Réf. ${summary.publicId}'),
              Text('Activité : ${summary.activityLabel}'),
              Text('Quartier : ${summary.quartier}'),
              const Divider(height: 32),
              const Text('Type de contrôle', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              ...fieldControlTypes.map(
                (type) {
                  final selected = _selectedType == type.value;
                  return ListTile(
                    leading: Icon(selected ? Icons.radio_button_checked : Icons.radio_button_off),
                    title: Text(type.label),
                    onTap: _loading
                        ? null
                        : () => setState(() => _selectedType = type.value),
                  );
                },
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _notesController,
                maxLines: 3,
                decoration: const InputDecoration(
                  labelText: 'Observations (optionnel)',
                  border: OutlineInputBorder(),
                ),
              ),
              if (_error != null) ...[
                const SizedBox(height: 12),
                Text(_error!, style: const TextStyle(color: Colors.red)),
              ],
              if (_success != null) ...[
                const SizedBox(height: 12),
                Text(_success!, style: const TextStyle(color: Colors.green)),
              ],
              const SizedBox(height: 20),
              FilledButton(
                onPressed: _loading ? null : _submit,
                child: _loading
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Enregistrer le contrôle'),
              ),
            ],
          );
        },
      ),
    );
  }
}
