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
  Timer? _aiAdvanceTimer;
  bool _busy = false;
  bool _advancingAi = false;

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
    _aiAdvanceTimer?.cancel();
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
      if (mounted) {
        setState(() => _match = match);
        _scheduleAiAdvanceIfNeeded(match);
      }
    } catch (_) {
      if (!silent && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Impossible de charger la partie')));
      }
    }
  }

  List<String> _myColorsOf(Map<String, dynamic> match) {
    final raw = match['my_colors'];
    if (raw is List && raw.isNotEmpty) {
      return raw.map((e) => e.toString()).toList();
    }
    final single = match['my_color']?.toString();
    return single == null || single.isEmpty ? <String>[] : [single];
  }

  bool _isHumanTurn(Map<String, dynamic> match) {
    if (match['mode']?.toString() != 'solo') return true;
    if (match['game_type']?.toString() != 'ludo') return true;
    final board =
        Map<String, dynamic>.from((match['board_state'] as Map?) ?? {});
    final turn = board['turn']?.toString() ?? '';
    return _myColorsOf(match).contains(turn);
  }

  void _scheduleAiAdvanceIfNeeded(Map<String, dynamic> match) {
    if (match['status']?.toString() == 'finished') return;
    if (match['mode']?.toString() != 'solo') return;
    if (match['game_type']?.toString() != 'ludo') return;
    if (_isHumanTurn(match)) return;
    if (_advancingAi || _busy) return;
    // Ne pas réarmer le délai si un avancement IA est déjà planifié
    // (le polling 3s annulait sinon le délai d'affichage du dé).
    if (_aiAdvanceTimer?.isActive ?? false) return;

    final board =
        Map<String, dynamic>.from((match['board_state'] as Map?) ?? {});
    final ai = Map<String, dynamic>.from((board['_ai'] as Map?) ?? {});
    final phase = ai['phase']?.toString();
    final hasDiceToShow = board['dice'] != null && board['must_roll'] == false;

    // Laisser le temps de lire le dé IA avant le déplacement.
    final delayMs = (phase == 'show_dice' || hasDiceToShow) ? 1800 : 850;

    _aiAdvanceTimer = Timer(Duration(milliseconds: delayMs), _advanceAiOnce);
  }

  Future<void> _advanceAiOnce() async {
    if (!mounted || _advancingAi || _busy) return;
    final current = _match;
    if (current == null || _isHumanTurn(current)) return;
    if (current['status']?.toString() == 'finished') return;

    setState(() => _advancingAi = true);
    try {
      final updated =
          await ref.read(jbLudoRepositoryProvider).advanceAi(widget.matchId);
      if (!mounted) return;
      setState(() => _match = updated);
    } catch (_) {
      // Le polling reprendra si besoin.
    } finally {
      if (mounted) {
        setState(() => _advancingAi = false);
        if (_match != null) {
          _scheduleAiAdvanceIfNeeded(_match!);
        }
      }
    }
  }

  Future<void> _onTap(int r, int c) async {
    if (_busy || _match == null || _match!['status'] == 'finished') return;
    if (_match!['game_type']?.toString() == 'damier' && !_isMyCheckersTurn()) {
      _showMessage('Ce n\'est pas votre tour.');
      return;
    }

    if (_selected == null) {
      if (!_isLegalCheckersStart(r, c)) {
        _showMessage('Touchez un de vos pions surlignés.');
        return;
      }
      setState(() {
        _selected = [r, c];
        _path
          ..clear()
          ..add({'r': r, 'c': c});
      });
      return;
    }

    if (_isLegalCheckersStart(r, c)) {
      setState(() {
        _selected = [r, c];
        _path
          ..clear()
          ..add({'r': r, 'c': c});
      });
      return;
    }

    final from = _path.first;
    final legalPath = _legalCheckersPathBetween(
      from['r']!,
      from['c']!,
      r,
      c,
    );
    if (legalPath != null) {
      await _submitPath(legalPath);
      return;
    }

    setState(() {
      _path.add({'r': r, 'c': c});
      _selected = [r, c];
    });
  }

  Future<void> _onDragCheckersMove(
    int fromR,
    int fromC,
    int toR,
    int toC,
  ) async {
    if (_busy || _match == null || _match!['status'] == 'finished') return;
    if (!_isMyCheckersTurn()) {
      _showMessage('Ce n\'est pas votre tour.');
      return;
    }

    final legalPath = _legalCheckersPathBetween(fromR, fromC, toR, toC);
    if (legalPath == null) {
      _showMessage('Coup non autorisé.');
      return;
    }

    await _submitPath(legalPath);
  }

  Future<void> _submitPath([List<Map<String, int>>? forcedPath]) async {
    final path = forcedPath ?? List<Map<String, int>>.from(_path);
    if (path.length < 2) return;
    setState(() => _busy = true);
    try {
      final updated = await ref
          .read(jbLudoRepositoryProvider)
          .playMove(widget.matchId, path);
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

  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  Future<void> _rollLudoDice() async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      final updated = await ref
          .read(jbLudoRepositoryProvider)
          .playLudoAction(widget.matchId, {'action': 'roll'});
      if (mounted) {
        setState(() => _match = updated);
        _scheduleAiAdvanceIfNeeded(updated);
      }
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
      if (mounted) {
        setState(() => _match = updated);
        _scheduleAiAdvanceIfNeeded(updated);
      }
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

  List<int> _legalLudoPieces(
      Map<String, dynamic> board, List<String> myColors) {
    final turn = board['turn']?.toString();
    if (turn == null ||
        !myColors.contains(turn) ||
        board['must_roll'] == true ||
        board['dice'] == null) {
      return [];
    }

    final dice = (board['dice'] as num?)?.toInt() ?? 0;
    final players = Map<String, dynamic>.from((board['players'] as Map?) ?? {});
    final mine = Map<String, dynamic>.from((players[turn] as Map?) ?? {});
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

  bool _isMyCheckersTurn() {
    final match = _match;
    if (match == null) return false;
    if (match['game_type']?.toString() != 'damier') return false;
    return match['my_color']?.toString() == match['turn_color']?.toString();
  }

  List<List<Map<String, int>>> _legalCheckersPaths() {
    final rawMoves = _match?['legal_moves'];
    if (rawMoves is! List) return const [];

    final paths = <List<Map<String, int>>>[];
    for (final rawMove in rawMoves) {
      if (rawMove is! Map || rawMove['path'] is! List) continue;
      final path = <Map<String, int>>[];
      for (final rawSquare in rawMove['path'] as List) {
        if (rawSquare is! Map) continue;
        final r = (rawSquare['r'] as num?)?.toInt();
        final c = (rawSquare['c'] as num?)?.toInt();
        if (r == null || c == null) continue;
        path.add({'r': r, 'c': c});
      }
      if (path.length >= 2) paths.add(path);
    }

    return paths;
  }

  Set<String> _legalCheckersStartKeys() {
    return _legalCheckersPaths()
        .map((path) => '${path.first['r']}:${path.first['c']}')
        .toSet();
  }

  Set<String> _legalCheckersDestinationKeys() {
    final selected = _selected;
    final paths = _legalCheckersPaths();
    if (selected == null) {
      return paths.map((path) => '${path.last['r']}:${path.last['c']}').toSet();
    }

    return paths
        .where((path) =>
            path.first['r'] == selected[0] && path.first['c'] == selected[1])
        .map((path) => '${path.last['r']}:${path.last['c']}')
        .toSet();
  }

  bool _isLegalCheckersStart(int r, int c) {
    return _legalCheckersStartKeys().contains('$r:$c');
  }

  List<Map<String, int>>? _legalCheckersPathBetween(
    int fromR,
    int fromC,
    int toR,
    int toC,
  ) {
    for (final path in _legalCheckersPaths()) {
      if (path.first['r'] == fromR &&
          path.first['c'] == fromC &&
          path.last['r'] == toR &&
          path.last['c'] == toC) {
        return path;
      }
    }

    return null;
  }

  String _aiStatusText(
    Map<String, dynamic> board,
    List<String> myColors,
    String turn,
    bool isMyLudoTurn,
    List<int> legalLudoPieces,
    bool mustRoll,
  ) {
    const labels = {
      'red': 'Rouge',
      'blue': 'Bleu',
      'green': 'Vert',
      'yellow': 'Jaune',
    };
    if (isMyLudoTurn) {
      if (legalLudoPieces.isEmpty && !mustRoll) {
        return 'Aucun de vos pions ne peut jouer ce dé.';
      }
      return 'À vous (${labels[turn] ?? turn}). Lancez le dé, puis touchez un pion surligné.';
    }

    final ai = Map<String, dynamic>.from((board['_ai'] as Map?) ?? {});
    final reveal = Map<String, dynamic>.from((ai['reveal'] as Map?) ?? {});
    final dice = board['dice'] ?? reveal['dice'];
    final color = reveal['color']?.toString() ?? turn;
    final pending = reveal['pending_piece'] ?? ai['pending_piece'];
    final skipped = reveal['skipped'] == true;

    if (skipped) {
      return 'IA ${labels[color] ?? color} : dé $dice — aucun pion jouable, tour passé.';
    }
    if (dice != null && board['must_roll'] == false) {
      final pion =
          pending is num ? ' — pion ${pending.toInt() + 1} va jouer' : '';
      return 'IA ${labels[color] ?? color} a lancé $dice$pion.';
    }
    return 'Tour IA (${labels[turn] ?? turn}) — un siège à la fois.';
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
    final rawBoard = _match!['board_state'];
    // Ludo = objet {players, turn, …} ; Damier = grille 10×10 (liste).
    final board = rawBoard is Map
        ? Map<String, dynamic>.from(rawBoard)
        : <String, dynamic>{};
    final checkersGrid = _asCheckersGrid(rawBoard);
    final ludoTurn = board['turn']?.toString();
    final myColors = _myColorsOf(_match!);
    final turn = gameType == 'ludo'
        ? (ludoTurn ?? '')
        : (_match!['turn_color']?.toString() ?? '');
    final myColor = myColors.isEmpty ? _match!['my_color']?.toString() : turn;
    final legalLudoPieces =
        gameType == 'ludo' ? _legalLudoPieces(board, myColors) : <int>[];
    final mustRoll = board['must_roll'] != false;
    final isMyLudoTurn = gameType == 'ludo' && myColors.contains(turn);

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
          Text(
            gameType == 'ludo'
                ? 'Statut : $status'
                : 'Statut : $status · Tour : $turn',
          ),
          if (gameType != 'ludo')
            Text(
                'Temps B/N : ${_match!['white_time_left']}s / ${_match!['black_time_left']}s'),
          if (status == 'grace')
            Text(
              'Reconnexion jusqu\'à ${_match!['grace_until']}',
              style: const TextStyle(color: Colors.orange),
            ),
          const SizedBox(height: 12),
          if (gameType == 'ludo') ...[
            LudoBoard(
              board: board,
              myColors: myColors,
              activeColor: turn,
              legalPieces: legalLudoPieces,
              onPieceTap: _moveLudoPiece,
            ),
            const SizedBox(height: 12),
            LudoSidePanel(
              board: board,
              myColors: myColors,
              canRoll: status != 'finished' && isMyLudoTurn && mustRoll,
              onRoll: _rollLudoDice,
              busy: _busy || _advancingAi,
            ),
            if (status != 'finished') ...[
              const SizedBox(height: 8),
              Text(
                _advancingAi
                    ? 'L\'IA réfléchit…'
                    : _aiStatusText(board, myColors, turn, isMyLudoTurn,
                        legalLudoPieces, mustRoll),
              ),
            ],
          ] else
            CheckersBoard(
              board: checkersGrid,
              selected: _selected,
              onTapSquare: _onTap,
              onDragMove: _onDragCheckersMove,
              legalStarts: _legalCheckersStartKeys(),
              legalDestinations: _legalCheckersDestinationKeys(),
              myColor: myColor ?? 'white',
            ),
          const SizedBox(height: 12),
          if (status != 'finished' && gameType != 'ludo') ...[
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
                    onPressed: _busy ? null : () => _submitPath(),
                    child: const Text('Valider le coup'),
                  ),
                ),
              ],
            ),
          ] else if (status == 'finished')
            Text(
              'Résultat : ${_match!['result'] ?? '—'} (${_match!['result_reason'] ?? ''})',
              style: Theme.of(context).textTheme.titleMedium,
            ),
        ],
      ),
    );
  }

  /// Grille damier 10×10 depuis `board_state` (liste) ou objet encapsulé.
  List<dynamic> _asCheckersGrid(dynamic raw) {
    if (raw is List) return List<dynamic>.from(raw);
    if (raw is Map && raw['grid'] is List) {
      return List<dynamic>.from(raw['grid'] as List);
    }
    return const [];
  }
}
