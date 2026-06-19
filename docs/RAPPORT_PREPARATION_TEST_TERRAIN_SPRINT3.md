# Rapport de préparation — Test terrain Sprint 3 Municipality V3

**Version :** 1.0  
**Date :** juin 2026  
**Périmètre :** audit APK Agent Municipal, chaîne recouvrement, impression Bluetooth  
**Hors scope :** P2 Commerce, P3 Main d'œuvre, P4 RideSharing, P5 TM — **aucune nouvelle fonctionnalité**

---

## Synthèse exécutive

| Question | Réponse |
|----------|---------|
| Existe-t-il un APK `mami_agent` ? | **Non** — tout le code agent est dans `mobile/mami_client` |
| La chaîne Sprint 3 est-elle codée ? | **Oui** — session → dette → encaissement → quittance → impression |
| Peut-on tester sur le terrain aujourd'hui ? | **Non immédiatement** — blocages ops + validation matérielle |
| APK constructible ? | **Oui** — `flutter build apk` sur `mami_client` (voir §6) |

---

# 1. Inventaire Flutter — écrans municipaux

## 1.1 Localisation du code

**Tout le module municipal est intégré dans `mobile/mami_client`**, sous :

```
mobile/mami_client/lib/features/municipality/
├── data/                    # Repositories API
├── printing/                # Bluetooth + ESC/POS 58 mm
└── presentation/
    ├── providers/
    └── screens/             # 14 écrans
```

**`mobile/mami_driver` :** aucun écran municipal (taxi uniquement).  
**`mobile/mami_agent` :** **n'existe pas** dans le dépôt.

## 1.2 Écrans par domaine fonctionnel

### Authentification (partagé Super App)

| Écran | Fichier | Route |
|-------|---------|-------|
| Login | `features/auth/.../login_screen.dart` | `/login` |
| Register | `features/auth/.../register_screen.dart` | `/register` |
| Splash | `features/splash/.../splash_screen.dart` | `/splash` |

> **Note agent :** après connexion, le splash redirige vers `/` (portail Super App taxi). L'agent doit naviguer manuellement vers `/municipality/agent` ou activer la tuile Municipalité.

### Recensement économique

| Écran | Fichier | Route |
|-------|---------|-------|
| Accueil agent | `municipality_agent_home_screen.dart` | `/municipality/agent` |
| Enrôlement opérateur | `enroll_economic_operator_screen.dart` | `/municipality/enrollment/new` |

Carte OSM (`flutter_map`) utilisée **uniquement** à l'enrôlement (GPS + photo façade), pas de carte SIG dédiée agent.

### Recouvrement / fiscalité / caisse

| Écran | Fichier | Route |
|-------|---------|-------|
| Hub recouvrement | `recovery_hub_screen.dart` | `/municipality/recovery` |
| Ouvrir caisse | `open_cash_session_screen.dart` | `/municipality/recovery/open-session` |
| Fermer caisse | `close_cash_session_screen.dart` | `/municipality/recovery/close-session` |
| Scan QR commerce | `scan_operator_screen.dart` | `/municipality/recovery/scan` |
| Situation fiscale | `fiscal_summary_screen.dart` | `/municipality/recovery/fiscal-summary` (+ `/:operatorId`) |
| Encaissement | `collect_cash_screen.dart` | `/municipality/recovery/collect` |
| Mes encaissements | `my_collections_screen.dart` | `/municipality/recovery/my-collections` |
| Mes quittances | `receipt_history_screen.dart` | `/municipality/recovery/receipts` |
| Impression quittance | `print_receipt_screen.dart` | `/municipality/recovery/print-receipt/:receiptId` |

### Signalements citoyens

| Écran | Fichier | Route |
|-------|---------|-------|
| Portail mairie | `municipality_home_screen.dart` | `/municipality` |
| Créer signalement | `create_municipality_report_screen.dart` | `/municipality/report/new` |
| Mes signalements | `my_municipality_reports_screen.dart` | `/municipality/reports` |

### SIG / cartographie

| Côté | Statut |
|------|--------|
| **Mobile agent** | Pas d'écran carte SIG dédié |
| **Web admin** | `/admin/municipality/map` (Leaflet + GeoJSON) |
| **API** | `GET /api/municipality/operators/map` (existe, non consommé en Flutter agent) |

### Superviseur

| Côté | Statut |
|------|--------|
| **Mobile** | Aucun écran superviseur |
| **Web** | `/admin/municipality/collection` (KPIs sessions, encaissements par agent/quartier/jour) |
| **Web** | `/admin/municipality/mayor` (quittances officielles) |
| **API** | `GET /api/municipality/fiscal/supervisor/dashboard` |

## 1.3 Intégration Super App vs isolation

| Aspect | État actuel |
|--------|-------------|
| Package Flutter | **Unique** : `mami_client` |
| Routes municipales | Cohabitent avec taxi (`app_router.dart`, 14 routes `/municipality/*`) |
| Feature flag module | `GET /api/app/features` → `modules.municipality` (défaut serveur : `false`) |
| Tuile accueil | `HomeScreen` → tuile Municipalité **grisée** si module désactivé |
| Détection agent | `UserModel.isMunicipalAgent` / `canEnrollEconomicOperators` |
| Entrée agent | `/municipality/agent` — **pas de redirect auto** au login |

**Conclusion :** fonctionnalités agent **non isolées** — embarquées dans la Super App client avec garde-fous par rôle et feature flag.

---

# 2. Structure cible — APK `mami_agent`

## 2.1 Architecture recommandée (post-Sprint 3, sans dev immédiat)

```
mobile/
├── mami_client/          # Citoyens + taxi (existant)
├── mami_driver/          # Chauffeurs taxi (existant)
└── mami_agent/           # Agents municipaux (à créer — backlog)
    ├── lib/
    │   ├── core/         # config, dio, router, theme (copie adaptée)
    │   └── features/
    │       ├── auth/
    │       └── municipality/   # symlink ou package partagé
    └── android/          # label « MAMI Agent Owendo », icône mairie
```

## 2.2 Option minimale pour test terrain Sprint 3 (sans scission)

**Utiliser `mami_client` rebaptisé opérationnellement « APK Agent »** avec :

- Compte `municipal_agent` dédié (pas de courses taxi)
- `MAMI_MODULE_MUNICIPALITY=true` sur l'API
- Raccourci deep link ou bookmark : `/municipality/agent`
- Build avec `--dart-define=API_BASE_URL=https://api.mami.ga/api`

## 2.3 Option cible `mami_agent` (clôture Q3 2026)

| Élément | Contenu |
|---------|---------|
| **Package partagé** | Extraire `lib/features/municipality/` → `packages/mami_municipality/` |
| **Router agent** | Routes limitées : auth, agent home, recovery, enrollment, reports |
| **Splash agent** | Redirect `/municipality/agent` si `municipal_agent` |
| **Exclusions** | Pas de taxi, pas de booking, pas de modules P2–P5 |
| **Identité visuelle** | Icône + nom « MAMI Agent — Owendo » |
| **Permissions** | GPS, Bluetooth, caméra (future scan QR) |

## 2.4 Profils couverts

| Profil | Accès actuel (`mami_client`) | Accès cible (`mami_agent`) |
|--------|------------------------------|----------------------------|
| Agent municipal | ✅ Rôle + permissions | ✅ App dédiée |
| Brigade terrain | ✅ Même rôle | ✅ |
| Superviseur municipal | ⚠️ Web admin uniquement | Web + (optionnel) dashboard mobile V2 |

---

# 3. Matrice testabilité — état au 19 juin 2026

| Fonction | Disponible (code) | Testable terrain | Blocage éventuel |
|----------|-------------------|------------------|------------------|
| **Connexion agent municipal** | ✅ | ⚠️ Partiel | Compte agent à créer via admin ; token Sanctum |
| **Accès module Municipalité** | ✅ | ❌ | `MAMI_MODULE_MUNICIPALITY=false` par défaut → tuile grisée |
| **Accueil agent** | ✅ `/municipality/agent` | ⚠️ | Pas de redirect auto après login |
| **Recensement opérateur** | ✅ | ⚠️ | GPS + photos ; territoire Owendo seedé requis |
| **Ouverture caisse** | ✅ | ⚠️ | GPS obligatoire ; permission `municipal.cash_session.open` |
| **Fermeture caisse** | ✅ | ⚠️ | Session ouverte requise |
| **Consultation situation fiscale** | ✅ | ⚠️ | Obligations générées en backoffice |
| **Encaissement espèces** | ✅ | ⚠️ | Session ouverte + GPS + opérateur + dette |
| **Génération quittance** | ✅ (serveur) | ⚠️ | Automatique post-encaissement ; données fiscales requises |
| **Impression Bluetooth 58 mm** | ✅ (code) | ❌ | **Jamais validé sur imprimante réelle** |
| **Scan QR commerce** | ⚠️ Saisie manuelle UUID | ⚠️ | **Pas de lecteur caméra** — contournement copier-coller jeton |
| **Historique encaissements** | ✅ (hub recouvrement) | ✅ | Menu agent « Historique » désactivé (« Bientôt ») — hub OK |
| **Historique quittances** | ✅ | ✅ | Via hub Recouvrement |
| **Réimpression quittance** | ✅ | ⚠️ | Dépend Bluetooth |
| **Cartographie municipale (SIG)** | ❌ mobile | ❌ | Web admin uniquement |
| **Signalements citoyens** | ✅ | ✅ | Agent peut consulter/créer via `/municipality` |
| **Dashboard superviseur** | ✅ web | ✅ web | Pas d'écran mobile ; URL `admin.mami.ga` |
| **Vérification publique quittance** | ✅ (web) | ✅ | `GET /public/receipts/verify/{token}` |
| **Contrôles terrain** | ❌ | ❌ | Menu « Bientôt disponible » |
| **Synchronisation offline** | ❌ | ❌ | Menu « Bientôt — V2.1 » |

**Légende :** ✅ prêt · ⚠️ conditionnel · ❌ bloquant ou absent

---

# 4. Chaîne complète Sprint 3

```mermaid
flowchart TD
    A[Login agent] --> B{Module municipality ON?}
    B -->|Non| X[Tuile grisée — NO GO]
    B -->|Oui| C[/municipality/agent]
    C --> D[Ouvrir session caisse]
    D --> E[Scan QR ou ID opérateur]
    E --> F[Situation fiscale]
    F --> G[Encaissement]
    G --> H[Quittance serveur]
    H --> I[Impression Bluetooth]
    I --> J[Dashboard superviseur web]

    D -.->|GPS requis| D
    G -.->|Session ouverte + GPS| G
    F -.->|Obligations en base| F
    I -.->|Imprimante appairée| I
    J -.->|admin.mami.ga| J
```

## 4.1 Détail étape par étape

| # | Étape | Statut code | API | Gap |
|---|-------|-------------|-----|-----|
| 1 | **Connexion** | ✅ | `POST /api/login` | Compte agent prod |
| 2 | **Navigation agent** | ⚠️ | — | Splash → `/` taxi ; agent doit aller à `/municipality/agent` |
| 3 | **Ouverture session** | ✅ | `POST .../cash-sessions/open` | GPS + montant initial |
| 4 | **Identification commerce** | ⚠️ | `GET .../operators/by-qr/{value}` | Saisie manuelle, pas caméra |
| 5 | **Consultation dette** | ✅ | `GET .../fiscal/operator/{id}/summary` | Prérequis : taxes + obligations |
| 6 | **Encaissement** | ✅ | `POST .../fiscal/collections` | Redirige auto vers impression |
| 7 | **Quittance** | ✅ | `MunicipalReceiptEmissionService` | PDF + hash + URL vérif côté serveur |
| 8 | **Impression Bluetooth** | ✅ code | `print_payload` dans réponse | **Validation matérielle manquante** |
| 9 | **Superviseur** | ✅ web | `FiscalSupervisorDashboardService` | Pas de mobile ; OK pour recette mairie |

## 4.2 Prérequis backoffice (obligatoires)

1. `MAMI_MODULE_MUNICIPALITY=true` + `php artisan config:cache`
2. Migrations municipality appliquées (batch 6+)
3. Agent créé (`/admin/users/agents/create`) avec rôle `municipal_agent`
4. ≥ 1 opérateur économique enrôlé (mobile ou API)
5. Chaîne fiscal : type taxe → taux → affectation → génération obligations
6. Imprimante thermique 58 mm Bluetooth appairée au téléphone test

---

# 5. Impression Bluetooth — audit technique

## 5.1 Stack logicielle

| Composant | Détail |
|-----------|--------|
| Plugin | `print_bluetooth_thermal: ^1.1.6` |
| Adaptateur | `bluetooth_printer_adapter.dart` — scan appairés, connect, writeBytes |
| ESC/POS | `esc_pos_command_builder.dart` — inline (plus de `esc_pos_utils_plus`) |
| Service | `printer_service.dart` — ticket 58 mm + QR vérification |
| Écran | `print_receipt_screen.dart` — sélection MAC, impression, réimpression audit |

## 5.2 Contenu ticket 58 mm

| Zone | Contenu |
|------|---------|
| En-tête | Commune d'Owendo (centré, gras) |
| Titre | QUITTANCE OFFICIELLE |
| Corps | Référence, commerce, ID public, montant XAF, date, agent, hash court |
| QR | URL `verification_url` (ESC/POS QR natif) |
| Fin | Feed + coupe papier (`GS V 0`) |

Largeur effective : **32 caractères** (standard 58 mm, police ESC/POS par défaut).

## 5.3 Permissions Android (`AndroidManifest.xml`)

| Permission | Présente |
|------------|----------|
| `BLUETOOTH` (≤ API 30) | ✅ |
| `BLUETOOTH_ADMIN` (≤ API 30) | ✅ |
| `BLUETOOTH_CONNECT` | ✅ |
| `BLUETOOTH_SCAN` (`neverForLocation`) | ✅ |
| `ACCESS_FINE_LOCATION` | ✅ (GPS encaissement) |

**Runtime :** le plugin `print_bluetooth_thermal` expose `isPermissionBluetoothGranted` — l'app ne demande pas explicitement les permissions runtime dans le code Dart (délégation plugin).

## 5.4 Imprimantes compatibles (théorique)

Toute imprimante **ESC/POS thermique 58 mm** avec profil Bluetooth SPP classique :

| Marque / type courant | Compatibilité attendue |
|-----------------------|------------------------|
| Imprimantes POS génériques 58 mm (MPT-II, ZJ-5802, etc.) | ✅ ESC/POS standard |
| Epson TM-P series (Bluetooth) | ✅ |
| Sunmi / PAX (si mode ESC/POS) | ⚠️ À valider |
| Imprimantes 80 mm | ⚠️ Mise en page non optimisée (58 mm codé) |

**Non validé en dépôt :** aucun test documenté sur matériel gabonais.

## 5.5 Risques impression terrain

| Risque | Gravité |
|--------|---------|
| QR ESC/POS mal rendu sur certains firmware | Moyenne |
| Encodage UTF-8 accents (« Owendo » OK ; caractères spéciaux commerce) | Faible |
| Android 12+ permissions Bluetooth non accordées | Moyenne |
| Imprimante non appairée dans paramètres Android | Fréquent |

---

# 6. Génération APK terrain

## 6.1 État actuel du dépôt

| APK | Statut | Chemin |
|-----|--------|--------|
| `mami_agent` debug | ❌ Projet absent | — |
| `mami_agent` release | ❌ Projet absent | — |
| **`mami_client` debug** (substitut agent) | ✅ **Généré 19/06/2026** | `mobile/mami_client/build/app/outputs/flutter-apk/app-debug.apk` |
| **`mami_client` release** (substitut agent) | ✅ **Généré 19/06/2026** (54 Mo) | `mobile/mami_client/build/app/outputs/flutter-apk/app-release.apk` |

> **Correctif build appliqué :** import manquant `fiscal_collection_repository.dart` dans `print_receipt_screen.dart` (erreur de compilation bloquante).

## 6.2 Commandes — APK Agent terrain (via `mami_client`)

**Prérequis :** Flutter 3.24+, Android SDK, dossier `mobile/mami_driver/packages/pusher_channels_flutter` présent (dépendance path).

### Debug (recette interne)

```bash
cd mobile/mami_client
flutter pub get
flutter build apk --debug \
  --dart-define=API_BASE_URL=https://api.mami.ga/api
```

**Sortie :** `build/app/outputs/flutter-apk/app-debug.apk`

### Release (distribution terrain)

```bash
cd mobile/mami_client
flutter build apk --release \
  --dart-define=API_BASE_URL=https://api.mami.ga/api
```

**Sortie :** `build/app/outputs/flutter-apk/app-release.apk`

> **Signature release :** configurer `android/key.properties` + keystore pour installation hors Play Store.

### Build exécuté lors de cet audit (19 juin 2026)

| Variante | Résultat | Durée Gradle |
|----------|----------|--------------|
| `--debug` | ✅ Succès | ~10 min |
| `--release` | ✅ Succès (54 Mo) | ~8 min |

**Avertissement Flutter :** plugins `print_bluetooth_thermal` et `pusher_channels_flutter` utilisent KGP legacy — build OK, migration future possible.

## 6.3 Procédure installation terrain

1. Transférer `app-release.apk` sur téléphone Android test
2. Autoriser sources inconnues
3. Appairer imprimante 58 mm dans Paramètres Bluetooth
4. Accorder GPS + Bluetooth à l'installation
5. Se connecter avec compte agent municipal
6. Naviguer : accueil → Municipalité → Agent terrain → Recouvrement  
   **Ou** deep link interne : route `/municipality/agent`

---

# 7. Checklist pré-test terrain (ops)

| # | Action | Responsable | Statut |
|---|--------|-------------|--------|
| 1 | VPS : `MAMI_MODULE_MUNICIPALITY=true` | Ops | ⏳ |
| 2 | Migrations + seeders Owendo | Ops | ⏳ |
| 3 | Agent municipal créé (admin) | Admin | ⏳ Sprint 3.1 |
| 4 | Données fiscal pilote (taxe, taux, affectation, obligations) | Admin fiscal | ⏳ |
| 5 | ≥ 1 opérateur enrôlé avec QR | Agent | ⏳ |
| 6 | APK release compilé et distribué | Dev/Ops | ⏳ |
| 7 | Imprimante 58 mm appairée + test impression | Terrain | ⏳ |
| 8 | Checklist [CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md](CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md) | Mairie | ⏳ |

---

# 8. Décision finale

# NO GO TEST TERRAIN

Le **code Sprint 3 est complet et enchaîné** dans `mami_client`. Un test terrain **fiable** n'est pas possible **immédiatement** sans préparation ops et validation matérielle. **Aucun développement P2–P5 n'est requis** pour lever les blocages listés ci-dessous.

## Blocages exacts

| ID | Blocage | Type | Levée sans dev ? |
|----|---------|------|------------------|
| **B1** | `MAMI_MODULE_MUNICIPALITY=false` en production | Infrastructure | ✅ Oui — `.env` + `config:cache` |
| **B2** | Migrations / seeders VPS non confirmés | Infrastructure | ✅ Oui — checklist déploiement |
| **B3** | Données fiscales pilote absentes (taxes, obligations) | Données | ✅ Oui — backoffice fiscal |
| **B4** | Compte agent municipal non déployé en prod | Données | ✅ Oui — `/admin/users/agents/create` |
| **B5** | Scan QR : saisie manuelle uniquement (pas caméra) | UX terrain | ⚠️ Contournement accepté (copier jeton QR) |
| **B6** | Impression Bluetooth **non validée** sur imprimante réelle | Matériel | ❌ Test terrain obligatoire |
| **B7** | APK agent non distribué (install terrain) | Ops | ✅ Build OK — reste transfert + install |
| **B8** | Splash agent → accueil taxi (navigation manuelle) | UX | ⚠️ Contournement : bookmark `/municipality/agent` |
| **B9** | Pas d'APK `mami_agent` dédié | Organisation | ⚠️ Acceptable Sprint 3 via `mami_client` |
| **B10** | Tests PHPUnit Municipality non verts (MySQL test) | Qualité CI | ⚠️ Non bloquant terrain si smoke test manuel OK |

## Passage à GO TEST TERRAIN

**GO** lorsque **tous** les critères suivants sont remplis :

- [ ] B1, B2, B3, B4 résolus
- [ ] B7 : APK release installé sur téléphone test (`app-release.apk`)
- [ ] B6 validé : ≥ 1 quittance imprimée lisible + QR scannable
- [ ] B5 et B8 acceptés par la mairie comme contournements Sprint 3
- [ ] Chaîne complète exécutée une fois sur le terrain (checklist validation)
- [ ] Superviseur confirme KPIs sur `admin.mami.ga/admin/municipality/collection`

---

## Annexes

| Document | Lien |
|----------|------|
| Audit testabilité Sprint 3 | [AUDIT_TESTABILITE_SPRINT3_MUNICIPALITE.md](AUDIT_TESTABILITE_SPRINT3_MUNICIPALITE.md) |
| Stabilisation Sprint 3.1 | [RAPPORT_STABILISATION_SPRINT3_1.md](RAPPORT_STABILISATION_SPRINT3_1.md) |
| Checklist déploiement VPS | [CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md](CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md) |
| Checklist validation terrain | [CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md](CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md) |
| Script vérif tables fiscal | `scripts/check_fiscal_tables.php` |

---

*Audit et préparation — aucune nouvelle fonctionnalité développée. Scission `mami_agent` reportée post-clôture Sprint 3.*
