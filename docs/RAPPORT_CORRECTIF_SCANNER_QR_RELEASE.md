# Rapport — Correctif scanner QR Android release (Sprint 3.2.2)

## Contexte

Lors de la recette terrain Sprint 3.2.1, l’écran **Scanner QR commerce** s’ouvrait puis plantait immédiatement sur APK **release** (Samsung Android 13/14), avec une stack obfusquée du type :

```text
Attempt to invoke virtual method 'v5.d w5.c.a(...)' on a null object reference
```

L’audit (`docs/SPRINT_3_2_1_SCAN_QR_CAMERA.md` + analyse crash) a identifié un crash natif **CameraX / ML Kit** au démarrage de `mobile_scanner`, déclenché par la **minification R8** activée par défaut sur les builds release Flutter 3.44.

## Cause identifiée

| Facteur | Détail |
|---------|--------|
| Build | `flutter build apk --release` avec `isMinifyEnabled = true` (défaut Flutter) |
| Plugin | `mobile_scanner` 7.2.0 → CameraX 1.5.3 + ML Kit barcode-scanning |
| ProGuard app | Fichier `proguard-rules.pro` limité aux règles SLF4J (Reverb) |
| Symptôme | R8 retire ou obfusque des classes CameraX/ML Kit → NPE à l’initialisation de la caméra |
| Non cause | Logique Flutter MAMI, routes, lookup QR, écrans fiscalité/recouvrement |

Le code Dart (`scan_qr_camera_screen.dart`) et les permissions manifeste étaient corrects ; le problème est **exclusivement côté packaging Android release**.

## Règles ajoutées

Fichier : `mobile/mami_client/android/app/proguard-rules.pro`

```proguard
-keep class androidx.camera.** { *; }
-keep class com.google.mlkit.** { *; }
-keep class com.google.android.gms.** { *; }
-keep public class androidx.camera.core.impl.CameraCaptureMetaData$** { *; }
-dontwarn androidx.camera.**
-dontwarn com.google.mlkit.**
```

Ces règles complètent les `consumerProguardFiles` du plugin `mobile_scanner` et évitent que R8 ne supprime des types requis par CameraX au bind de la caméra.

## Impact APK

| Aspect | Effet attendu |
|--------|----------------|
| Taille | Légère augmentation (classes CameraX/ML Kit conservées) — typiquement quelques Ko à ~100 Ko selon ABI |
| Debug / profile | Aucun changement (minification release uniquement) |
| Workflows métier | Aucun — pas de modification Dart, API, fiscalité, recouvrement, quittance |
| Sécurité | Pas d’assouplissement des permissions ; seules des classes scanner/caméra sont préservées |

## Procédure de validation

### 1. Rebuild release

```bash
cd mobile/mami_client
flutter build apk --release --dart-define=API_BASE_URL=https://api.mami.ga/api
```

### 2. Installation sur appareil terrain (Samsung Android 13/14 recommandé)

Installer l’APK `build/app/outputs/flutter-apk/app-release.apk` (ou variante ABI).

### 3. Scénario nominal

1. Connexion agent municipal (`municipal_agent`)
2. **Recouvrement** → **Scanner QR commerce**
3. **Scanner avec la caméra**
4. Accorder la permission caméra si demandée
5. Vérifier : **aperçu caméra stable**, pas de crash immédiat
6. Scanner un QR commerce enrôlé
7. Vérifier : commerce identifié → situation fiscale → encaissement / quittance possibles

### 4. Régression

- Fallback saisie manuelle UUID : inchangé
- Autres écrans Sprint 3 (session caisse, encaissement, impression Bluetooth) : smoke test rapide

### 5. En cas d’échec persistant

- Comparer `flutter run --debug` vs APK release sur le même appareil
- Rebuild avec mapping :  
  `flutter build apk --release --split-debug-info=build/debug-info`
- Désobfusquer la stack avec `retrace` et `mapping.txt`

## Périmètre du correctif

- **Modifié :** `android/app/proguard-rules.pro`, ce rapport
- **Non modifié :** écrans fiscalité, recouvrement, quittance, backend, parseur QR, tests widget existants

## Référence

- Audit crash : conversation Sprint 3.2.1 (Correctif A)
- Issues `mobile_scanner` : #614, #628, #666 (crash release CameraX / R8)
