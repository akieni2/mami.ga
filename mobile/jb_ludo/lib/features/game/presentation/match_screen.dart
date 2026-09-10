import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_exception.dart';
import '../data/jb_ludo_repository.dart';
import 'checkers_board.dart';
import 'ludo_board.dart';

class MatchScreen extends ConsumerStatefulWidget {
  const MatchScreen({required this.matchId, super.key});

  final int matchId;

  @override
  ConsumerState<MatchScreen> createState() => _MatchScreenState();
}

class _MatchScreenState extends ConsumerState<MatchScreen>
    with WidgetsBindingObserver {
  Map<String, dynamic>? _match;
  List<int>? _selected;
  final List<Map<String, int>> _path = [];
  Timer? _poll;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _refresh();
    _poll = Timer.periodic(
        const Duration(seconds: 3), (_) => _refresh(silent: true));
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _poll?.cancel();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    final repo = ref.read(jbLudoRepositoryProvider);
    if (state == AppLifecycleState.paused) {
      repo.disconnect(widget.matchId);
    } else if (state == AppLifecycleState.resumed) {
      repo.reconnect(widget.matchId).then((_) => _refresh());
    }
  }

  Future<void> _refresh({bool silent = false}) async {
    try {
      final match =
          await ref.read(jbLudoRepositoryProvider).fetchMatch(widget.matchId);
      if (mounted) setState(() => _match = match);
    } catch (_) {
      if (!silent && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Impossible de charger la partie')));
      }
    }
  }

  Future<void> _onTap(int r, int c) async {
    if (_busy || _match == null || _match!['status'] == 'finished') return;

    if (_selected == null) {
      setState(() {
        _selected = [r, c];
        _path
          ..clear()
          ..add({'r': r, 'c': c});
      });
      return;
    }

    setState(() {
      _path.add({'r': r, 'c': c});
      _selected = [r, c];
    });
  }

  Future<void> _submitPath() async {
    if (_path.length < 2) return;
    setState(() => _busy = true);
    try {
      final updated = await ref
          .read(jbLudoRepositoryProvider)
          .playMove(widget.matchId, List.from(_path));
      if (mounted) {
        setState(() {
          _match = updated;
          _selected = null;
          _path.clear();
        });
      }
    } catch (e) {
      if (mounted) {
        _showError(e);
        setState(() {
          _selected = null;
          _path.clear();
        });
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _rollLudoDice() async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      final updated = await ref
          .read(jbLudoRepositoryProvider)
          .playLudoAction(widget.matchId, {'action': 'roll'});
      if (mounted) setState(() => _match = updated);
    } catch (e) {
      if (mounted) _showError(e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _moveLudoPiece(int piece) async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      final updated = await ref
          .read(jbLudoRepositoryProvider)
          .playLudoAction(widget.matchId, {
        'action': 'move',
        'piece': piece,
      });
      if (mounted) setState(() => _match = updated);
    } catch (e) {
      if (mounted) _showError(e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _showError(Object error) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(_errorMessage(error))),
    );
  }

  String _errorMessage(Object error) {
    if (error is DioException && error.error is ApiException) {
      return (error.error as ApiException).message;
    }

    if (error is ApiException) return error.message;

    return error
        .toString()
        .replaceFirst('Exception: ', '')
        .replaceFirst('ApiException: ', '')
        .replaceFirst('DioException [bad response]: null\nError: ', '')
        .replaceFirst('DioException [unknown]: null\nError: ', '');
  }

  List<int> _legalLudoPieces(Map<String, dynamic> board, String? myColor) {
    if (myColor == null ||
        board['turn'] != myColor ||
        board['must_roll'] == true ||
        board['dice'] == null) {
      return [];
    }

    final dice = (board['dice'] as num?)?.toInt() ?? 0;
    final players = Map<String, dynamic>.from((board['players'] as Map?) ?? {});
    final mine = Map<String, dynamic>.from((players[myColor] as Map?) ?? {});
    final pieces = List<dynamic>.from((mine['pieces'] as List?) ?? const []);
    final legal = <int>[];

    for (var i = 0; i < pieces.length && i < 4; i++) {
      final position = (pieces[i] as num?)?.toInt() ?? -1;
      if (position < 0 && dice != 6) continue;
      if (position >= 57) continue;
      if (position >= 0 && position + dice > 57) continue;
      legal.add(i);
    }

    return legal;
  }

  Future<void> _resign() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Abandonner ?'),
        content: const Text('Vous perdrez la partie (−5 points).'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Annuler')),
          FilledButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Abandonner')),
        ],
      ),
    );
    if (ok == true) {
      final updated =
          await ref.read(jbLudoRepositoryProvider).resign(widget.matchId);
      if (mounted) setState(() => _match = updated);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_match == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    final gameType = _match!['game_type']?.toString() ?? 'damier';
    final status = _match!['status']?.toString() ?? '';
    final board =
        Map<String, dynamic>.from((_match!['board_state'] as Map?) ?? {});
    final ludoTurn = board['turn']?.toString();
    final turn = gameType == 'ludo'
        ? (ludoTurn ?? '')
        : (_match!['turn_color']?.toString() ?? '');
    final myColor = _match!['my_color']?.toString();
    final legalLudoPieces =
        gameType == 'ludo' ? _legalLudoPieces(board, myColor) : <int>[];
    final mustRoll = board['must_roll'] != false;

    return Scaffold(
      appBar: AppBar(
        title: Text(_match!['reference']?.toString() ?? 'Partie'),
        actions: [
          if (status != 'finished')
            TextButton(
                onPressed: _resign,
                child: const Text('Abandon',
                    style: TextStyle(color: Colors.white))),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text('Statut : $status · Tour : $turn'),
          Text(
              'Temps B/N : ${_match!['white_time_left']}s / ${_match!['black_time_left']}s'),
          if (status == 'grace')
            Text(
              'Reconnexion jusqu\'à ${_match!['grace_until']}',
              style: const TextStyle(color: Colors.orange),
            ),
          const SizedBox(height: 12),
          if (gameType == 'ludo')
            LudoBoard(
              board: board,
              myColor: myColor,
              legalPieces: legalLudoPieces,
              onPieceTap: _moveLudoPiece,
            )
          else
            CheckersBoard(
              board: (_match!['board_state'] as List?) ?? [],
              selected: _selected,
              onTapSquare: _onTap,
            ),
          const SizedBox(height: 12),
          if (status != 'finished' && gameType == 'ludo') ...[
            FilledButton.icon(
              onPressed:
                  _busy || myColor != turn || !mustRoll ? null : _rollLudoDice,
              icon: const Icon(Icons.casino_outlined),
              label: const Text('Lancer le de'),
            ),
            const SizedBox(height: 8),
            Text(
              legalLudoPieces.isEmpty && !mustRoll && myColor == turn
                  ? 'Aucun de vos pions ne peut jouer ce de. Le tour passera automatiquement si nécessaire.'
                  : 'Votre couleur : ${myColor ?? '-'} · Lancez le de, puis touchez seulement un pion actif.',
            ),
          ] else if (status != 'finished') ...[
            Text(
                'Chemin : ${_path.map((p) => '${p['r']},${p['c']}').join(' → ')}'),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => setState(() {
                      _selected = null;
                      _path.clear();
                    }),
                    child: const Text('Annuler sélection'),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: FilledButton(
                    onPressed: _busy ? null : _submitPath,
                    child: const Text('Valider le coup'),
                  ),
                ),
              ],
            ),
          ] else
            Text(
              'Résultat : ${_match!['result'] ?? '—'} (${_match!['result_reason'] ?? ''})',
              style: Theme.of(context).textTheme.titleMedium,
            ),
        ],
      ),
    );
  }
}
