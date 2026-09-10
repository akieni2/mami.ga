import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';

class LudoBoard extends StatelessWidget {
  const LudoBoard({
    required this.board,
    required this.onPieceTap,
    required this.myColor,
    required this.legalPieces,
    super.key,
  });

  final Map<String, dynamic> board;
  final String? myColor;
  final List<int> legalPieces;
  final void Function(int piece) onPieceTap;

  @override
  Widget build(BuildContext context) {
    final players = Map<String, dynamic>.from((board['players'] as Map?) ?? {});
    final red = Map<String, dynamic>.from((players['red'] as Map?) ?? {});
    final blue = Map<String, dynamic>.from((players['blue'] as Map?) ?? {});
    final green = Map<String, dynamic>.from((players['green'] as Map?) ?? {});
    final yellow = Map<String, dynamic>.from((players['yellow'] as Map?) ?? {});
    final redPieces = List<dynamic>.from((red['pieces'] as List?) ?? const []);
    final bluePieces =
        List<dynamic>.from((blue['pieces'] as List?) ?? const []);
    final greenPieces =
        List<dynamic>.from((green['pieces'] as List?) ?? const []);
    final yellowPieces =
        List<dynamic>.from((yellow['pieces'] as List?) ?? const []);

    return AspectRatio(
      aspectRatio: 1,
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          border: Border.all(color: Colors.black87, width: 8),
        ),
        child: Stack(
          children: [
            _home(
                alignment: Alignment.topLeft,
                playerColor: 'red',
                color: Colors.red,
                label: 'Rouge',
                pieces: redPieces),
            _home(
                alignment: Alignment.topRight,
                playerColor: 'green',
                color: Colors.green,
                label: 'Vert',
                pieces: greenPieces),
            _home(
                alignment: Alignment.bottomLeft,
                playerColor: 'yellow',
                color: Colors.amber,
                label: 'Jaune',
                pieces: yellowPieces),
            _home(
                alignment: Alignment.bottomRight,
                playerColor: 'blue',
                color: Colors.blue,
                label: 'Bleu',
                pieces: bluePieces),
            Center(
              child: Container(
                width: 96,
                height: 96,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: AppTheme.primary,
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white, width: 4),
                ),
                child: Text(
                  '${board['dice'] ?? '-'}',
                  style: const TextStyle(
                      fontSize: 36,
                      fontWeight: FontWeight.bold,
                      color: Colors.white),
                ),
              ),
            ),
            Align(
              alignment: Alignment.center,
              child: Transform.rotate(
                angle: -0.785,
                child: Container(
                    width: 280,
                    height: 36,
                    color: Colors.green.withValues(alpha: 0.2)),
              ),
            ),
            Align(
              alignment: Alignment.center,
              child: Transform.rotate(
                angle: 0.785,
                child: Container(
                    width: 280,
                    height: 36,
                    color: Colors.red.withValues(alpha: 0.15)),
              ),
            ),
            Positioned(
              left: 0,
              right: 0,
              top: 12,
              child: Center(
                child: Text(
                  'Tour: ${board['turn'] ?? 'red'}',
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _home({
    required Alignment alignment,
    required String playerColor,
    required Color color,
    required String label,
    required List<dynamic> pieces,
  }) {
    final isMine = playerColor == myColor;

    return Align(
      alignment: alignment,
      child: Container(
        width: 150,
        height: 150,
        margin: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.14),
          border: Border.all(color: color, width: 4),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(label,
                style: TextStyle(color: color, fontWeight: FontWeight.bold)),
            const SizedBox(height: 10),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: List.generate(4, (index) {
                final position = index < pieces.length ? pieces[index] : -1;
                final canMove = isMine && legalPieces.contains(index);
                return GestureDetector(
                  onTap: canMove ? () => onPieceTap(index) : null,
                  child: Opacity(
                    opacity: canMove || !isMine ? 1 : 0.35,
                    child: CircleAvatar(
                      radius: canMove ? 21 : 18,
                      backgroundColor: color,
                      child: Text(
                        position.toString(),
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 11,
                            fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                );
              }),
            ),
          ],
        ),
      ),
    );
  }
}
