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
    return AspectRatio(
      aspectRatio: 1,
      child: DecoratedBox(
        decoration: BoxDecoration(
          border: Border.all(color: Colors.black87, width: 2),
        ),
        child: GridView.builder(
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
            final cell = _cellAt(r, c);
            final isSelected =
                selected != null && selected![0] == r && selected![1] == c;

            return GestureDetector(
              onTap: dark ? () => onTapSquare(r, c) : null,
              child: Container(
                decoration: BoxDecoration(
                  color: isSelected
                      ? AppTheme.accent.withValues(alpha: 0.7)
                      : (dark ? AppTheme.boardDark : AppTheme.boardLight),
                  border: Border.all(color: Colors.black12),
                ),
                child: cell == null
                    ? null
                    : Center(
                        child: Container(
                          width: 22,
                          height: 22,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: cell['c'] == 'w'
                                ? Colors.white
                                : Colors.grey.shade900,
                            border: Border.all(
                              color: cell['k'] == true
                                  ? AppTheme.accent
                                  : Colors.black26,
                              width: cell['k'] == true ? 3 : 1,
                            ),
                          ),
                        ),
                      ),
              ),
            );
          },
        ),
      ),
    );
  }
}
