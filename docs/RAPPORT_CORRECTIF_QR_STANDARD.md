# Rapport — Correctif QR standard ISO (Sprint 3.2.3)

## Contexte

L’audit Sprint 3.2.1 a confirmé que `QRCodeManagement::renderPngWithGd()` produisait un **motif décoratif 16×16** (hash SHA-256), et non un QR code ISO/IEC 18004. Conséquence : aucun scanner (Samsung, `mobile_scanner`, lecteur tiers) ne pouvait lire les supports imprimés, alors que les UUID en base et l’API `by-qr/{uuid}` fonctionnaient via saisie manuelle.

## Correctif appliqué

### Bibliothèque

- **`endroid/qr-code` ^5.1** (production)
  - Encodeur : `bacon/bacon-qr-code` v3
  - Writer PNG via **PHP GD** (compatible serveur Laragon / production sans Imagick)
- **`khanamiryan/qrcode-detector-decoder` ^2.0** (dev — validation tests)

> `simplesoftwareio/simple-qrcode` a été écarté : génération PNG **requiert Imagick**, absent sur l’environnement actuel (`imagick=no`, `gd=yes`).

### Fichier modifié

`app/Modules/Municipality/Services/QRCodeManagement.php`

| Méthode | Changement |
|---------|------------|
| `buildPngContent()` | Inchangée en signature — délègue à `renderStandardQrImage()` |
| `generateForOperator()` | Inchangée |
| `findByValue()` | Inchangée |
| `renderPngWithGd()` | **Supprimée** (faux QR hash) |
| `renderSvg()` texte | **Supprimée** — remplacée par `SvgWriter` endroid si GD indisponible |

### Paramètres QR

| Paramètre | Valeur |
|-----------|--------|
| Contenu encodé | `qr_uuid` (ex. `2ec2f031-f7b7-476c-b82e-f2bdbe9e372a`) |
| Taille image | **400×400 px** minimum |
| Marge (quiet zone) | **10 px** (`RoundBlockSizeMode::Margin`) |
| Correction d’erreur | **High** (~30 %, niveau H) |
| Couleurs | Noir `#000000` sur blanc `#FFFFFF` |
| Format | PNG (GD) ou SVG (fallback) |

## Périmètre respecté

- **Modifié :** génération QR uniquement (`QRCodeManagement`, `composer.json`, tests QR)
- **Non modifié :** fiscalité, encaissement, quittances, scan caméra Flutter, routes API, modèle `economic_operator_qrcodes`

Les services PDF (`EconomicOperatorQrDocumentService`, `EconomicOperatorQrBatchService`) et l’API `downloadPng` consomment toujours `buildPngContent()` — ils bénéficient automatiquement du vrai QR.

## Tests ajoutés / renforcés

Fichier : `tests/Feature/Municipality/QRCodeManagementTest.php`

| Test | Vérification |
|------|--------------|
| `test_standard_qr_png_is_decodable_with_hello_world_payload` | Payload `HELLO-WORLD` décodé par `Zxing\QrReader` |
| `test_qr_png_encodes_uuid_not_public_id` | PNG valide, dimensions ≥ 400, payload = UUID |

Commande :

```bash
php artisan test tests/Feature/Municipality/QRCodeManagementTest.php
```

## Validation terrain

1. Déployer le backend avec **ext-gd** activée.
2. Télécharger un nouveau PNG : `GET /api/municipality/operators/{id}/qrcode/png` ou backoffice admin.
3. Scanner avec Samsung + APK `mobile_scanner` → UUID détecté → commerce identifié.
4. **Réimprimer** les QR déjà distribués (anciens motifs hash non convertibles).

## Impact

| Aspect | Effet |
|--------|--------|
| QR existants en base | UUID inchangés — **seules les images** doivent être regénérées |
| APK Flutter | Aucun changement requis |
| Taille PNG | Légèrement supérieure (~1–3 Ko) |
| Dépendances prod | +`endroid/qr-code`, +`bacon/bacon-qr-code` |

## Références

- Audit illisibilité : conversation Sprint 3.2.1
- Spec ISO/IEC 18004 (QR Code Model 2)
- [endroid/qr-code](https://github.com/endroid/qr-code)
