# Procédure — Recette terrain finale Sprint 3

**Version :** 1.0  
**Date :** juin 2026  
**Objectif :** valider intégralement le cycle économique municipal avec un commerce réel  
**Hors scope :** P2–P5 — aucune nouvelle fonctionnalité

---

## Synthèse

| Élément | Statut |
|---------|--------|
| Création commerce via APK | ✅ Opérationnel |
| QR auto à l'enrôlement | ✅ Backend |
| Affectation taxe + obligations | ✅ Admin web |
| Chaîne encaissement → quittance | ✅ Code complet |
| Scan QR caméra | ❌ Saisie manuelle UUID |
| APK release à jour | ⚠️ **Regénérer** depuis `HEAD` (voir §7) |

---

# 1. Création opérateur pilote depuis `mami_client`

## 1.1 Prérequis APK

- Compte **`municipal_agent`** actif (voir §5)
- `MAMI_MODULE_MUNICIPALITY=true` sur l'API
- GPS activé, précision ≤ 20 m
- Connexion `https://api.mami.ga/api`

## 1.2 Navigation dans l'APK

```
Login
  → Accueil Super App (/) 
  → Tuile « Municipalité » OU route directe /municipality/agent
  → Accueil agent municipal
  → « Recensement économique »
  → /municipality/enrollment/new
```

**Raccourci terrain :** après login, si la tuile Municipalité est grisée, vérifier `GET /api/app/features` → `modules.municipality: true`, puis relancer l'app.

## 1.3 Formulaire enrôlement (terrain)

| Champ | Exemple pilote | Obligatoire |
|-------|----------------|-------------|
| GPS | Position sur place commerce | ✅ ≤ 20 m |
| Confirmation carte | Cocher « Je confirme l'emplacement » | ✅ |
| Catégorie | Boutique / Alimentation | ✅ |
| Nom commercial | **Boutique Pilote Owendo** | ✅ |
| Activité | Alimentation générale | ✅ |
| Responsable | Nom du commerçant | ✅ |
| Téléphone | +24106XXXXXXX | ✅ |
| Photo façade | Photo devanture | ✅ |

## 1.4 Validation

- Message SnackBar : `Commerce OWE-COM-000001 enregistré`
- Retour écran agent — KPI « Total enregistrés » +1

## 1.5 API sous-jacente

```http
POST /api/municipality/operators
Content-Type: multipart/form-data
Authorization: Bearer {token_agent}
```

Le serveur crée automatiquement :
- ligne `economic_operators`
- ligne `economic_operator_qrcodes` (UUID actif)
- statut fiscal initial `a_jour`

---

# 2. Procédure complète — cycle économique

## Phase A — Préparation backoffice (Administrateur)

| # | Action | URL / outil | Résultat |
|---|--------|-------------|----------|
| A1 | Connexion admin | `https://admin.mami.ga/login` | Dashboard |
| A2 | Créer type taxe | Fiscalité → Types → `TAX-PILOTE` | `municipal_tax_types` |
| A3 | Créer taux | Fiscalité → Taux → ex. 5 000 XAF / mensuel | `municipal_tax_rates` |
| A4 | (Optionnel) Objectif annuel | Fiscalité → Objectifs | `municipal_collection_targets` |

## Phase B — Enrôlement commerce (Agent APK)

| # | Action | Résultat |
|---|--------|----------|
| B1 | Agent se connecte sur APK | Token Sanctum |
| B2 | Recensement économique (§1) | `OWE-COM-000001` |
| B3 | Noter `scan_token` | Voir B4 |

### B4 — Récupérer le QR / scan_token

L'APK n'affiche pas le UUID après enrôlement. **Récupération obligatoire via API :**

```http
GET /api/municipality/operators/{id}/business-card
Authorization: Bearer {token_agent}
```

Noter :
- `data.scan_token` → UUID à coller dans le scan manuel
- `data.public_id` → `OWE-COM-000001`

**Télécharger image QR imprimable :**

```http
GET /api/municipality/operators/{id}/qrcode/png
```

Coller le QR sur la devanture du commerce pilote (impression papier).

## Phase C — Fiscalité (Administrateur)

| # | Action | URL | Table |
|---|--------|-----|-------|
| C1 | Affecter taxe au commerce | Fiscalité → Affectations | `operator_tax_assignments` |
| C2 | Générer obligations | Fiscalité → Obligations → Générer | `fiscal_obligations` |

Vérifier : message « X obligation(s) créée(s) » avec **X ≥ 1**.

## Phase D — Recouvrement (Agent APK)

| # | Action | Route APK | API |
|---|--------|-----------|-----|
| D1 | Ouvrir caisse | Recouvrement → Ouvrir caisse | `POST .../cash-sessions/open` |
| D2 | Identifier commerce | Recouvrement → Scanner QR → coller UUID | `GET .../operators/by-qr/{uuid}` |
| D3 | Situation fiscale | Auto après scan | `GET .../fiscal/operator/{id}/summary` |
| D4 | Encaisser | Bouton Encaisser | `POST .../fiscal/collections` |
| D5 | Quittance | Redirection auto | Quittance dans réponse |
| D6 | Impression | Écran Impression → Imprimer | Bluetooth ESC/POS 58 mm |
| D7 | Fermer caisse | Recouvrement → Fermer caisse | `POST .../close` |

## Phase E — Vérification publique

| # | Action | Résultat |
|---|--------|----------|
| E1 | Scanner QR sur ticket | `https://mami.ga/public/receipts/verify/{token}` |
| E2 | Statut | « Quittance valide » |

## Phase F — Superviseur (Administrateur web)

| # | Action | URL |
|---|--------|-----|
| F1 | Encaissements du jour | `/admin/municipality/collection` |
| F2 | Quittances émises | `/admin/municipality/mayor` |
| F3 | Carte SIG commerce | `/admin/municipality/map` |

---

# 3. Vérification base de données

Exécuter après chaque phase :

```bash
php scripts/check_economic_operators.php
php scripts/check_fiscal_tables.php
```

## Requêtes SQL de contrôle

```sql
-- Commerce pilote
SELECT id, public_id, commercial_name, is_active
FROM economic_operators
WHERE public_id = 'OWE-COM-000001';

-- QR actif
SELECT operator_id, qr_uuid, qr_value, is_active
FROM economic_operator_qrcodes
WHERE operator_id = (SELECT id FROM economic_operators WHERE public_id = 'OWE-COM-000001');

-- Affectation taxe
SELECT ota.id, eo.public_id, mtt.code, ota.is_active
FROM operator_tax_assignments ota
JOIN economic_operators eo ON eo.id = ota.operator_id
JOIN municipal_tax_types mtt ON mtt.id = ota.tax_type_id
WHERE eo.public_id = 'OWE-COM-000001';

-- Obligations
SELECT fo.reference, fo.amount_due, fo.balance_due, fo.status
FROM fiscal_obligations fo
JOIN economic_operators eo ON eo.id = fo.operator_id
WHERE eo.public_id = 'OWE-COM-000001';
```

## Matrice présence attendue

| Table | Après enrôlement | Après affectation | Après génération | Après encaissement |
|-------|------------------|-------------------|------------------|-------------------|
| `economic_operators` | ✅ 1 ligne | ✅ | ✅ | ✅ |
| `economic_operator_qrcodes` | ✅ 1 QR actif | ✅ | ✅ | ✅ |
| `operator_tax_assignments` | — | ✅ 1 ligne | ✅ | ✅ |
| `fiscal_obligations` | — | — | ✅ ≥ 1 | solde ↓ |
| `municipal_cash_sessions` | — | — | — | ✅ session |
| `municipal_collections` | — | — | — | ✅ 1 ligne |
| `municipal_receipts` | — | — | — | ✅ 1 quittance |

---

# 4. Dashboard superviseur — indicateurs attendus

## Recouvrement — `/admin/municipality/collection`

| KPI | Source | Après encaissement pilote |
|-----|--------|---------------------------|
| Sessions ouvertes | `municipal_cash_sessions` status=open | ≥ 1 pendant test |
| Collecté le {date} | Somme collections du jour | = montant encaissé |
| Par agent | Groupement par `agent_id` | Nom agent + total |
| Par quartier | Groupement quartier opérateur | Quartier du commerce |
| Par jour | Historique | Point du jour J |

## Quittances maire — `/admin/municipality/mayor`

| KPI | Attendu après test |
|-----|-------------------|
| Quittances émises | +1 |
| Quittances annulées | 0 |
| Montant encaissé | Montant pilote |
| Par quartier / agent / taxe | Lignes cohérentes |

Filtrer par **date du jour** du test.

---

# 5. Comptes à utiliser — liste exacte

## 5.1 Administrateur (backoffice + superviseur)

| Champ | Valeur |
|-------|--------|
| **URL** | `https://admin.mami.ga/login` |
| **Email** | `admin@mami.ga` |
| **Mot de passe** | `password` |
| **Rôle** | Admin web (`is_admin=true`) |
| **Usage** | Fiscalité, affectations, obligations, dashboards |

> Source : `database/seeders/AdminSeeder.php` — **changer le mot de passe en production** si encore par défaut.

## 5.2 Agent municipal (APK terrain)

| Champ | Valeur recommandée |
|-------|-------------------|
| **Création** | Admin → Utilisateurs → Créer un agent municipal |
| **Email** | `agent.owendo@mami.ga` *(à créer)* |
| **Mot de passe** | Défini à la création — **noter sur fiche terrain** |
| **Rôle auto** | `municipal_agent` |
| **APK** | `mami_client` release (APK agent) |
| **Usage** | Enrôlement, caisse, encaissement, impression |

**Pas de seeder agent** — compte à créer une fois via `/admin/users/agents/create` avant le Jour J.

## 5.3 Commerce pilote (données métier — pas un login)

| Champ | Valeur recommandée |
|-------|-------------------|
| **Référence** | `OWE-COM-000001` (auto, premier commerce) |
| **Nom commercial** | Boutique Pilote Owendo |
| **Responsable** | Nom réel commerçant présent sur place |
| **Téléphone** | Numéro réel du commerce |
| **Compte utilisateur** | **Aucun** — fiche `economic_operators` uniquement |
| **QR** | UUID généré à l'enrôlement — affiché sur devanture |

## 5.4 Fiche terrain à remplir le Jour J

| Rôle | Identifiant | Mot de passe | Notes |
|------|-------------|--------------|-------|
| Admin | admin@mami.ga | ******** | |
| Agent | agent.owendo@mami.ga | ******** | |
| Commerce | OWE-COM-000001 | — | scan_token : ________________ |
| Quittance test | OWE-RCP-2026-______ | — | après encaissement |

---

# 6. Contenu APK release — vérification

## 6.1 Fonctionnalités embarquées (`mami_client`)

| Fonction | Écran | Route | Présent |
|----------|-------|-------|---------|
| Recensement économique | `enroll_economic_operator_screen.dart` | `/municipality/enrollment/new` | ✅ |
| Consultation fiscale | `fiscal_summary_screen.dart` | `/municipality/recovery/fiscal-summary/:id` | ✅ |
| Encaissement | `collect_cash_screen.dart` | `/municipality/recovery/collect` | ✅ |
| Impression Bluetooth | `print_receipt_screen.dart` | `/municipality/recovery/print-receipt/:id` | ✅ |
| Hub recouvrement | `recovery_hub_screen.dart` | `/municipality/recovery` | ✅ |
| Ouverture / fermeture caisse | `open/close_cash_session_screen.dart` | ✅ | ✅ |
| Scan QR (saisie UUID) | `scan_operator_screen.dart` | `/municipality/recovery/scan` | ✅ |
| Historique quittances | `receipt_history_screen.dart` | ✅ | ✅ |

**Dépendances terrain :** `print_bluetooth_thermal`, `geolocator`, `flutter_map` (enrôlement).

## 6.2 Permissions Android (APK)

- GPS (`ACCESS_FINE_LOCATION`)
- Bluetooth (`BLUETOOTH_CONNECT`, `BLUETOOTH_SCAN`)
- Internet

## 6.3 Limites connues (acceptées Sprint 3)

- Pas de lecteur caméra QR
- Pas d'APK `mami_agent` séparé — même binaire que client
- Splash redirige vers accueil taxi — agent doit aller manuellement à `/municipality/agent`

---

# 7. Faut-il regénérer l'APK ?

## Verdict : **OUI — regénérer avant le test terrain**

| Critère | État |
|---------|------|
| Branche cible | `feature/mami-taxi-v2-p2` ≥ commit `e9a6f54` |
| Correctif impression (`print_receipt_screen.dart`) | Inclus depuis Sprint 3.1 |
| Admin agents municipaux | Backend requis — pas dans APK mais API nécessaire |
| APK local existant | Peut dater d'un build antérieur — **non garanti aligné HEAD** |

## Commande recommandée

```bash
cd mobile/mami_client
flutter pub get
flutter build apk --release \
  --dart-define=API_BASE_URL=https://api.mami.ga/api
```

**Sortie :** `build/app/outputs/flutter-apk/app-release.apk`

## Checklist pré-installation

- [ ] APK compilé depuis commit déployé sur VPS
- [ ] `API_BASE_URL=https://api.mami.ga/api`
- [ ] VPS : module municipalité actif + migrations à jour
- [ ] Agent municipal créé en admin
- [ ] Imprimante 58 mm appairée au téléphone test

**Pas de regénération nécessaire** uniquement si l'APK installé a été buildé depuis le même commit que le backend production **et** le test d'impression debug a déjà réussi.

---

# 8. Grille de validation finale Sprint 3

| # | Critère | OK |
|---|---------|-----|
| 1 | Commerce réel enrôlé sur terrain | ☐ |
| 2 | QR imprimé et `scan_token` fonctionnel (saisie manuelle) | ☐ |
| 3 | Affectation + obligation visible en admin | ☐ |
| 4 | Encaissement agent sans erreur API | ☐ |
| 5 | Quittance `OWE-RCP-2026-*` générée | ☐ |
| 6 | Ticket Bluetooth 58 mm lisible + QR vérif | ☐ |
| 7 | Page publique vérification OK | ☐ |
| 8 | Dashboard recouvrement + maire à jour | ☐ |
| 9 | 4 tables SQL conformes (§3) | ☐ |

**Sprint 3 validé intégralement** lorsque les 9 cases sont cochées.

---

## Documents liés

| Document | Usage |
|----------|-------|
| [CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md](CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md) | Grille terrain détaillée |
| [RAPPORT_AUDIT_TEST_TERRAIN_SPRINT3_OPERATEURS.md](RAPPORT_AUDIT_TEST_TERRAIN_SPRINT3_OPERATEURS.md) | Audit chaîne QR |
| [RAPPORT_PREPARATION_TEST_TERRAIN_SPRINT3.md](RAPPORT_PREPARATION_TEST_TERRAIN_SPRINT3.md) | Préparation APK |
| [CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md](CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md) | Prérequis VPS |

---

*Procédure recette terrain — documentation uniquement, aucun développement.*
