# Municipality V2.5 — Rapport de fondations V3 Fiscalité

**Date** : juin 2026  
**Branche cible** : `feature/mami-taxi-v2-p2`  
**Statut** : fondations prêtes — logique métier recouvrement **non implémentée**

---

## 1. Objectif

Enrichir le registre économique communal (V2) avec les briques nécessaires à **Municipality V3 Fiscalité & Recouvrement**, sans développer encore l'encaissement ni les workflows financiers complets.

Principe conservé : **Terrain First** — l'enrôlement mobile reste le mode principal ; le backoffice reste secondaire.

---

## 2. Tables créées

Migration : `database/migrations/2026_06_21_100000_create_municipality_v25_foundation_tables.php`

| Table | Rôle V3 |
|-------|---------|
| `economic_operator_qrcodes` | Identité scannable : jeton UUID unique + libellé affiché `OWE-COM-NNNNNN` |
| `field_visits` | Journal des opérations terrain (inspection, recouvrement, etc.) |
| `municipal_payments` | Encaissements communaux (structure uniquement) |
| `municipal_receipts` | Quittances `OWE-RCP-YYYY-NNNNNN` (structure uniquement) |

### Tables V2 déjà en place (rappel)

- `economic_operators`, `economic_operator_categories`, `economic_zones`
- `economic_operator_tax_status`

---

## 3. Modèles & enums

| Fichier | Description |
|---------|-------------|
| `EconomicOperatorQrcode` | QR lié à un opérateur |
| `FieldVisit` | Visite terrain |
| `MunicipalPayment` | Paiement municipal (sans logique) |
| `MunicipalReceipt` | Quittance (sans émission automatique) |
| `MunicipalBusinessCardData` | DTO carte professionnelle |
| `VisitType` | `inspection`, `verification`, `collection`, `awareness` |
| `PaymentMethod` | `cash`, `mobile_money`, `card`, `transfer` |
| `PaymentStatus` | `pending`, `completed`, `cancelled`, `failed` |

Relations ajoutées sur `EconomicOperator` : `qrcodes`, `activeQrcode`, `fieldVisits`, `municipalPayments`.

---

## 4. Services créés

| Service | Responsabilité |
|---------|----------------|
| `QRCodeManagement` | Génération QR à l'enrôlement, lookup par valeur, PNG (GD/SVG), placeholder PDF |
| `MunicipalBusinessCardService` | Préparation données carte pro (QR, référence, activité, année d'exercice) |
| `FieldVisitService` | Enregistrement visites terrain + audit |
| `MunicipalReceiptReferenceGenerator` | Numérotation `OWE-RCP-YYYY-NNNNNN` + QR quittance |

### Seeders

| Seeder | Rôle |
|--------|------|
| `MunicipalityDatabaseSeeder` | **Orchestrateur** — installe tout le module en une commande |
| `RolePermissionSeeder` | Rôles et permissions (`municipal_agent`, `economic_operator.*`) |
| `OwendoTerritorySeeder` | Territoire Owendo, quartiers, ZOP |
| `EconomicOperatorCategorySeeder` | 10 catégories d'activité |
| `EconomicZoneSeeder` | 8 zones `OWE-ZEC-*` — lève `RuntimeException` si territoire absent |

**Hook enrôlement** : `QRCodeManagement::generateForOperator()` crée un **UUID v4** (`scan_token`) encodé dans le QR, et un libellé affiché `OWE-COM-000001`. La référence publique seule n'est **pas** acceptée au scan.

### Sécurité QR commerce

| Élément | Valeur | Rôle |
|---------|--------|------|
| `qr_uuid` | UUID v4 (UNIQUE) | **Payload encodé dans l'image QR** — non devinable |
| `qr_value` | `OWE-COM-000001` | Libellé imprimé sous le QR (affichage humain) |
| `display_label_with_suffix` | `QR-OWE-COM-000001-7A9D2F18` | Forme lisible optionnelle (suffixe = 8 premiers hex du UUID) |
| Scan API | `GET /operators/by-qr/{uuid}` | Résolution uniquement par jeton UUID ou forme suffixée |
| Rejeté au scan | `OWE-COM-000001`, `QR-OWE-COM-000001` | Empêche la copie d'un QR prévisible |

---

## 5. Routes API créées

Préfixe : `/api/municipality` (auth Sanctum + `module:municipality`)

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/operators/by-qr/{value}` | Scan QR → commerce, fiscalité, historique, zones |
| GET | `/operators/{operator}/qrcode/png` | Téléchargement image QR |
| GET | `/operators/{operator}/qrcode/pdf` | Placeholder PDF (501 — V3) |
| GET | `/operators/{operator}/business-card` | Aperçu carte professionnelle |
| POST | `/operators/{operator}/field-visits` | Enregistrer une visite terrain |

Routes V2 existantes inchangées : enrôlement, carte SIG, dashboard, etc.

### Réponse scan QR (`OperatorQrScanResource`)

- Commerce (`EconomicOperatorResource`)
- Statut fiscal courant + historique (5 derniers)
- Territoire : quartier, arrondissement, ZOP, zone économique
- Historique visites terrain (10 dernières)

---

## 6. Dashboard Maire — indicateurs préparatoires

Bloc `v3_preparatory` dans `GET /operators/dashboard` :

| Indicateur | Statut |
|------------|--------|
| `qr_codes_generated` | **Calcul réel** (compte table QR) |
| `field_visits_total` | **Calcul réel** (compte visites) |
| `amounts_collected` | Placeholder (`sum` paiements `completed`, note V3) |
| `receipts_today` | Placeholder (compte quittances du jour, note V3) |

---

## 7. Application agent Flutter

Fichier : `municipality_agent_home_screen.dart`

Menu **Accueil agent** :

| Entrée | Statut |
|--------|--------|
| Recensement économique | **Opérationnel** → `/municipality/enrollment/new` |
| Scanner QR Commerce | Bientôt disponible |
| Contrôles terrain | Bientôt disponible |
| Recouvrement | Bientôt disponible |
| Historique | Bientôt disponible |
| Synchronisation | Bientôt disponible (V2.1 offline) |

L'écran d'enrôlement V2 (GPS auto, carte, photos, validation précision) reste inchangé.

---

## 8. Tests

| Fichier | Couverture |
|---------|------------|
| `QRCodeManagementTest` | Génération QR, lookup, PNG, carte pro |
| `FieldVisitTest` | CRUD visite, historique scan, KPI dashboard |
| `MunicipalReceiptTest` | Numérotation quittance, table, placeholders KPI |
| `EconomicOperatorIntegrityTest` | Contraintes UNIQUE/FK, soft delete, blocage hard delete |
| `MunicipalityDatabaseSeederTest` | Orchestrateur seeders + échec explicite `EconomicZoneSeeder` |

**Résultat** : `37` tests Municipality — **tous passants**.

---

## 9. Vérification intégrité schéma (pré-commit)

Contrôle effectué le 16 juin 2026. Migration corrective : `2026_06_21_110000_restrict_operator_dependent_foreign_keys.php`.

| # | Point vérifié | Statut | Détail |
|---|---------------|--------|--------|
| 1 | `UNIQUE` sur `economic_operator_qrcodes.qr_uuid` | **OK** | Jeton de scan sécurisé |
| 1b | `qr_value` = libellé affiché (non UNIQUE) | **OK** | `OWE-COM-NNNNNN` — non encodé dans le QR |
| 2 | `UNIQUE` sur `municipal_receipts.receipt_number` | **OK** | `$table->string('receipt_number', 30)->unique()` |
| 3 | `municipal_payments.operator_id` → `economic_operators` | **OK** | FK `restrictOnDelete()` |
| 4a | `field_visits.operator_id` → `economic_operators` | **OK** | FK `restrictOnDelete()` |
| 4b | `field_visits.agent_id` → `users` | **OK** | FK `restrictOnDelete()` |
| 5 | Suppression opérateur sans perte données liées | **OK** | Voir politique ci-dessous |

### Politique de suppression des opérateurs

| Mécanisme | Comportement |
|-----------|--------------|
| **Soft delete** (`economic_operators.deleted_at`) | **Recommandé** — colonne `softDeletes()` sur `economic_operators`. Aucune FK déclenchée : paiements, quittances, visites et QR sont **conservés**. |
| **Hard delete** (`forceDelete`) | **Bloqué** si des enregistrements dépendants existent (`RESTRICT` sur visites, QR, statuts fiscaux, paiements). |

### Clés étrangères — synthèse `ON DELETE`

| Table enfant | Colonne | Table parente | `ON DELETE` |
|--------------|---------|---------------|-------------|
| `economic_operator_qrcodes` | `operator_id` | `economic_operators` | `RESTRICT` |
| `field_visits` | `operator_id` | `economic_operators` | `RESTRICT` |
| `field_visits` | `agent_id` | `users` | `RESTRICT` |
| `municipal_payments` | `operator_id` | `economic_operators` | `RESTRICT` |
| `municipal_payments` | `agent_id` | `users` | `SET NULL` |
| `municipal_receipts` | `payment_id` | `municipal_payments` | `CASCADE` * |
| `economic_operator_tax_status` | `economic_operator_id` | `economic_operators` | `RESTRICT` |

\* La cascade `municipal_receipts` → `municipal_payments` ne s'applique qu'à la **suppression physique d'un paiement**, pas à l'archivage d'un opérateur. Tant que l'opérateur est archivé (soft delete), paiements et quittances restent intacts.

### Correction appliquée

La migration initiale V2.5 utilisait `cascadeOnDelete()` sur `field_visits` et `economic_operator_qrcodes`. Corrigé en `restrictOnDelete()` pour garantir l'intégrité historique V3 (recouvrement, audit terrain).

Tests automatisés : `tests/Feature/Municipality/EconomicOperatorIntegrityTest.php`.

---

## 10. Impact sur V3 Fiscalité & Recouvrement

| Brique V2.5 | Usage V3 prévu |
|-------------|----------------|
| QR commerce | Scan brigade → fiche opérateur instantanée |
| Carte professionnelle | PDF imprimable + QR physique sur commerce |
| `field_visits` | Missions brigades, campagnes recouvrement |
| `municipal_payments` | Encaissement terrain (cash, mobile money) |
| `municipal_receipts` | Quittance légale avec QR de vérification |
| Dashboard placeholders | Brancher calculs réels quand paiements actifs |

### Non implémenté (volontairement)

- Workflow encaissement complet
- Génération PDF carte / quittance
- Interface Flutter scan QR et recouvrement
- Offline-first sync (V2.1)
- Intégration passerelles Mobile Money

---

## 11. Déploiement

### Installation complète du module (VPS vierge ou réinitialisation référentiels)

Après les migrations :

```bash
php artisan migrate --force
php artisan db:seed --class=MunicipalityDatabaseSeeder --force
php artisan config:cache && php artisan route:cache
```

`MunicipalityDatabaseSeeder` exécute dans l'ordre :

1. `RolePermissionSeeder` — rôles et permissions (`municipal_agent`, `economic_operator.*`, etc.)
2. `OwendoTerritorySeeder` — territoire Owendo, quartiers, ZOP
3. `EconomicOperatorCategorySeeder` — catégories d'activité
4. `EconomicZoneSeeder` — 8 zones économiques `OWE-ZEC-*`

> **Important** : `EconomicZoneSeeder` lève une `RuntimeException` si `OwendoTerritorySeeder` n'a pas été exécuté avant (plus d'échec silencieux).

### Prérequis `.env`

```env
MAMI_MODULE_MUNICIPALITY=true
```

### Seeders individuels (maintenance ciblée)

```bash
php artisan db:seed --class=OwendoTerritorySeeder --force
php artisan db:seed --class=EconomicOperatorCategorySeeder --force
php artisan db:seed --class=EconomicZoneSeeder --force   # requiert OwendoTerritorySeeder
```

---

## 12. Proposition de commit final

Message suggéré :

```
feat(municipality): V2 economic registry + V2.5 foundations for fiscal V3

- Terrain-first operator enrollment with auto GPS and OWE-COM IDs
- QR codes, field visits, payment/receipt tables (structure only)
- QR scan API, business card preview, dashboard V3 preparatory KPIs
- Flutter agent menu with enrollment operational
- Schema integrity: UNIQUE constraints, RESTRICT FKs, soft delete policy
- 37 municipality feature tests passing
```

Fichiers principaux :
- `database/migrations/2026_06_18_*`, `2026_06_20_*`, `2026_06_21_*` (dont `110000_restrict_operator_dependent_foreign_keys`)
- `app/Modules/Municipality/**`
- `mobile/mami_client/lib/features/municipality/**`
- `tests/Feature/Municipality/**`
- `docs/MUNICIPALITY_V2_5_FOUNDATION_REPORT.md`

---

*Document mis à jour après vérification intégrité schéma — prêt pour commit final.*
