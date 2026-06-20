import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:mami_client/core/network/api_exception.dart';
import 'package:mami_client/features/municipality/data/fiscal_collection_repository.dart';
import 'package:mami_client/features/municipality/domain/operator_qr_lookup.dart';
import 'package:mami_client/features/municipality/domain/qr_scan_token_parser.dart';
import 'package:mami_client/features/municipality/presentation/screens/scan_operator_screen.dart';

class _MockFiscalCollectionRepository extends FiscalCollectionRepository {
  _MockFiscalCollectionRepository(this.onLookup)
      : super(Dio(BaseOptions(baseUrl: 'http://test')));

  final Future<Map<String, dynamic>> Function(String token) onLookup;

  @override
  Future<Map<String, dynamic>> lookupOperatorByQr(String qrValue) => onLookup(qrValue);
}

void main() {
  group('parseQrScanToken', () {
    test('accepts UUID v4 (QR production actuels)', () {
      const uuid = '550e8400-e29b-41d4-a716-446655440000';
      expect(parseQrScanToken(uuid), uuid);
    });

    test('accepts JSON avec uuid et public_id', () {
      const json =
          '{"public_id":"OWE-COM-00000001","uuid":"550e8400-e29b-41d4-a716-446655440000"}';
      expect(
        parseQrScanToken(json),
        '550e8400-e29b-41d4-a716-446655440000',
      );
    });

    test('accepts libellé composite QR-OWE-COM', () {
      const composite = 'QR-OWE-COM-00000001-A1B2C3D4';
      expect(parseQrScanToken(composite), composite);
    });

    test('accepte public_id seul pour diagnostic terrain', () {
      expect(parseQrScanToken('OWE-COM-00000001'), 'OWE-COM-00000001');
    });

    test('rejette contenu invalide', () {
      expect(parseQrScanToken('pas-un-qr'), isNull);
      expect(parseQrScanToken(''), isNull);
    });
  });

  group('lookupOperatorIdByQr', () {
    test('scan réussi retourne l\'identifiant commerce', () async {
      final operatorId = await lookupOperatorIdByQr(
        lookup: (_) async => {
          'operator': {'id': 7},
        },
        rawPayload: '550e8400-e29b-41d4-a716-446655440000',
      );

      expect(operatorId, 7);
    });

    test('QR invalide', () async {
      expect(
        () => lookupOperatorIdByQr(
          lookup: (_) async => {},
          rawPayload: 'texte-invalide',
        ),
        throwsA(
          isA<OperatorQrLookupException>().having(
            (e) => e.message,
            'message',
            'QR non reconnu',
          ),
        ),
      );
    });

    test('commerce introuvable (404)', () async {
      expect(
        () => lookupOperatorIdByQr(
          lookup: (_) => throw DioException(
            requestOptions: RequestOptions(path: '/test'),
            response: Response(
              requestOptions: RequestOptions(path: '/test'),
              statusCode: 404,
            ),
            error: ApiException('QR commerce introuvable ou inactif.', statusCode: 404),
          ),
          rawPayload: '550e8400-e29b-41d4-a716-446655440000',
        ),
        throwsA(
          isA<OperatorQrLookupException>().having(
            (e) => e.message,
            'message',
            'Commerce introuvable',
          ),
        ),
      );
    });

    test('absence de réseau', () async {
      expect(
        () => lookupOperatorIdByQr(
          lookup: (_) => throw DioException(
            requestOptions: RequestOptions(path: '/test'),
            type: DioExceptionType.connectionError,
          ),
          rawPayload: '550e8400-e29b-41d4-a716-446655440000',
        ),
        throwsA(
          isA<OperatorQrLookupException>().having(
            (e) => e.message,
            'message',
            'Connexion réseau indisponible',
          ),
        ),
      );
    });
  });

  group('ScanOperatorScreen', () {
    Future<void> pumpScanScreen(
      WidgetTester tester, {
      required _MockFiscalCollectionRepository repo,
    }) async {
      final router = GoRouter(
        routes: [
          GoRoute(
            path: '/',
            builder: (context, state) => const ScanOperatorScreen(),
          ),
          GoRoute(
            path: '/municipality/recovery/scan/camera',
            builder: (context, state) =>
                const Scaffold(body: Text('Écran caméra')),
          ),
          GoRoute(
            path: '/municipality/recovery/fiscal-summary/:operatorId',
            builder: (context, state) => Scaffold(
              body: Text('Résumé ${state.pathParameters['operatorId']}'),
            ),
          ),
        ],
      );

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            fiscalCollectionRepositoryProvider.overrideWithValue(repo),
          ],
          child: MaterialApp.router(routerConfig: router),
        ),
      );
      await tester.pumpAndSettle();
    }

    testWidgets('affiche le bouton caméra et le fallback manuel', (tester) async {
      await pumpScanScreen(
        tester,
        repo: _MockFiscalCollectionRepository((_) async => {}),
      );

      expect(find.text('Scanner avec la caméra'), findsOneWidget);
      expect(find.text('OU'), findsOneWidget);
      expect(find.text('Jeton QR / UUID'), findsOneWidget);
      expect(find.text('Identifier le commerce'), findsOneWidget);
    });

    testWidgets('fallback manuel — identification réussie', (tester) async {
      await pumpScanScreen(
        tester,
        repo: _MockFiscalCollectionRepository((token) async {
          expect(token, '550e8400-e29b-41d4-a716-446655440000');
          return {
            'operator': {'id': 12},
          };
        }),
      );

      await tester.enterText(
        find.byType(TextField),
        '550e8400-e29b-41d4-a716-446655440000',
      );
      await tester.tap(find.text('Identifier le commerce'));
      await tester.pumpAndSettle();

      expect(find.text('Résumé 12'), findsOneWidget);
    });

    testWidgets('fallback manuel — QR invalide', (tester) async {
      await pumpScanScreen(
        tester,
        repo: _MockFiscalCollectionRepository((_) async => {}),
      );

      await tester.enterText(find.byType(TextField), 'qr-cassé');
      await tester.tap(find.text('Identifier le commerce'));
      await tester.pumpAndSettle();

      expect(find.text('QR non reconnu'), findsOneWidget);
    });

    testWidgets('fallback manuel — commerce introuvable', (tester) async {
      await pumpScanScreen(
        tester,
        repo: _MockFiscalCollectionRepository(
          (_) => throw DioException(
            requestOptions: RequestOptions(path: '/test'),
            response: Response(
              requestOptions: RequestOptions(path: '/test'),
              statusCode: 404,
            ),
            error: ApiException('QR commerce introuvable ou inactif.', statusCode: 404),
          ),
        ),
      );

      await tester.enterText(
        find.byType(TextField),
        '550e8400-e29b-41d4-a716-446655440000',
      );
      await tester.tap(find.text('Identifier le commerce'));
      await tester.pumpAndSettle();

      expect(find.text('Commerce introuvable'), findsOneWidget);
    });

    testWidgets('fallback manuel — absence de réseau', (tester) async {
      await pumpScanScreen(
        tester,
        repo: _MockFiscalCollectionRepository(
          (_) => throw DioException(
            requestOptions: RequestOptions(path: '/test'),
            type: DioExceptionType.connectionError,
          ),
        ),
      );

      await tester.enterText(
        find.byType(TextField),
        '550e8400-e29b-41d4-a716-446655440000',
      );
      await tester.tap(find.text('Identifier le commerce'));
      await tester.pumpAndSettle();

      expect(find.text('Connexion réseau indisponible'), findsOneWidget);
    });

    testWidgets('ouvre l\'écran caméra au clic', (tester) async {
      await pumpScanScreen(
        tester,
        repo: _MockFiscalCollectionRepository((_) async => {}),
      );

      await tester.tap(find.text('Scanner avec la caméra'));
      await tester.pumpAndSettle();

      expect(find.text('Écran caméra'), findsOneWidget);
    });
  });
}
