import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';

class CheckersBoard extends StatelessWidget {
  const CheckersBoard({
    required this.board,
    required this.selected,
    required this.onTapSquare,
    required this.onDragMove,
    required this.legalStarts,
    required this.legalDestinations,
    this.myColor = 'white',
    super.key,
  });

  final List<dynamic> board;
  final List<int>? selected; // [r,c]
  final void Function(int r, int c) onTapSquare;
  final void Function(int fromR, int fromC, int toR, int toC) onDragMove;
  final Set<String> legalStarts;
  final Set<String> legalDestinations;
  final String myColor;

  /// Blancs (joueur) — ivoire bien visible sur case sombre.
  static const Color whitePiece = Color(0xFFFFF3D6);

  /// Noirs (adversaire) — rouge distinct, jamais camouflé sur le damier.
  static const Color blackPiece = Color(0xFFC62828);

  Map<String, dynamic>? _cellAt(int r, int c) {
    if (board.length <= r) return null;
    final row = board[r];
    if (row is! List || row.length <= c) return null;
    final cell = row[c];
    if (cell == null) return null;
    if (cell is Map) return Map<String, dynamic>.from(cell);
    return null;
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        AspectRatio(
          aspectRatio: 1,
          child: DecoratedBox(
            decoration: BoxDecoration(
              border: Border.all(color: Colors.black87, width: 2),
            ),
            child: LayoutBuilder(
              builder: (context, constraints) {
                final cell = constraints.maxWidth / 10;
                final pieceSize = cell * 0.72;

                return GridView.builder(
                  physics: const NeverScrollableScrollPhysics(),
                  padding: EdgeInsets.zero,
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 10,
                  ),
                  itemCount: 100,
                  itemBuilder: (context, index) {
                    final r = index ~/ 10;
                    final c = index % 10;
                    final dark = (r + c) % 2 == 1;
                    final cellData = _cellAt(r, c);
                    final isSelected = selected != null &&
                        selected![0] == r &&
                        selected![1] == c;
                    final squareKey = '$r:$c';
                    final canStartMove = legalStarts.contains(squareKey);
                    final canEndMove = legalDestinations.contains(squareKey);
                    final isWhite = cellData?['c'] == 'w';
                    final isKing = cellData?['k'] == true;

                    final square = GestureDetector(
                      onTap: dark ? () => onTapSquare(r, c) : null,
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 120),
                        decoration: BoxDecoration(
                          color: _squareColor(
                            dark: dark,
                            isSelected: isSelected,
                            canStartMove: canStartMove,
                            canEndMove: canEndMove,
                          ),
                          border: Border.all(
                            color:
                                canEndMove ? AppTheme.accent : Colors.black12,
                            width: canEndMove ? 2 : 1,
                          ),
                        ),
                        child: _pieceChild(
                          cellData: cellData,
                          canStartMove: canStartMove,
                          pieceSize: pieceSize,
                          isWhite: isWhite,
                          isKing: isKing,
                          r: r,
                          c: c,
                        ),
                      ),
                    );

                    if (!dark) return square;

                    return DragTarget<List<int>>(
                      onWillAcceptWithDetails: (_) => canEndMove,
                      onAcceptWithDetails: (details) {
                        final from = details.data;
                        if (from.length < 2) return;
                        onDragMove(from[0], from[1], r, c);
                      },
                      builder: (context, candidateData, rejectedData) {
                        return DecoratedBox(
                          decoration: BoxDecoration(
                            boxShadow: candidateData.isEmpty
                                ? null
                                : [
                                    BoxShadow(
                                      color: AppTheme.accent
                                          .withValues(alpha: 0.4),
                                      blurRadius: 8,
                                      spreadRadius: 1,
                                    ),
                                  ],
                          ),
                          child: square,
                        );
                      },
                    );
                  },
                );
              },
            ),
          ),
        ),
        const SizedBox(height: 10),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            _LegendDot(color: whitePiece, label: 'Blancs'),
            const SizedBox(width: 20),
            _LegendDot(color: blackPiece, label: 'Noirs'),
          ],
        ),
      ],
    );
  }

  Color _squareColor({
    required bool dark,
    required bool isSelected,
    required bool canStartMove,
    required bool canEndMove,
  }) {
    if (isSelected) return AppTheme.accent.withValues(alpha: 0.75);
    if (canEndMove) return AppTheme.accent.withValues(alpha: 0.28);
    if (canStartMove) return AppTheme.primary.withValues(alpha: 0.25);
    return dark ? AppTheme.boardDark : AppTheme.boardLight;
  }

  Widget? _pieceChild({
    required Map<String, dynamic>? cellData,
    required bool canStartMove,
    required double pieceSize,
    required bool isWhite,
    required bool isKing,
    required int r,
    required int c,
  }) {
    if (cellData == null) return null;

    final token = _PieceToken(
      size: pieceSize,
      isWhite: isWhite,
      isKing: isKing,
      isPlayable: canStartMove,
    );

    if (!canStartMove) return Center(child: token);

    return Center(
      child: Draggable<List<int>>(
        data: [r, c],
        feedback: Material(
          color: Colors.transparent,
          child: _PieceToken(
            size: pieceSize,
            isWhite: isWhite,
            isKing: isKing,
            isPlayable: true,
          ),
        ),
        childWhenDragging: Opacity(opacity: 0.25, child: token),
        child: token,
      ),
    );
  }
}

class _PieceToken extends StatelessWidget {
  const _PieceToken({
    required this.size,
    required this.isWhite,
    required this.isKing,
    required this.isPlayable,
  });

  final double size;
  final bool isWhite;
  final bool isKing;
  final bool isPlayable;

  @override
  Widget build(BuildContext context) {
    final rim = isWhite ? const Color(0xFF5D4037) : const Color(0xFFFFF8E1);

    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(
          color:
              isPlayable ? AppTheme.primary : (isKing ? AppTheme.accent : rim),
          width: isPlayable || isKing ? 3.5 : 2,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.35),
            blurRadius: 3,
            offset: const Offset(0, 1.5),
          ),
        ],
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: isWhite
              ? const [Color(0xFFFFFBF0), Color(0xFFE8D5A3)]
              : const [Color(0xFFE53935), Color(0xFF8E0000)],
        ),
      ),
      alignment: Alignment.center,
      child: isKing
          ? Icon(
              Icons.star_rounded,
              size: size * 0.45,
              color: isWhite ? const Color(0xFF5D4037) : Colors.amber.shade200,
            )
          : null,
    );
  }
}

class _LegendDot extends StatelessWidget {
  const _LegendDot({required this.color, required this.label});

  final Color color;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 16,
          height: 16,
          decoration: BoxDecoration(
            color: color,
            shape: BoxShape.circle,
            border: Border.all(color: Colors.black54),
          ),
        ),
        const SizedBox(width: 6),
        Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
      ],
    );
  }
}
