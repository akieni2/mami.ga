# Sprint 3.2.1 — Scanner QR Commerce par Caméra

## Objectif

Permettre à un agent municipal d’identifier un commerce en scannant son QR avec la caméra du téléphone, sans saisie manuelle du UUID, tout en conservant un mode secours par saisie manuelle.

## Architecture

```text
ScanOperatorScreen (hub)
├── Bouton « Scanner avec la caméra » → ScanQrCameraScreen
│   └── mobile_scanner → parseQrScanToken → lookupOperatorIdByQr → FiscalSummaryScreen
└── Saisie manuelle UUID → lookupOperatorIdByQr → FiscalSummaryScreen
```

### Fichiers Flutter

| Fichier | Rôle |
|---------|------|
| `presentation/screens/scan_operator_screen.dart` | Écran principal : caméra + fallback manuel |
| `presentation/screens/scan_qr_camera_screen.dart` | Vue caméra plein écran avec détection automatique |
| `domain/qr_scan_token_parser.dart` | Normalisation du contenu QR scanné |
| `domain/operator_qr_lookup.dart` | Appel API + mapping des erreurs métier |
| `data/fiscal_collection_repository.dart` | `GET /municipality/operators/by-qr/{token}` |

### Route

- `/municipality/recovery/scan` — écran principal
- `/municipality/recovery/scan/camera` — scan caméra

## Dépendances Flutter

```yaml
mobile_scanner: ^7.2.0
```

### Permissions Android (`AndroidManifest.xml`)

- `android.permission.CAMERA`
- `android.hardware.camera` (non obligatoire)

### Permissions iOS (`Info.plist`)

- `NSCameraUsageDescription`

## Workflow terrain

1. Agent ouvre **Recouvrement** → **Scanner QR commerce**
2. Clique **📷 Scanner avec la caméra**
3. Pointe le QR imprimé du commerce
4. L’app lit automatiquement le contenu
5. Appel `GET /api/municipality/operators/by-qr/{token}`
6. Navigation vers **Situation fiscale** du commerce identifié
7. Paiement et quittance selon le workflow Sprint 3 existant

### Mode secours

Si le QR est illisible, la caméra est indisponible ou l’agent préfère la saisie :

1. Saisir le jeton dans **Jeton QR / UUID**
2. Cliquer **Identifier le commerce**

## Contenu des QR — analyse et compatibilité

### Production actuelle (Sprint 3.2)

Le backend (`QRCodeManagement::scanPayload`) encode **uniquement l’UUID v4** du jeton actif :

```text
550e8400-e29b-41d4-a716-446655440000
```

L’API accepte aussi le libellé composite imprimé sur certaines cartes :

```text
QR-OWE-COM-00000001-A1B2C3D4
```

(où `A1B2C3D4` est le préfixe hex du UUID)

Le `public_id` seul (`OWE-COM-00000001`) est **parsé côté Flutter** pour faciliter les diagnostics terrain, mais **n’est pas encore résolu par l’API** — une extension backend sera nécessaire pour le support complet.

### Évolution envisagée (sans casser l’existant)

Le parseur Flutter accepte déjà :

```json
{
  "public_id": "OWE-COM-00000001",
  "uuid": "550e8400-e29b-41d4-a716-446655440000"
}
```

Priorité d’extraction : `uuid` → `public_id`. Les QR UUID existants restent valides.

## Gestion des erreurs

| Situation | Message affiché |
|-----------|-----------------|
| Contenu QR non reconnu | QR non reconnu |
| Jeton valide mais commerce absent / inactif (HTTP 404) | Commerce introuvable |
| Timeout, coupure réseau | Connexion réseau indisponible |

## Limites

- Nécessite une caméra fonctionnelle et l’autorisation caméra accordée
- Un seul scan traité à la fois (`DetectionSpeed.noDuplicates`) pour éviter les doubles appels API
- Le scan caméra n’est pas testé en widget test (dépendance matérielle) ; la logique métier est couverte par des tests unitaires et widget sur l’écran principal
- Les QR contenant uniquement `OWE-COM-…` nécessiteront une évolution API pour être résolus sans UUID

## Tests

Fichier : `mobile/mami_client/test/features/municipality/scan_qr_commerce_screen_test.dart`

- Parseur : UUID, JSON, composite, public_id, invalide
- Lookup : succès, QR invalide, 404, réseau
- UI : bouton caméra, fallback manuel, messages d’erreur, navigation caméra

## Critère de validation terrain

1. Commerce enrôlé avec QR imprimé
2. Agent ouvre l’APK → Scanner QR commerce
3. Scan caméra → commerce identifié automatiquement
4. Situation fiscale affichée
5. Encaissement et quittance possibles
6. **Aucune saisie manuelle du UUID requise** en conditions normales
