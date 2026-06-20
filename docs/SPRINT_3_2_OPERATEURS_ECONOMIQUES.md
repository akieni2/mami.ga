# Sprint 3.2 — Gestion industrielle des opérateurs économiques

**Date :** 2026-06-16  
**Module :** Backoffice municipalité — registre économique à grande échelle  
**Principe :** couche d'exploitation **additive** — aucun changement aux workflows Sprint 3 (recensement APK, fiscalité, encaissement, quittances, Bluetooth).

---

## 1. Architecture

```
Backoffice (Blade)
  └─ EconomicOperatorAdminController
       ├─ EconomicOperatorRepository      (liste, fiche, filtres, eager loading)
       ├─ EconomicOperatorExportService   (CSV, Excel, PDF liste)
       ├─ EconomicOperatorQrDocumentService (QR PNG/PDF/carte commerce)
       └─ EconomicOperatorQrBatchService  (PDF A4 par lot)

API mobile / agents terrain (inchangé)
  └─ EconomicOperatorController + QRCodeManagement
```

**Séparation des accès :**

| Rôle | Backoffice opérateurs | API terrain |
|------|----------------------|-------------|
| `admin` / `super_admin` | ✅ | ✅ (selon permissions) |
| `municipal_supervisor` | ✅ | ❌ (pas d'accès admin général) |
| `municipal_agent` | ❌ | ✅ enrôlement / recouvrement |

Middleware dédié : `economic_operator.admin` → `EnsureEconomicOperatorAdminAccess`.

---

## 2. Écrans Backoffice

| Route | Écran |
|-------|-------|
| `GET /admin/municipality/operators` | Liste paginée (100/page) + recherche multicritère |
| `GET /admin/municipality/operators/{id}` | Fiche opérateur (infos, QR, fiscalité, documents, carte) |
| `GET /admin/municipality/operators/qr-batch` | Génération QR par lot (PDF A4) |
| `GET .../export/csv` | Export CSV |
| `GET .../export/excel` | Export Excel (TSV UTF-8) |
| `GET .../export/pdf` | Export PDF liste (500 lignes max) |
| `GET .../{id}/qr/png` | QR PNG individuel |
| `GET .../{id}/qr/pdf` | QR PDF individuel |
| `GET .../{id}/qr/business-card` | Carte commerce PDF |

**Menu sidebar :** « Opérateurs économiques » (visible si `canAccessEconomicOperatorAdmin()`).

---

## 3. Identifiants métier — passage 8 chiffres

| Élément | Avant | Après |
|---------|-------|-------|
| Format | `OWE-COM-000001` (6 chiffres) | `OWE-COM-00000001` (8 chiffres) |
| Générateur | `sprintf('OWE-COM-%06d')` | `sprintf('OWE-COM-%08d')` |
| Capacité | ~999 999 | **99 999 999** (`OWE-COM-99999999`) |
| Clé technique | `economic_operators.id` (bigint) + `qr_uuid` (UUID) | inchangé |

Migration `2026_06_16_120001_pad_economic_operator_public_ids_to_8_digits` : reformatage des IDs existants + `qr_value` associés.

Regex QR composite : `\d{6,8}` (rétrocompatibilité transition).

---

## 4. Performance

### Pagination serveur

- `EconomicOperatorRepository::ADMIN_PER_PAGE = 100`
- Jamais de `->get()` sur la table complète en liste
- Exports en `chunk(500)`

### Index SQL (migration `2026_06_16_120000`)

- `public_id`
- `commercial_name`
- `responsible_name`
- `phone`
- `sector_id`
- `created_at`

### Eager loading (anti N+1)

Liste : `category`, `sector`  
Fiche : `category`, `sector`, `operationalZone`, `economicZone`, `registeredBy`, `attachments`, `activeQrcode`, `taxAssignments.taxType`, `municipalPayments`

---

## 5. QR par lot

Formulaire : séquence **Début** / **Fin** (1–99 999 999).  
Lots prédéfinis : 100, 500, 1000, 5000, 10000.  
Maximum : **10 000** QR par export PDF.

Pour chaque identifiant de la plage :
- si commerce enregistré → QR actif (généré si absent)
- sinon → page « non enregistré »

---

## 6. Stratégie montée à 100 millions d'opérateurs

| Couche | Stratégie |
|--------|-----------|
| **Identifiant métier** | 8 chiffres = 100 M max ; au-delà : extension format ou préfixe commune |
| **Clé technique** | `id` bigint + `qr_uuid` UUID — jamais exposé seul au scan |
| **Requêtes liste** | Pagination + index + SELECT colonnes minimales |
| **Exports massifs** | Chunk/cursor ; jobs queue recommandés au-delà de 10k (évolution future) |
| **QR batch** | Plafond 10k/PDF ; découpage par plages pour impression industrielle |
| **Archivage** | Soft deletes existants ; partitionnement table (évolution > 10 M lignes) |

---

## 7. Fichiers principaux

| Fichier | Rôle |
|---------|------|
| `app/Modules/Municipality/Http/Controllers/Admin/EconomicOperatorAdminController.php` | Contrôleur web |
| `app/Modules/Municipality/Services/EconomicOperatorRepository.php` | Requêtes optimisées |
| `app/Modules/Municipality/Services/EconomicOperatorExportService.php` | Exports |
| `app/Modules/Municipality/Services/EconomicOperatorQrDocumentService.php` | QR individuel PDF |
| `app/Modules/Municipality/Services/EconomicOperatorQrBatchService.php` | QR lot |
| `app/Http/Middleware/EnsureEconomicOperatorAdminAccess.php` | Sécurité |
| `resources/views/admin/municipality/operators/*` | Vues Blade |
| `tests/Feature/Municipality/EconomicOperatorAdminTest.php` | Tests Feature |

---

## 8. Tests

```bash
php artisan test --filter=EconomicOperatorAdmin
php artisan test --filter=EconomicOperatorEnrollment
php artisan test --filter=QRCodeManagement
```

---

## 9. Non-régression Sprint 3

- Aucune modification des routes API `/api/municipality/operators` (mobile)
- `EconomicOperatorService::enroll()` inchangé (hors format `public_id`)
- Fiscalité, encaissement, quittances, Bluetooth : non touchés
- Agents terrain : accès backoffice opérateurs **refusé** (403)
