# Rapport — Correctif GPS ouverture de caisse (Sprint 3.2.4)

## Contexte audit

L’écran `OpenCashSessionScreen` appelait `Geolocator.getCurrentPosition()` sans vérifier ni demander les permissions Android. Erreur terrain :

```text
User denied permissions to access the device's location
```

Le GPS reste **obligatoire** pour `POST /municipality/fiscal/cash-sessions/open` (`latitude` / `longitude` requis côté API).

## Correctif appliqué

### Service partagé

`lib/features/municipality/domain/municipal_gps_service.dart`

| Méthode | Rôle |
|---------|------|
| `ensureGpsAvailable()` | `isLocationServiceEnabled()` → `checkPermission()` → `requestPermission()` si `denied` |
| `capturePosition()` | `ensureGpsAvailable()` puis `getCurrentPosition()` (précision haute) |

Provider Riverpod : `municipalGpsServiceProvider`

### Écrans mis à jour

| Fichier | Changement |
|---------|------------|
| `open_cash_session_screen.dart` | Utilise `MunicipalGpsService.capturePosition()` |
| `close_cash_session_screen.dart` | Idem |
| `collect_cash_screen.dart` | Idem |

### Messages utilisateur (français)

| Situation | Message |
|-----------|---------|
| GPS système désactivé | Veuillez activer la localisation de votre téléphone. |
| Permission refusée | Autorisez la localisation pour effectuer les opérations de recouvrement. |
| Permission refusée définitivement | Veuillez autoriser la localisation dans les paramètres Android. |

Les écrans interceptent `MunicipalGpsException` et affichent `e.message` (plus de `e.toString()` anglais Geolocator pour ces cas).

## Périmètre respecté

- API Laravel : **inchangée**
- Règles métier GPS obligatoire à l’ouverture : **inchangées**
- Workflows QR, fiscalité, quittances : **inchangés**

## Tests Flutter

Fichier : `test/features/municipality/municipal_gps_service_test.dart`

```bash
cd mobile/mami_client
flutter test test/features/municipality/municipal_gps_service_test.dart
```

## Procédure build APK release

```bash
cd mobile/mami_client
flutter pub get
flutter test test/features/municipality/municipal_gps_service_test.dart
flutter build apk --release --dart-define=API_BASE_URL=https://api.mami.ga/api
```

APK : `build/app/outputs/flutter-apk/app-release.apk`

## Validation terrain

1. Désinstaller / réinstaller l’APK ou réinitialiser permissions app.
2. Recouvrement → **Ouvrir caisse**.
3. Accepter la permission localisation à la demande système.
4. Vérifier snackbar « Session de caisse ouverte ».
5. En cas de refus : message FR affiché (pas le message Geolocator anglais).
6. Si « Ne plus demander » : message paramètres Android + autoriser manuellement dans Paramètres → MAMI → Localisation.

## Référence

Audit ouverture de caisse Sprint 3 — conversation précédente.
