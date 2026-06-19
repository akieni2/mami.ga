# Rapport de validation — Municipality V3 Sprint 3

**Version :** 1.0  
**Date :** juin 2026  
**Priorité :** P1 — clôture officielle  
**Branche :** `feature/mami-taxi-v2-p2`  
**Tag de référence :** `v1.6-roadmap-2026`

---

## Documents de clôture associés

| Document | Rôle |
|----------|------|
| [CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md](CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md) | Infrastructure et backend production |
| [CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md](CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md) | Parcours agent et impression Bluetooth |
| [CHECKLIST_RECETTE_MAIRIE_SPRINT3.md](CHECKLIST_RECETTE_MAIRIE_SPRINT3.md) | Recette fonctionnelle mairie |
| [PROCEDURE_CLOTURE_SPRINT3.md](PROCEDURE_CLOTURE_SPRINT3.md) | Procédure de clôture et autorisation P2 |
| [MAMI_2026_PROGRESS_TRACKER.md](MAMI_2026_PROGRESS_TRACKER.md) | Suivi d'avancement global |

---

## Objectif du rapport

Documenter l'état de validation de la **Priorité 1** avant déclaration officielle de clôture et ouverture de la Priorité 2 (Commerce & Services).

**Périmètre Sprint 3 :**

```
Scan QR opérateur économique
  → Situation fiscale
  → Encaissement espèces
  → Quittance officielle (PDF + signature)
  → Impression thermique Bluetooth 58 mm
  → Vérification publique QR
```

**Hors périmètre (backlog) :** Mobile Money, sync offline, brigades, route API remboursement.

---

## Synthèse exécutive

| Domaine | Statut technique | Statut production / terrain |
|---------|------------------|----------------------------|
| A. Infrastructure | ✅ Code prêt (domaines) | ⏳ À valider sur VPS |
| B. Backend | ✅ Livré | ⏳ Migrations / seeders VPS |
| C. Fiscalité | ✅ Livré + tests | ⏳ Parcours réel pilote |
| D. Bluetooth | ✅ Code Flutter livré | ⏳ Test imprimante réelle |
| E. Sécurité view | ✅ Tests permissions | ⏳ Vérification prod |
| F. Validation terrain | — | ⏳ Non exécutée |
| G. Clôture | — | ⏳ En cours |

**Avancement global P1 :** 90 % (livraison technique) — **100 %** après validation terrain + recette mairie.

---

## A. Infrastructure

| # | Vérification | Attendu | Statut code | Statut prod | OK |
|---|--------------|---------|-------------|-------------|-----|
| A.1 | Portail citoyen | `https://mami.ga` répond (HTTPS) | ✅ Configuré | ⏳ | ☐ |
| A.2 | API REST | `https://api.mami.ga/api` répond | ✅ Configuré | ⏳ | ☐ |
| A.3 | Backoffice admin | `https://admin.mami.ga` répond | ✅ Configuré | ⏳ | ☐ |
| A.4 | WebSocket Reverb | `wss://ws.mami.ga` connectable | ✅ Configuré | ⏳ | ☐ |
| A.5 | Certificats TLS | HTTPS valide sur les 4 domaines | — | ⏳ | ☐ |
| A.6 | Vérification quittance publique | `https://mami.ga/public/receipts/verify/{token}` | ✅ `ReceiptVerificationUrlBuilder` | ⏳ | ☐ |
| A.7 | Absence IP legacy | Plus de `63.142.241.105` dans `.env` prod | ✅ Migration doc | ⏳ | ☐ |

**Référence :** [DOMAIN_MIGRATION_REPORT.md](DOMAIN_MIGRATION_REPORT.md), [CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md](CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md)

---

## B. Backend

### B.1 Migrations Municipality (8 fichiers)

| Migration | Contenu |
|-----------|---------|
| `2026_06_18_100000_create_municipality_phase1_tables.php` | Territoires, signalements |
| `2026_06_20_100000_create_municipality_economic_registry_tables.php` | Registre économique |
| `2026_06_21_100000_create_municipality_v25_foundation_tables.php` | Paiements, quittances base |
| `2026_06_22_100000_secure_operator_qr_scan_tokens.php` | QR sécurisé |
| `2026_06_23_100000_create_municipality_fiscal_engine_tables.php` | Moteur fiscal |
| `2026_06_24_100000_create_municipality_v3_sprint2_cash_collection.php` | Sessions de caisse |
| `2026_06_24_110000_add_municipality_collection_performance_indexes.php` | Index performance |
| `2026_06_25_100000_create_municipality_v3_sprint3_official_receipts.php` | Quittances Sprint 3 |
| `2026_06_25_100001_widen_municipal_receipt_qr_value_column.php` | URL QR complète |

| # | Vérification | Commande | OK |
|---|--------------|----------|-----|
| B.1 | Toutes migrations appliquées | `php artisan migrate:status` | ☐ |
| B.2 | Aucune migration en attente | Sortie sans `Pending` Municipality | ☐ |

### B.2 Seeders

| Seeder | Usage |
|--------|-------|
| `RolePermissionSeeder` | Permissions Sprint 1–3 dont `municipal.receipt.annul` |
| `MunicipalityDatabaseSeeder` | Territoire Owendo, catégories, zones |
| `OwendoTerritorySeeder` | Référentiel territorial |
| `EconomicOperatorCategorySeeder` | Catégories commerces |
| `EconomicZoneSeeder` | Zones économiques pilote |

| # | Vérification | Commande | OK |
|---|--------------|----------|-----|
| B.3 | Permissions à jour | `php artisan db:seed --class=RolePermissionSeeder --force` | ☐ |
| B.4 | Données référence Owendo | `php artisan db:seed --class=MunicipalityDatabaseSeeder --force` | ☐ |
| B.5 | Module activé | `MAMI_MODULE_MUNICIPALITY=true` dans `.env` | ☐ |
| B.6 | Cache config | `php artisan config:cache && php artisan route:cache` | ☐ |

### B.3 Queues et Reverb

| # | Vérification | Attendu | OK |
|---|--------------|---------|-----|
| B.7 | Worker queue actif | `php artisan queue:work` ou supervisor |  | ☐ |
| B.8 | Connexion queue | `QUEUE_CONNECTION` opérationnel (database/redis) | ☐ |
| B.9 | Reverb démarré | `php artisan reverb:start` ou service systemd | ☐ |
| B.10 | Job obligations fiscales | `GenerateFiscalObligationsJob` exécutable | ☐ |

### B.4 Tests automatisés

**Suite :** `tests/Feature/Municipality/` — **25 fichiers**, objectif **~170 cas**.

| Fichier Sprint 3 | Couverture |
|------------------|------------|
| `MunicipalReceiptPdfTest.php` | PDF A4 + thermique |
| `ReceiptSignatureTest.php` | Hash SHA-256 |
| `ReceiptVerificationTest.php` | Vérification publique |
| `ReceiptCancellationTest.php` | Annulation superviseur |

| # | Vérification | Commande | OK |
|---|--------------|----------|-----|
| B.11 | Tests Municipality verts | `php artisan test --filter=Municipality` | ☐ |
| B.12 | 0 régression Taxi | `php artisan test --filter=Ride` | ☐ |
| B.13 | CI GitHub Actions | Pipeline vert sur branche | ☐ |

> **Note juin 2026 — exécution locale :** `php artisan test --filter=Municipality` a échoué (168/168) car MySQL n'était pas accessible sur `127.0.0.1:3306` (`mami_ga_testing`). Relancer avec MySQL démarré et base de test créée, ou valider via CI / VPS.

---

## C. Fiscalité — chaîne bout-en-bout

| # | Étape | Endpoint / composant | OK |
|---|-------|---------------------|-----|
| C.1 | Créer type de taxe | `POST /api/municipality/fiscal/taxes` | ☐ |
| C.2 | Créer taux | `POST /api/municipality/fiscal/rates` | ☐ |
| C.3 | Affecter taxe à opérateur | `POST /api/municipality/fiscal/assignments` | ☐ |
| C.4 | Générer obligations | `POST /api/municipality/fiscal/obligations/generate` | ☐ |
| C.5 | Ouvrir session de caisse | `POST /api/municipality/fiscal/cash-sessions/open` | ☐ |
| C.6 | Scanner QR opérateur | `GET /api/municipality/operators/by-qr/{value}` | ☐ |
| C.7 | Consulter situation fiscale | `GET /api/municipality/fiscal/operator/{id}/summary` | ☐ |
| C.8 | Encaisser | `POST /api/municipality/fiscal/collections` | ☐ |
| C.9 | Quittance générée | Référence `OWE-RCP-YYYY-NNNNNN` | ☐ |
| C.10 | Signature numérique | `document_hash`, `signed_at`, `verification_token` renseignés | ☐ |
| C.11 | PDF stocké | Entrée `municipal_receipt_documents` (A4 + thermique) | ☐ |
| C.12 | Vérification publique | `GET /public/receipts/verify/{token}` → statut **valide** | ☐ |
| C.13 | QR pointe vers portail | URL `https://mami.ga/public/receipts/verify/...` | ☐ |

**Parcours détaillé :** [CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md](CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md)

---

## D. Bluetooth — impression thermique 58 mm

| # | Vérification | OK |
|---|--------------|-----|
| D.1 | APK agent rebuild avec domaines `mami.ga` | ☐ |
| D.2 | Permission Bluetooth accordée (Android) | ☐ |
| D.3 | Imprimante 58 mm appairée | ☐ |
| D.4 | Impression quittance complète (commune, référence, commerce, montant, agent, hash) | ☐ |
| D.5 | QR imprimé scannable | ☐ |
| D.6 | Scan QR → page vérification publique valide | ☐ |
| D.7 | Réimpression auditée (nouvelle version document) | ☐ |

**Composants Flutter :** `PrinterService`, `BluetoothPrinterAdapter`, `esc_pos_command_builder.dart`, écran `print_receipt_screen.dart`.

---

## E. Sécurité et permissions

| Permission | Rôle attendu | Test | OK |
|------------|--------------|------|-----|
| `municipal.payment.collect` | `municipal_agent` | Encaissement + quittances | ☐ |
| `municipal.cash_session.open/close` | `municipal_agent` | Session caisse | ☐ |
| `municipal.fiscal.view` | `municipal_agent` | Situation fiscale | ☐ |
| `municipal.receipt.annul` | `admin` uniquement | Annulation quittance | ☐ |
| `municipal.payment.collect_without_gps` | `admin` | Bypass GPS superviseur | ☐ |
| Agent sans permission annulation | `municipal_agent` | POST annul → **403** | ☐ |
| Citoyen sans rôle agent | `citizen` | API fiscal → **403** | ☐ |
| Vérification publique | Anonyme | GET verify → **200** sans auth | ☐ |
| Module désactivé | Tous | `MAMI_MODULE_MUNICIPALITY=false` → **403** | ☐ |

**Source :** `database/seeders/RolePermissionSeeder.php`

---

## F. Validation terrain

| # | Acteur | Scénario | OK |
|---|--------|----------|-----|
| F.1 | Agent municipal | Connexion APK, ouverture session caisse | ☐ |
| F.2 | Agent municipal | Scan QR commerce pilote | ☐ |
| F.3 | Agent municipal | Encaissement réel de test (montant symbolique) | ☐ |
| F.4 | Agent municipal | Impression quittance sur imprimante terrain | ☐ |
| F.5 | Commerçant pilote | Réception quittance papier | ☐ |
| F.6 | Commerçant / citoyen | Scan QR → vérification en ligne | ☐ |
| F.7 | Superviseur | Consultation dashboard maire | ☐ |
| F.8 | Superviseur | Test annulation quittance test (optionnel) | ☐ |

**Checklist complète :** [CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md](CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md)

---

## G. Clôture

| # | Action | OK |
|---|--------|-----|
| G.1 | Toutes les cases A → F cochées | ☐ |
| G.2 | Recette mairie signée | ☐ |
| G.3 | Mise à jour [MAMI_2026_PROGRESS_TRACKER.md](MAMI_2026_PROGRESS_TRACKER.md) → P1 = 100 % | ☐ |
| G.4 | Date de clôture renseignée | ☐ |
| G.5 | Autorisation officielle ouverture P2 | ☐ |
| G.6 | Tag git `v1.6-sprint3-closed` (optionnel) | ☐ |

**Procédure :** [PROCEDURE_CLOTURE_SPRINT3.md](PROCEDURE_CLOTURE_SPRINT3.md)

---

## Améliorations détectées — backlog uniquement

| ID | Observation | Action |
|----|-------------|--------|
| BL-04 | Route API `refund` absente (service existe) | Backlog — ne pas développer avant clôture |
| BL-01 | Mobile Money non implémenté | Backlog Sprint 4 |
| BL-02 | Sync offline SQLite | Backlog post-P1 |
| — | `esc_pos_utils_plus` retiré, remplacé par builder inline | ✅ Résolu (`b0e169b`) |

Toute nouvelle observation terrain → ajouter dans [MAMI_2026_PROGRESS_TRACKER.md](MAMI_2026_PROGRESS_TRACKER.md) section backlog.

---

## Décision de clôture

| Rôle | Nom | Date | Signature | Décision |
|------|-----|------|-----------|----------|
| Chef de projet technique | | | | ☐ Clôturé / ☐ Réserves |
| Superviseur municipal | | | | ☐ Clôturé / ☐ Réserves |
| Représentant mairie | | | | ☐ Clôturé / ☐ Réserves |

**Clôture P1 autorisée :** ☐ Oui — ouverture P2 autorisée  
**Clôture P1 refusée :** ☐ Non — réserves : _______________

---

*Rapport vivant — mettre à jour les cases OK au fur et à mesure de l'exécution des checklists.*
