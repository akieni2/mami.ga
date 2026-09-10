import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';

class CheckersBoard extends StatelessWidget {
  const CheckersBoard({
    required this.board,
    required this.selected,
    required this.onTapSquare,
    this.myColor = 'white',
    super.key,
  });

  final List<dynamic> board;
  final List<int>? selected; // [r,c]
  final void Function(int r, int c) onTapSquare;
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
                  gridDelegate:
                      const SliverGridDelegateWithFixedCrossAxisCount(
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
                    final isWhite = cellData?['c'] == 'w';
                    final isKing = cellData?['k'] == true;

                    return GestureDetector(
                      onTap: dark ? () => onTapSquare(r, c) : null,
                      child: Container(
                        decoration: BoxDecoration(
                          color: isSelected
                              ? AppTheme.accent.withValues(alpha: 0.75)
                              : (dark
                                  ? AppTheme.boardDark
                                  : AppTheme.boardLight),
                          border: Border.all(color: Colors.black12),
                        ),
                        child: cellData == null
                            ? null
                            : Center(
                                child: _PieceToken(
                                  size: pieceSize,
                                  isWhite: isWhite,
                                  isKing: isKing,
                                ),
                              ),
                      ),
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
}

class _PieceToken extends StatelessWidget {
  const _PieceToken({
    required this.size,
    required this.isWhite,
    required this.isKing,
  });

  final double size;
  final bool isWhite;
  final bool isKing;

  @override
  Widget build(BuildContext context) {
    final rim = isWhite ? const Color(0xFF5D4037) : const Color(0xFFFFF8E1);

    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(
          color: isKing ? AppTheme.accent : rim,
          width: isKing ? 3.5 : 2,
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
