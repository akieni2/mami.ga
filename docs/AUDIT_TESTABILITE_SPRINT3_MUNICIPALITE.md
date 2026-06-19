# Audit de testabilité terrain — Municipality V3 Sprint 3

**Version :** 1.0  
**Date :** juin 2026  
**Type :** audit documentaire — aucune modification de code  
**Branche analysée :** `feature/mami-taxi-v2-p2` (≥ `3d0bbdd`)  
**Objectif :** déterminer si le Sprint 3 est **réellement testable de bout en bout** sur le terrain par des agents municipaux

---

## Méthodologie

Analyse statique du dépôt : routes web/API, contrôleurs admin, migrations, seeders, écrans Flutter, impression Bluetooth, feature flags, middleware. Aucun test de code ni correction appliquée.

**Documents de référence :** [MAMI_2026_EXECUTION_PLAN.md](MAMI_2026_EXECUTION_PLAN.md), [CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md](CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md)

---

## Synthèse

| Domaine | Verdict audit |
|---------|---------------|
| Dashboard web | Partiel — fiscalité, recouvrement, SIG, signalements, quittances OK ; opérateurs et QR sans écran dédié |
| Backend | Code complet — dépend du déploiement VPS et `MAMI_MODULE_MUNICIPALITY=true` |
| Application agent | Chaîne recouvrement opérationnelle en code ; scan QR manuel ; pas de carte SIG agent |
| APK | Constructible (`mami_client` = Client + Agent) ; pas d'APK Agent séparé |
| Bluetooth | Code prêt — **jamais validé sur imprimante réelle** dans ce dépôt |
| Bout en bout | Enchaînement codé — **bloqué par prérequis ops + 3 écarts UX terrain** |

---

## 1. Dashboard web

**Accès commun :** `auth` + middleware `admin` (`users.is_admin`) + `module:municipality`  
**URL base :** `https://admin.mami.ga/admin/municipality/...`  
**Prérequis :** `MAMI_MODULE_MUNICIPALITY=true` — sinon **403** sur toutes les routes municipales.

| Écran demandé | Route | Permissions | Statut | Dépendances manquantes |
|---------------|-------|-------------|--------|------------------------|
| **Tableau de bord municipal** | Pas d'écran unifié | `core.admin.access` | **Partiel** | `admin.dashboard` = taxi ; KPIs municipaux éclatés entre recouvrement + maire |
| **Fiscalité** | `/admin/municipality/fiscal/tax-types` (+ nav : taux, objectifs, affectations, obligations) | Admin web | **Opérationnel** | Taxes/taux saisis manuellement ; aucun montant en seed |
| **Opérateurs économiques** | Aucune vue CRUD dédiée | — | **Non opérationnel (web)** | Enrôlement via API mobile uniquement ; carte SIG affiche les points |
| **Sessions de caisse** | `/admin/municipality/collection` | Admin web | **Partiel** | Liste sessions ouvertes + KPIs ; pas de détail session / clôture depuis le web |
| **Recouvrement** | `/admin/municipality/collection` | Admin web | **Opérationnel** | Encaissements par agent, quartier, jour |
| **Carte SIG** | `/admin/municipality/map` + `/map/geojson` | Admin web | **Opérationnel** | Leaflet + GeoJSON opérateurs/zones |
| **Signalements** | `/admin/municipality/reports` | Admin web | **Opérationnel** | Assignation + changement statut |
| **Quittances** | `/admin/municipality/mayor` | Admin web | **Opérationnel** | KPI quittances émises/annulées, par quartier/agent |
| **QR Codes** | Aucune page admin | API : `/api/municipality/operators/{id}/qrcode/png\|pdf` | **Non opérationnel (web)** | Téléchargement QR via API ou enrôlement mobile uniquement |

**Navigation :** entrées municipales présentes dans `resources/views/admin/partials/sidebar.blade.php`.

---

## 2. Backend

### 2.1 Migrations (8 fichiers Municipality)

| Migration | Statut code |
|-----------|-------------|
| `2026_06_18_100000_create_municipality_phase1_tables.php` | ✅ Présente |
| `2026_06_20_100000_create_municipality_economic_registry_tables.php` | ✅ |
| `2026_06_21_100000_create_municipality_v25_foundation_tables.php` | ✅ |
| `2026_06_22_100000_secure_operator_qr_scan_tokens.php` | ✅ |
| `2026_06_23_100000_create_municipality_fiscal_engine_tables.php` | ✅ |
| `2026_06_24_100000_create_municipality_v3_sprint2_cash_collection.php` | ✅ |
| `2026_06_24_110000_add_municipality_collection_performance_indexes.php` | ✅ |
| `2026_06_25_100000_create_municipality_v3_sprint3_official_receipts.php` | ✅ |
| `2026_06_25_100001_widen_municipal_receipt_qr_value_column.php` | ✅ |

**Statut exécution VPS :** ⏳ non vérifié dans cet audit — à confirmer via `php artisan migrate:status`.

### 2.2 Seeders

| Seeder | Rôle | Commande |
|--------|------|----------|
| `RolePermissionSeeder` | Permissions Sprint 1–3 (`municipal.receipt.annul`, etc.) | `php artisan db:seed --class=RolePermissionSeeder --force` |
| `MunicipalityDatabaseSeeder` | Territoire Owendo, catégories, zones | `php artisan db:seed --class=MunicipalityDatabaseSeeder --force` |

**Manquant pour test terrain :** compte agent `municipal_agent`, opérateurs pilotes, taxes et obligations — **saisie admin obligatoire**, pas entièrement seedée.

### 2.3 Tableau de statut backend

| Composant | Implémentation | Statut testabilité |
|-----------|----------------|-------------------|
| Routes API `/api/municipality/*` | `app/Modules/Municipality/Routes/api.php` | ✅ Actives si module ON |
| Feature flag | `MAMI_MODULE_MUNICIPALITY` — **défaut `false`** | ⚠️ Bloquant si non activé |
| Génération obligations | `FiscalObligationGeneratorService` + POST admin `obligations/generate` + API `obligations/generate` | ✅ Opérationnel (synchrone, pas de Job séparé) |
| Génération quittances | `MunicipalReceiptEmissionService` via encaissement | ✅ |
| PDF quittances | `MunicipalReceiptPdfService` (A4 + 58 mm) | ✅ |
| Signature numérique | `ReceiptDocumentHasher` (SHA-256) | ✅ |
| Vérification publique | `GET /public/receipts/verify/{token}` | ✅ |
| Reverb | Configuré (`wss://ws.mami.ga`) | ⚪ Non requis pour chaîne Sprint 3 |
| Queue | `QUEUE_CONNECTION=database` par défaut | ⚪ Non bloquant — génération obligations synchrone ; worker utile pour jobs futurs |
| Tests automatisés | 25 fichiers `tests/Feature/Municipality/` | ⚠️ Non confirmés verts (MySQL local injoignable lors du dernier run) |

---

## 3. Application agent municipal

**APK unique :** `mobile/mami_client` (pas de `mami_agent` séparé).  
**Accès agent :** `/municipality/agent` — visible si rôle `municipal_agent` ou permission `economic_operator.create`.  
**Module mobile :** tuile Municipalité sur l'accueil **désactivée par défaut** (`AppFeatures.defaults()` → `municipality: false`) — activée si `GET /api/app/features` retourne `municipality: true` (nécessite flag serveur).

| Fonctionnalité | Écran Flutter | API | Statut réel |
|----------------|---------------|-----|-------------|
| **Connexion** | Auth existante (`auth_provider`) | `POST /api/login` + Sanctum | ✅ Opérationnel |
| **Recensement économique** | `enroll_economic_operator_screen.dart` → `/municipality/enrollment/new` | `POST /api/municipality/operators` | ✅ Opérationnel |
| **Scan QR commerce** | `scan_operator_screen.dart` → `/municipality/recovery/scan` | `GET /api/municipality/operators/by-qr/{value}` | **Partiel** — saisie manuelle UUID, **pas de lecteur caméra** |
| **Situation fiscale** | `fiscal_summary_screen.dart` → `/municipality/recovery/fiscal-summary/:operatorId` | `GET /api/municipality/fiscal/operator/{id}/summary` | ✅ Opérationnel |
| **Encaissement** | `collect_cash_screen.dart` → `/municipality/recovery/collect` | `POST /api/municipality/fiscal/collections` | ✅ Opérationnel — redirige vers impression si quittance |
| **Sessions de caisse** | `open_cash_session_screen.dart`, `close_cash_session_screen.dart` | `POST .../cash-sessions/open`, `.../close`, `GET .../current` | ✅ Opérationnel |
| **Historique** | Hub : `my_collections_screen.dart`, `receipt_history_screen.dart` | `GET .../collections`, `GET .../receipts` | **Partiel** — menu agent accueil « Historique » = **désactivé** (« Bientôt ») ; accessible via hub Recouvrement |
| **Carte SIG** | Aucun écran agent dédié | `GET /api/municipality/operators/map` (existe) | **Non opérationnel (mobile)** — carte uniquement à l'enrôlement |
| **Signalements** | `create_municipality_report_screen.dart`, `my_municipality_reports_screen.dart` | `POST/GET /api/municipality/reports` | ✅ Opérationnel (citoyen + agent) |

**Hub recouvrement :** `recovery_hub_screen.dart` — point d'entrée complet (caisse, scan, fiscalité, encaissement, quittances, impression).

---

## 4. APK Android

### APK Agent Municipal

**Réalité :** n'existe pas en tant qu'APK séparé. L'agent utilise **`mami_client`** (Super App Client + Agent).

### APK Client

**Chemin :** `mobile/mami_client/`

### Commandes exactes

```bash
cd mobile/mami_client
flutter clean
flutter pub get
flutter build apk --release
```

**Sortie attendue :** `mobile/mami_client/build/app/outputs/flutter-apk/app-release.apk`

| Élément | Évaluation |
|---------|------------|
| **Succès attendu** | ✅ Oui — builds release déjà observés dans le dépôt ; `pubspec.yaml` sans dépendance externe bloquante (`esc_pos_utils_plus` retiré) |
| **Dépendances** | `pusher_channels_flutter` en `path: ../mami_driver/packages/...` — le dossier `mami_driver` doit être présent |
| **Erreurs potentielles** | SDK Flutter ^3.5 ; Android SDK/Gradle ; permissions Bluetooth Android 12+ (`BLUETOOTH_CONNECT`, `BLUETOOTH_SCAN` déclarés dans `AndroidManifest.xml`) |
| **URLs production** | Par défaut `https://api.mami.ga/api` (`app_config.dart`) — rebuild obligatoire après migration domaines |

**Recommandation terrain :** distribuer l'APK avec compte agent pré-configuré et vérifier que `GET /api/app/features` retourne `modules.municipality: true`.

---

## 5. Impression Bluetooth

| Composant | Fichier / package | Statut |
|-----------|-------------------|--------|
| Bibliothèque | `print_bluetooth_thermal: ^1.1.6` | ✅ Intégrée |
| Adaptateur | `bluetooth_printer_adapter.dart` | ✅ |
| Génération ESC/POS | `esc_pos_command_builder.dart` (inline, remplace `esc_pos_utils_plus`) | ✅ |
| Service | `printer_service.dart` — commune, référence, commerce, montant, agent, hash, QR | ✅ |
| Écran | `print_receipt_screen.dart` — sélection imprimante, impression, réimpression | ✅ |
| Permissions Android | `BLUETOOTH`, `BLUETOOTH_CONNECT`, `BLUETOOTH_SCAN` | ✅ Déclarées |

### Verdict impression

**Partiellement prêt pour test terrain**

| Prêt | Non validé |
|------|------------|
| Code complet ESC/POS 58 mm | Aucun test sur imprimante physique documenté |
| Flux post-encaissement → écran impression | Compatibilité modèles ESC/POS gabonais inconnue |
| QR imprimé dans le ticket | Rendu thermique (contraste, taille) à valider |

---

## 6. Test de bout en bout

| Étape | Écran | API | Statut |
|-------|-------|-----|--------|
| 1. Connexion agent | Auth → accueil Super App ou `/municipality` | `POST /api/login` | ✅ |
| 2. Ouverture caisse | `open_cash_session_screen.dart` | `POST /api/municipality/fiscal/cash-sessions/open` | ✅ |
| 3. Scan QR commerce | `scan_operator_screen.dart` | `GET /api/municipality/operators/by-qr/{value}` | ⚠️ **Saisie manuelle** — pas de scan caméra |
| 4. Situation fiscale | `fiscal_summary_screen.dart` | `GET /api/municipality/fiscal/operator/{id}/summary` | ✅ — nécessite obligations générées |
| 5. Encaissement | `collect_cash_screen.dart` | `POST /api/municipality/fiscal/collections` | ✅ — GPS + session ouverte requis |
| 6. Quittance | Générée côté serveur (`MunicipalReceiptEmissionService`) | Incluse dans réponse encaissement | ✅ |
| 7. Impression Bluetooth | `print_receipt_screen.dart` | `GET /api/municipality/fiscal/receipts/{id}` (`print_payload`) | ⚠️ Code OK — matériel non validé |
| 8. Historique | `receipt_history_screen.dart`, `my_collections_screen.dart` | `GET .../receipts`, `GET .../collections` | ✅ via hub Recouvrement |
| 9. Dashboard superviseur | **Web** : `/admin/municipality/mayor` ou `/admin/municipality/collection` | API : `GET /api/municipality/fiscal/supervisor/dashboard` | ✅ **Web uniquement** — pas d'écran Flutter superviseur |

### Prérequis obligatoires avant exécution terrain

1. `MAMI_MODULE_MUNICIPALITY=true` sur le VPS + `config:cache`
2. Migrations + seeders exécutés
3. Compte utilisateur avec rôle `municipal_agent` + permissions recouvrement
4. Au moins 1 opérateur économique enrôlé avec QR actif
5. Taxes, taux, affectations et obligations générées (backoffice fiscal)
6. APK `mami_client` release installé, pointant vers `api.mami.ga`
7. Imprimante thermique 58 mm Bluetooth appairée

---

## 7. Écarts et améliorations — backlog uniquement

| ID | Écart détecté | Action |
|----|---------------|--------|
| BL-11 | Scan QR sans caméra intégrée | Backlog — ne pas développer dans cet audit |
| BL-12 | Pas d'écran carte SIG agent mobile | Backlog |
| BL-13 | Pas d'APK Agent dédié | Backlog (scission APK Q3 2026) |
| BL-14 | Menu agent « Historique » désactivé alors que hub recouvrement fonctionne | Backlog UX |
| BL-15 | Pas d'admin web opérateurs / QR | Backlog |
| BL-04 | Route API remboursement absente | Backlog existant |

---

## 8. Décision

# NO GO TERRAIN

Le Sprint 3 **n'est pas immédiatement testable de bout en bout sur le terrain** dans l'état actuel de déploiement et de validation. Le **code de la chaîne principale est présent et enchaîné**, mais des blocages opérationnels et des écarts terrain empêchent un test agent fiable **sans préparation préalable**.

### Blocages restants (liste précise)

| # | Blocage | Type | Action requise (hors dev) |
|---|---------|------|---------------------------|
| **B1** | `MAMI_MODULE_MUNICIPALITY=false` par défaut | Infrastructure | Activer sur VPS + `config:cache` |
| **B2** | Déploiement production non validé (domaines HTTPS, migrations, seeders) | Infrastructure | Exécuter [CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md](CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md) |
| **B3** | Données fiscales pilote absentes (taxes, obligations, opérateurs) | Données métier | Configuration backoffice + enrôlement terrain |
| **B4** | Compte agent municipal non garanti en prod | Données métier | Créer agent + rôle `municipal_agent` |
| **B5** | Scan QR : saisie manuelle UUID uniquement — pas de lecteur caméra | UX terrain | Contournement : copier-coller jeton QR ; impact ergonomie terrain |
| **B6** | Impression Bluetooth jamais validée sur imprimante réelle 58 mm | Validation matérielle | Test terrain obligatoire avant recette |
| **B7** | Tests automatisés Municipality non confirmés verts | Qualité | Relancer `php artisan test --filter=Municipality` (CI ou MySQL local) |
| **B8** | Tuile Municipalité désactivée côté Flutter si API features désactivée | Config | Aligner flag serveur + rebuild APK |

### Passage à GO TERRAIN

Le passage à **GO TERRAIN** est possible **sans nouveau développement** lorsque :

- B1 à B4 et B8 sont résolus (checklist VPS) ;
- B6 est validé sur au moins une imprimante terrain ;
- B7 est confirmé vert ;
- B5 est accepté comme contournement temporaire par la mairie (saisie jeton QR) **ou** reporté explicitement au backlog.

Ensuite : exécuter [CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md](CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md) et mettre à jour [RAPPORT_VALIDATION_SPRINT3_MUNICIPALITE.md](RAPPORT_VALIDATION_SPRINT3_MUNICIPALITE.md).

---

*Audit documentaire uniquement — aucun code modifié, aucun commit, aucune migration.*
