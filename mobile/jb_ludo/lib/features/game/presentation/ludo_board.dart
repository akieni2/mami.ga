import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';

/// Plateau Ludo 15×15 : cases visibles, pions positionnés, dé hors plateau.
class LudoBoard extends StatelessWidget {
  const LudoBoard({
    required this.board,
    required this.onPieceTap,
    required this.myColors,
    required this.activeColor,
    required this.legalPieces,
    super.key,
  });

  final Map<String, dynamic> board;
  final List<String> myColors;
  final String activeColor;
  final List<int> legalPieces;
  final void Function(int piece) onPieceTap;

  static const _colors = ['red', 'blue', 'green', 'yellow'];

  static const _colorMap = {
    'red': Colors.red,
    'blue': Colors.blue,
    'green': Colors.green,
    'yellow': Color(0xFFE6A700),
  };

  static const _labels = {
    'red': 'Rouge',
    'blue': 'Bleu',
    'green': 'Vert',
    'yellow': 'Jaune',
  };

  /// Parcours global 0..51 (aligné sur LudoEngineService START_OFFSETS).
  static const List<List<int>> track = [
    [6, 1], [6, 2], [6, 3], [6, 4], [6, 5],
    [5, 6], [4, 6], [3, 6], [2, 6], [1, 6], [0, 6],
    [0, 7],
    [0, 8], [1, 8], [2, 8], [3, 8], [4, 8], [5, 8],
    [6, 9], [6, 10], [6, 11], [6, 12], [6, 13], [6, 14],
    [7, 14],
    [8, 14], [8, 13], [8, 12], [8, 11], [8, 10], [8, 9],
    [9, 8], [10, 8], [11, 8], [12, 8], [13, 8], [14, 8],
    [14, 7],
    [14, 6], [13, 6], [12, 6], [11, 6], [10, 6], [9, 6],
    [8, 5], [8, 4], [8, 3], [8, 2], [8, 1], [8, 0],
    [7, 0],
    [6, 0],
  ];

  static const Map<String, int> startOffsets = {
    'red': 0,
    'blue': 13,
    'green': 26,
    'yellow': 39,
  };

  static const Map<String, List<List<int>>> homeStretch = {
    'red': [
      [7, 1], [7, 2], [7, 3], [7, 4], [7, 5], [7, 6]
    ],
    'blue': [
      [1, 7], [2, 7], [3, 7], [4, 7], [5, 7], [6, 7]
    ],
    'green': [
      [7, 13], [7, 12], [7, 11], [7, 10], [7, 9], [7, 8]
    ],
    'yellow': [
      [13, 7], [12, 7], [11, 7], [10, 7], [9, 7], [8, 7]
    ],
  };

  static const Map<String, List<List<int>>> yardSlots = {
    'red': [
      [1, 1], [1, 3], [3, 1], [3, 3]
    ],
    'blue': [
      [1, 10], [1, 12], [3, 10], [3, 12]
    ],
    'green': [
      [10, 10], [10, 12], [12, 10], [12, 12]
    ],
    'yellow': [
      [10, 1], [10, 3], [12, 1], [12, 3]
    ],
  };

  static const Map<String, List<int>> homeBounds = {
    'red': [0, 0, 6, 6],
    'blue': [0, 9, 6, 15],
    'green': [9, 9, 15, 15],
    'yellow': [9, 0, 15, 6],
  };

  @override
  Widget build(BuildContext context) {
    final turn = board['turn']?.toString() ?? 'red';
    final players = Map<String, dynamic>.from((board['players'] as Map?) ?? {});

    return AspectRatio(
      aspectRatio: 1,
      child: LayoutBuilder(
        builder: (context, constraints) {
          final size = constraints.biggest.shortestSide;
          final cell = size / 15;

          return Container(
            decoration: BoxDecoration(
              color: const Color(0xFFF7F3E8),
              border: Border.all(color: Colors.black87, width: 3),
            ),
            child: Stack(
              children: [
                CustomPaint(
                  size: Size(size, size),
                  painter: _LudoGridPainter(turn: turn),
                ),
                for (final color in _colors)
                  ..._buildPieces(
                    color: color,
                    players: players,
                    cell: cell,
                  ),
              ],
            ),
          );
        },
      ),
    );
  }

  List<Widget> _buildPieces({
    required String color,
    required Map<String, dynamic> players,
    required double cell,
  }) {
    final data = Map<String, dynamic>.from((players[color] as Map?) ?? {});
    final pieces = List<dynamic>.from((data['pieces'] as List?) ?? const []);
    final paint = _colorMap[color]!;
    final isActiveHuman =
        myColors.contains(color) && color == activeColor;
    final widgets = <Widget>[];

    for (var i = 0; i < 4; i++) {
      final position = i < pieces.length ? (pieces[i] as num?)?.toInt() ?? -1 : -1;
      final cellPos = _cellFor(color, position, i);
      if (cellPos == null) continue;

      final canMove = isActiveHuman && legalPieces.contains(i);
      final left = cellPos[1] * cell + cell * 0.12;
      final top = cellPos[0] * cell + cell * 0.12;
      final diameter = cell * 0.76;

      widgets.add(
        Positioned(
          left: left,
          top: top,
          width: diameter,
          height: diameter,
          child: GestureDetector(
            onTap: canMove ? () => onPieceTap(i) : null,
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 220),
              decoration: BoxDecoration(
                color: paint,
                shape: BoxShape.circle,
                border: Border.all(
                  color: canMove ? Colors.white : Colors.black54,
                  width: canMove ? 3 : 1.5,
                ),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: canMove ? 0.35 : 0.18),
                    blurRadius: canMove ? 6 : 3,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              alignment: Alignment.center,
              child: Text(
                '${i + 1}',
                style: TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  fontSize: cell * 0.32,
                ),
              ),
            ),
          ),
        ),
      );
    }

    return widgets;
  }

  List<int>? _cellFor(String color, int position, int pieceIndex) {
    if (position < 0) {
      final yard = yardSlots[color]!;
      return yard[pieceIndex.clamp(0, 3)];
    }
    if (position >= 52) {
      final stretch = homeStretch[color]!;
      final idx = (position - 52).clamp(0, stretch.length - 1);
      return stretch[idx];
    }
    final global = (startOffsets[color]! + position) % 52;
    return track[global];
  }
}

class _LudoGridPainter extends CustomPainter {
  _LudoGridPainter({required this.turn});

  final String turn;

  @override
  void paint(Canvas canvas, Size size) {
    final cell = size.width / 15;
    final fill = Paint()..style = PaintingStyle.fill;
    final stroke = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1.1
      ..color = Colors.black87;

    void rect(int r0, int c0, int r1, int c1, Color color) {
      fill.color = color;
      canvas.drawRect(
        Rect.fromLTWH(c0 * cell, r0 * cell, (c1 - c0) * cell, (r1 - r0) * cell),
        fill,
      );
    }

    // Maisons
    rect(0, 0, 6, 6, Colors.red.shade100);
    rect(0, 9, 6, 15, Colors.blue.shade100);
    rect(9, 9, 15, 15, Colors.green.shade100);
    rect(9, 0, 15, 6, const Color(0xFFFFF0B3));

    // Cadres maisons
    final homeStroke = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;
    homeStroke.color = Colors.red;
    canvas.drawRect(Rect.fromLTWH(0, 0, 6 * cell, 6 * cell), homeStroke);
    homeStroke.color = Colors.blue;
    canvas.drawRect(Rect.fromLTWH(9 * cell, 0, 6 * cell, 6 * cell), homeStroke);
    homeStroke.color = Colors.green;
    canvas.drawRect(
        Rect.fromLTWH(9 * cell, 9 * cell, 6 * cell, 6 * cell), homeStroke);
    homeStroke.color = const Color(0xFFE6A700);
    canvas.drawRect(
        Rect.fromLTWH(0, 9 * cell, 6 * cell, 6 * cell), homeStroke);

    // Couloirs / cases du parcours
    for (final pos in LudoBoard.track) {
      final r = pos[0];
      final c = pos[1];
      fill.color = Colors.white;
      final square = Rect.fromLTWH(c * cell, r * cell, cell, cell);
      canvas.drawRect(square, fill);
      canvas.drawRect(square, stroke);
    }

    // Couloirs d'arrivée
    for (final entry in LudoBoard.homeStretch.entries) {
      final color = LudoBoard._colorMap[entry.key]!;
      for (final pos in entry.value) {
        fill.color = color.withValues(alpha: 0.35);
        final square = Rect.fromLTWH(pos[1] * cell, pos[0] * cell, cell, cell);
        canvas.drawRect(square, fill);
        canvas.drawRect(square, stroke);
      }
    }

    // Centre
    fill.color = AppTheme.primary.withValues(alpha: 0.9);
    canvas.drawCircle(Offset(size.width / 2, size.height / 2), cell * 1.15, fill);
    final centerStroke = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 2
      ..color = Colors.white;
    canvas.drawCircle(
        Offset(size.width / 2, size.height / 2), cell * 1.15, centerStroke);

    // Cases de départ (sûres) marquées
    for (final offset in LudoBoard.startOffsets.values) {
      final pos = LudoBoard.track[offset];
      fill.color = Colors.black87;
      canvas.drawCircle(
        Offset(pos[1] * cell + cell / 2, pos[0] * cell + cell / 2),
        cell * 0.12,
        fill,
      );
    }

    // Labels maisons
    final tp = TextPainter(textDirection: TextDirection.ltr);
    void label(String text, double x, double y, Color color) {
      tp.text = TextSpan(
        text: text,
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w800,
          fontSize: cell * 0.55,
        ),
      );
      tp.layout();
      tp.paint(canvas, Offset(x, y));
    }

    label('Rouge', cell * 1.6, cell * 0.2, Colors.red.shade800);
    label('Bleu', cell * 10.7, cell * 0.2, Colors.blue.shade800);
    label('Vert', cell * 10.7, cell * 9.2, Colors.green.shade800);
    label('Jaune', cell * 1.6, cell * 9.2, const Color(0xFF9A7A00));

    // Indicateur tour sur le bord de la maison active
    final bounds = LudoBoard.homeBounds[turn];
    if (bounds != null) {
      final turnPaint = Paint()
        ..style = PaintingStyle.stroke
        ..strokeWidth = 4
        ..color = LudoBoard._colorMap[turn] ?? AppTheme.primary;
      canvas.drawRect(
        Rect.fromLTWH(
          bounds[1] * cell + 2,
          bounds[0] * cell + 2,
          (bounds[3] - bounds[1]) * cell - 4,
          (bounds[2] - bounds[0]) * cell - 4,
        ),
        turnPaint,
      );
    }
  }

  @override
  bool shouldRepaint(covariant _LudoGridPainter oldDelegate) =>
      oldDelegate.turn != turn;
}

/// Panneau latéral : dé + historique type horloge.
class LudoSidePanel extends StatelessWidget {
  const LudoSidePanel({
    required this.board,
    required this.myColors,
    required this.canRoll,
    required this.onRoll,
    required this.busy,
    super.key,
  });

  final Map<String, dynamic> board;
  final List<String> myColors;
  final bool canRoll;
  final VoidCallback? onRoll;
  final bool busy;

  static const _labels = LudoBoard._labels;
  static const _colorMap = LudoBoard._colorMap;

  @override
  Widget build(BuildContext context) {
    final turn = board['turn']?.toString() ?? 'red';
    final dice = board['dice'];
    final mustRoll = board['must_roll'] != false;
    final log = List<Map<String, dynamic>>.from(
      ((board['log'] as List?) ?? const []).map(
        (e) => Map<String, dynamic>.from(e as Map),
      ),
    ).reversed.take(12).toList();

    final isMyTurn = myColors.contains(turn);
    final waitingAi = myColors.isNotEmpty && !isMyTurn;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: (_colorMap[turn] ?? AppTheme.primary).withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: _colorMap[turn] ?? AppTheme.primary),
          ),
          child: Text(
            waitingAi
                ? 'Tour de l\'IA : ${_labels[turn] ?? turn}'
                : isMyTurn
                    ? 'À vous de jouer (${_labels[turn] ?? turn})'
                    : 'Tour : ${_labels[turn] ?? turn}',
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Container(
              width: 72,
              height: 72,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: AppTheme.primary,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.2),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Text(
                '${dice ?? '—'}',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 34,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    mustRoll ? 'Dé à lancer' : 'Dé lancé — touchez un pion',
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                  const SizedBox(height: 8),
                  FilledButton.icon(
                    onPressed: canRoll && !busy ? onRoll : null,
                    icon: const Icon(Icons.casino_outlined),
                    label: const Text('Lancer le dé'),
                  ),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        Text(
          'Horloge des coups',
          style: Theme.of(context).textTheme.titleSmall?.copyWith(
                fontWeight: FontWeight.w700,
              ),
        ),
        const SizedBox(height: 8),
        Container(
          height: 160,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.black12),
          ),
          child: log.isEmpty
              ? const Center(child: Text('Aucun coup encore'))
              : ListView.separated(
                  padding: const EdgeInsets.all(8),
                  itemCount: log.length,
                  separatorBuilder: (_, __) => const Divider(height: 1),
                  itemBuilder: (context, index) {
                    final entry = log[index];
                    final color = entry['color']?.toString() ?? '';
                    final type = entry['type']?.toString() ?? '';
                    final d = entry['dice'];
                    final piece = entry['piece'];
                    final from = entry['from'];
                    final to = entry['to'];
                    String detail;
                    switch (type) {
                      case 'roll':
                        detail = 'lance $d';
                        break;
                      case 'skip':
                        detail = 'passe (dé $d)';
                        break;
                      case 'move':
                        detail =
                            'pion ${(piece is num ? piece.toInt() : 0) + 1} : $from → $to';
                        break;
                      default:
                        detail = type;
                    }
                    return ListTile(
                      dense: true,
                      leading: CircleAvatar(
                        radius: 12,
                        backgroundColor: _colorMap[color] ?? Colors.grey,
                      ),
                      title: Text(
                        '${_labels[color] ?? color} $detail',
                        style: const TextStyle(fontSize: 13),
                      ),
                    );
                  },
                ),
        ),
      ],
    );
  }
}
