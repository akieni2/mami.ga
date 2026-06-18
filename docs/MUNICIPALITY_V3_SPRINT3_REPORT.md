# MAMI Municipality V3 — Sprint 3 Report

**Sprint** : Quittances officielles & impression terrain  
**Date** : 16 juin 2026  
**Statut** : Livré (backend + Flutter agent)

---

## Objectif

Compléter la chaîne terrain :

```
Scan QR Commerce → Situation fiscale → Encaissement → Quittance officielle → Impression immédiate
```

Sans Mobile Money (reporté Sprint 4).

---

## Backend

### Quittances PDF (`MunicipalReceiptPdfService`)

- Formats : **A4 PDF** et **thermique 58 mm** (DomPDF)
- Référence : `OWE-RCP-YYYY-NNNNNN`
- Contenu : logo commune, référence, date, commerce, taxes, période, montant, agent, quartier, hash, QR vérification
- Stockage versionné : `municipal_receipt_documents` (chaque réimpression crée une nouvelle version)

### Signature numérique V3

- `document_hash` SHA-256 sur : `receipt_number|operator_id|amount|period|payment_id|collected_at`
- Colonnes : `document_hash`, `signed_at`, `verification_token`
- Hash exposé : PDF, thermique, API, `print_payload`

### Vérification publique

- `GET /public/receipts/verify/{token}` (sans auth)
- Statuts : valide, annulée, remboursée, introuvable
- QR imprimé = URL complète de vérification

### API agent (`/api/municipality/fiscal/receipts`)

| Méthode | Route | Rôle |
|---------|-------|------|
| GET | `/receipts` | Liste quittances agent |
| GET | `/receipts/{id}` | Détail + `print_payload` |
| GET | `/receipts/{id}/pdf/{format?}` | Téléchargement PDF |
| POST | `/receipts/{id}/reprint` | Réimpression + audit |
| POST | `/receipts/{id}/annul` | Annulation (superviseur) |

Permission annulation : `municipal.receipt.annul` (admin / superviseur uniquement).

### Annulation & audit

- Colonnes : `annulled_at`, `annulled_by`, `annulled_reason`, `refunded_at`, `refunded_by`
- Quittance jamais supprimée ; audit `receipt.annulled`, `receipt.refunded`, `receipt.reprinted`

### Dashboards

- **Maire** : `/admin/municipality/mayor` — quittances émises/annulées, montant encaissé, par quartier/agent/taxe
- **Superviseur API** : agents actifs, sessions ouvertes, dernières quittances (`active_agents`, `latest_receipts`)

---

## Flutter Agent

### Impression Bluetooth 58 mm

- `BluetoothPrinterAdapter` — appairage, connexion, envoi bytes
- `PrinterService` — génération ESC/POS (commune, référence, commerce, montant, date, agent, hash, QR)
- Écran **Impression quittance** avec sélection imprimante et réimpression auditée
- Navigation post-encaissement vers impression
- Hub recouvrement : **Mes quittances**, **Impression quittance**

### Packages

- `print_bluetooth_thermal`
- `esc_pos_utils_plus`
- `shared_preferences` (imprimante mémorisée)

---

## Tests

Nouveaux fichiers :

- `MunicipalReceiptPdfTest`
- `ReceiptSignatureTest`
- `ReceiptVerificationTest`
- `ReceiptCancellationTest`

Objectif : **160+ tests Municipality** (suite complète `tests/Feature/Municipality`).

---

## Migration

- `2026_06_25_100000_create_municipality_v3_sprint3_official_receipts.php`
- `2026_06_25_100001_widen_municipal_receipt_qr_value_column.php` (URL QR complète)

---

## Hors scope (Sprint 4)

- Airtel Money / Moov Money
- Carte bancaire / virement

---

## Validation terrain recommandée

1. Encaisser un commerce test avec session ouverte
2. Vérifier quittance PDF A4 + thermique en base
3. Scanner le QR → `/public/receipts/verify/{token}`
4. Imprimer sur imprimante thermique Bluetooth 58 mm (ESC/POS)
5. Réimprimer et vérifier audit + nouvelle version document

---

## Fichiers clés

| Domaine | Fichiers |
|---------|----------|
| PDF | `MunicipalReceiptPdfService.php`, vues `resources/views/municipality/receipts/` |
| Signature | `ReceiptDocumentHasher.php` |
| Vérification | `ReceiptVerificationService.php`, `PublicReceiptVerificationController.php` |
| Flutter | `printer_service.dart`, `print_receipt_screen.dart` |
