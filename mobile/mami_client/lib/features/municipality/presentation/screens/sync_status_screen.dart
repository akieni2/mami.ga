import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../providers/fiscal_collection_providers.dart';

class SyncStatusScreen extends ConsumerStatefulWidget {
  const SyncStatusScreen({super.key});

  @override
  ConsumerState<SyncStatusScreen> createState() => _SyncStatusScreenState();
}

class _SyncStatusScreenState extends ConsumerState<SyncStatusScreen> {
  String? _lastSyncLabel;

  @override
  void initState() {
    super.initState();
    _loadLastSync();
  }

  Future<void> _loadLastSync() async {
    final prefs = await SharedPreferences.getInstance();
    if (!mounted) return;
    setState(() {
      _lastSyncLabel = prefs.getString('municipality.last_sync_at');
    });
  }

  Future<void> _refresh() async {
    ref.invalidate(municipalSyncStatusProvider);
    final status = await ref.read(municipalSyncStatusProvider.future);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('municipality.last_sync_at', status.serverTime);
    if (!mounted) return;
    setState(() => _lastSyncLabel = status.serverTime);
  }

  @override
  Widget build(BuildContext context) {
    final syncAsync = ref.watch(municipalSyncStatusProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Synchronisation'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _refresh,
          ),
        ],
      ),
      body: syncAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text('État API : indisponible\n$e', textAlign: TextAlign.center),
                const SizedBox(height: 16),
                FilledButton(onPressed: _refresh, child: const Text('Réessayer')),
              ],
            ),
          ),
        ),
        data: (status) {
          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Dernière synchro',
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 8),
                      Text(_lastSyncLabel ?? 'Jamais synchronisé'),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 12),
              _StatTile(label: 'Nombre de commerces', value: '${status.operatorsCount}'),
              _StatTile(label: 'Nombre de paiements', value: '${status.paymentsCount}'),
              _StatTile(label: 'Nombre de quittances', value: '${status.receiptsCount}'),
              const SizedBox(height: 12),
              Card(
                child: ListTile(
                  leading: Icon(
                    status.isApiOk ? Icons.cloud_done_outlined : Icons.cloud_off_outlined,
                    color: status.isApiOk ? Colors.green : Colors.red,
                  ),
                  title: const Text('État API'),
                  subtitle: Text(status.isApiOk ? 'Connecté' : status.apiStatus),
                ),
              ),
              const SizedBox(height: 20),
              const Text(
                'Mode hors ligne prévu en V2.1 — les compteurs reflètent les données serveur.',
                style: TextStyle(color: Colors.grey),
              ),
              const SizedBox(height: 16),
              FilledButton.icon(
                onPressed: _refresh,
                icon: const Icon(Icons.sync),
                label: const Text('Synchroniser maintenant'),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _StatTile extends StatelessWidget {
  const _StatTile({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        title: Text(label),
        trailing: Text(value, style: const TextStyle(fontWeight: FontWeight.bold)),
      ),
    );
  }
}
