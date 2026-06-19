# Audit test terrain Sprint 3 — Opérateurs économiques et chaîne QR → quittance

**Version :** 1.0  
**Date :** juin 2026  
**Contexte :** module Municipalité actif — fiscalité, utilisateurs et agents validés  
**Objectif :** déterminer si le Sprint 3 peut être validé intégralement sur le terrain avec un commerce réel, un agent réel et une imprimante Bluetooth réelle  
**Hors scope :** développement P2–P5 — audit et procédures uniquement

---

## Synthèse exécutive

| Question | Réponse |
|----------|---------|
| Module Economic Operators présent ? | **Oui** — tables, API, Flutter enrôlement |
| QR généré automatiquement ? | **Oui** — à l'enrôlement + à la demande (PNG) |
| Scan QR Flutter implémenté ? | **Non** — saisie manuelle UUID uniquement |
| Chaîne complète exécutable ? | **Oui, sous conditions** (voir §4) |
| Format référence commerce | **`OWE-COM-000001`** (code actuel), pas `OWD-COM-*` |
| Verdict recette terrain intégrale | **GO CONDITIONNEL** |

---

# 1. État du module Economic Operators

## 1.1 Tables — existence et migration

| Table | Migration | Statut schéma | Données locales (audit) |
|-------|-----------|---------------|-------------------------|
| `economic_operator_categories` | `2026_06_20_*` | ✅ OK | Seedées (catégories) |
| `economic_zones` | `2026_06_20_*` | ✅ OK | 8 zones (Owendo) |
| `economic_operators` | `2026_06_20_*` | ✅ OK | **0 commerce** |
| `economic_operator_qrcodes` | `2026_06_21_*` | ✅ OK | **0 QR** |
| `economic_operator_tax_status` | `2026_06_20_*` | ✅ OK | — |
| `operator_tax_assignments` | `2026_06_23_*` | ✅ OK | **0 affectation** |

**Vérification VPS :**

```bash
php scripts/check_economic_operators.php
php scripts/check_fiscal_tables.php
```

## 1.2 Services backend clés

| Service | Rôle |
|---------|------|
| `EconomicOperatorService` | Enrôlement terrain + **génération QR auto** |
| `EconomicOperatorReferenceGenerator` | Numérotation séquentielle `OWE-COM-%06d` |
| `QRCodeManagement` | UUID scan, PNG, résolution `by-qr` |
| `FiscalAssignmentService` | Affectation taxe → opérateur |
| `FiscalObligationGeneratorService` | Génération obligations |
| `FiscalCollectionService` | Encaissement + quittance |
| `OperatorFiscalSummaryService` | Situation fiscale + trace scan |

## 1.3 API opérateurs (extrait)

| Méthode | Route | Usage terrain |
|---------|-------|---------------|
| POST | `/api/municipality/operators` | Enrôler commerce pilote |
| GET | `/api/municipality/operators/{id}` | Fiche commerce |
| GET | `/api/municipality/operators/{id}/qrcode/png` | Télécharger QR imprimable |
| GET | `/api/municipality/operators/{id}/business-card` | Aperçu + `scan_token` |
| GET | `/api/municipality/operators/by-qr/{value}` | Résolution scan |
| GET | `/api/municipality/fiscal/operator/{id}/summary` | Situation fiscale |
| POST | `/api/municipality/fiscal/collections` | Encaissement |
| GET | `/api/municipality/fiscal/receipts/{id}` | Quittance + `print_payload` |

---

# 2. Procédure — commerce pilote de bout en bout

## Étape A — Créer un commerce pilote

### Canal recommandé : APK agent (`mami_client`)

1. Connexion compte `municipal_agent`
2. Navigation : **Agent terrain** → **Recensement économique** (`/municipality/enrollment/new`)
3. Remplir : nom commercial, activité, catégorie, responsable, téléphone
4. **GPS** : précision ≤ 20 m (Flutter) / ≤ 15 m (serveur selon config)
5. Photo **façade** obligatoire (caméra)
6. Valider l'enrôlement

### Résultat automatique

| Élément | Valeur |
|---------|--------|
| `public_id` | `OWE-COM-000001` (premier commerce) |
| `economic_operators` | 1 ligne insérée |
| `economic_operator_qrcodes` | 1 QR actif (`qr_uuid` UUID v4) |
| Territoire / quartier | Résolu par GPS (Owendo) |

**API équivalente :** `POST /api/municipality/operators` (multipart + GPS + façade).

### Canal alternatif

Aucun écran web admin CRUD opérateur — **enrôlement mobile ou API uniquement**.

---

## Étape B — Affecter une taxe municipale

1. Admin → **Fiscalité Owendo** → **Types de taxes** : créer ex. `TAX-BOUTIQUE`
2. **Taux** : montant + périodicité + date validité
3. **Affectations** : sélectionner le commerce (`OWE-COM-000001`) + taxe
4. **Obligations** → **Générer** (période courante)

Sans affectation + obligations, la situation fiscale affichera **solde 0** et l'encaissement peut être refusé ou sans effet métier.

---

## Étape C — Obtenir le QR code

### Génération automatique (déjà en place)

À l'enrôlement, `EconomicOperatorService::enroll()` appelle :

```php
$this->qrCodeManagement->generateForOperator($operator);
```

La réponse API enrôlement inclut :

```json
{
  "qr_code": {
    "display_id": "OWE-COM-000001",
    "display_label": "OWE-COM-000001",
    "display_label_with_suffix": "QR-OWE-COM-000001-XXXXXXXX",
    "scan_token": "<uuid-v4>",
    "png_url": "https://api.mami.ga/api/municipality/operators/1/qrcode/png"
  }
}
```

### Téléchargement image QR

```http
GET /api/municipality/operators/{id}/qrcode/png
Authorization: Bearer {token_agent}
```

- Image PNG (ou SVG si GD absent)
- **Contenu encodé dans le QR :** `qr_uuid` (UUID), **pas** le `public_id` seul (sécurité anti-énumération)

### Impression carte pro

- `GET /api/municipality/operators/{id}/business-card` — aperçu JSON
- PDF carte : **non implémenté** (réponse 501)

---

## Étape D — Consulter la situation fiscale

### Mobile

1. Hub **Recouvrement** → **Scanner QR commerce**
2. Saisir le **`scan_token`** (UUID) — voir §3
3. Ou : **Situation fiscale** → ID opérateur interne
4. Écran affiche : montant dû, payé, solde, obligations ouvertes
5. Bouton **Encaisser** pré-remplit opérateur + solde

### API

```http
GET /api/municipality/fiscal/operator/{operatorId}/summary
```

---

## Étape E — Encaisser et quittance

1. **Ouvrir caisse** (GPS requis)
2. Encaissement avec `operator_id`, montant, `cash_session_id`, GPS
3. Serveur génère quittance (`MunicipalReceiptEmissionService`)
4. App redirige vers **Impression quittance**
5. Sélection imprimante Bluetooth 58 mm → Imprimer

### Superviseur

- Web : `https://admin.mami.ga/admin/municipality/collection`
- Vérifier encaissement par agent, quartier, jour

---

# 3. Scan QR Flutter — état réel

## Verdict : **saisie manuelle UUID — pas de lecteur caméra**

| Élément | Implémenté ? | Fichier |
|---------|--------------|---------|
| Écran « Scanner QR » | ✅ UI | `scan_operator_screen.dart` |
| Champ texte UUID | ✅ | `TextField` jeton QR |
| Lecteur caméra (`mobile_scanner`, etc.) | ❌ | Absent du `pubspec.yaml` |
| Appel API `by-qr` | ✅ | `lookupOperatorByQr()` |
| Redirection situation fiscale | ✅ | `/municipality/recovery/fiscal-summary/:id` |

**Texte UI trompeur :** « Saisissez le jeton QR (UUID) ou scannez avec l'appareil photo » — la seconde option **n'est pas codée**.

### Contournement terrain Sprint 3 (accepté)

| Méthode | Procédure |
|---------|-----------|
| **A — Copier `scan_token`** | Après enrôlement ou `GET .../business-card`, coller l'UUID dans le champ |
| **B — Format composite** | Coller `QR-OWE-COM-000001-XXXXXXXX` (suffixe 8 car. du UUID) |
| **C — ID opérateur** | Hub → Situation fiscale → saisir l'ID numérique interne |

### Formats rejetés par l'API (sécurité)

| Saisie | Résultat |
|--------|----------|
| `OWE-COM-000001` seul | ❌ 404 |
| `QR-OWE-COM-000001` sans suffixe | ❌ 404 |
| UUID v4 complet | ✅ |
| `QR-OWE-COM-000001-XXXXXXXX` | ✅ |

---

# 4. Chaîne complète — exécutabilité

```mermaid
flowchart LR
    A[Enrôlement commerce] --> B[QR auto généré]
    B --> C[Affectation taxe admin]
    C --> D[Génération obligations]
    D --> E[Scan UUID manuel]
    E --> F[Situation fiscale]
    F --> G[Ouverture caisse]
    G --> H[Encaissement]
    H --> I[Quittance serveur]
    I --> J[Impression Bluetooth]
    J --> K[Dashboard superviseur web]
```

## Matrice par maillon

| Maillon | Code | Testable terrain | Blocage |
|---------|------|------------------|---------|
| Commerce enrôlé | ✅ | ⚠️ | Aucun commerce en base actuellement |
| QR Code | ✅ auto | ✅ | Télécharger PNG ou copier `scan_token` |
| Scan QR mobile | ⚠️ manuel | ⚠️ | Pas de caméra — contournement UUID |
| Situation fiscale | ✅ | ⚠️ | Obligations requises |
| Encaissement | ✅ | ⚠️ | Session caisse + GPS |
| Génération quittance | ✅ | ✅ | Automatique post-encaissement |
| Impression Bluetooth | ✅ code | ❌ | **Validation matérielle non faite** |
| Superviseur | ✅ web | ✅ | `admin.mami.ga` |

## Conditions obligatoires pour exécuter la chaîne

- [ ] ≥ 1 commerce enrôlé (`OWE-COM-000001`)
- [ ] ≥ 1 affectation taxe active
- [ ] ≥ 1 obligation générée avec solde > 0
- [ ] Agent municipal connecté sur APK
- [ ] Session de caisse ouverte
- [ ] `scan_token` copié ou ID opérateur connu
- [ ] Imprimante 58 mm appairée (test impression)

---

# 5. Génération automatique des QR — confirmée

| Déclencheur | Comportement |
|-------------|--------------|
| **Enrôlement** (`POST /operators`) | QR créé immatiatement — test `QRCodeManagementTest::test_enrollment_generates_secure_uuid_scan_token` |
| **Téléchargement PNG** | Génère si absent (`downloadPng`) |
| **Carte pro** | Génère si absent (`businessCard`) |
| **Révocation** | Nouveau QR désactive l'ancien (`deactivateActiveCodes`) |

### Structure QR

| Champ | Contenu |
|-------|---------|
| `qr_uuid` | UUID v4 — **payload scanné** |
| `qr_value` | `OWE-COM-000001` — libellé affiché sous le QR |
| `display_label_with_suffix` | `QR-OWE-COM-000001-XXXXXXXX` — format alternatif scannable |

**Pas d'écran admin « Générer QR »** — tout est automatique côté enrôlement/API.

---

# 6. Numérotation `OWD-COM` vs `OWE-COM` — proposition documentaire

## État actuel du code

`EconomicOperatorReferenceGenerator` génère :

```
OWE-COM-000001 → OWE-COM-000002 → … → OWE-COM-006000
```

Préfixe **OWE** = code territoire Owendo (`MunicipalTerritory::code = 'OWE'`).

## Écart avec la demande `OWD-COM-*`

| Format demandé | Format implémenté |
|----------------|-------------------|
| `OWD-COM-000001` | `OWE-COM-000001` |
| Plage 1–6000 | Plage illimitée, séquence auto |

**Impact terrain Sprint 3 :** aucun si la mairie accepte `OWE-COM` comme référence officielle Owendo.

## Proposition de migration (backlog post-Sprint 3 — sans dev maintenant)

Si la mairie exige explicitement `OWD-COM` :

| Option | Description | Effort |
|--------|-------------|--------|
| **A — Alias affichage** | Conserver `OWE-COM` en base, afficher `OWD-COM` sur cartes/imprimés | Faible |
| **B — Renommage préfixe** | Modifier `EconomicOperatorReferenceGenerator` : `OWD-COM-%06d` | Moyen — migration données existantes |
| **C — Plage réservée** | Paramètre `MAMI_OPERATOR_ID_PREFIX=OWD-COM` + `MAMI_OPERATOR_ID_MAX=6000` | Moyen |

### Spécification cible (documentaire)

```
Premier commerce pilote : OWD-COM-000001 (ou OWE-COM-000001 selon décision mairie)
Dernier prévu phase 1   : OWD-COM-006000
Capacité                : 6 000 commerces Owendo
Séquence                : transaction DB lockForUpdate — pas de trous garantis
QR                      : UUID indépendant du numéro séquentiel (sécurité)
```

**Recommandation Sprint 3 :** utiliser **`OWE-COM-000001`** tel que codé — valider avec la mairie avant renommage.

---

# 7. Scénario recette terrain — pas à pas

## Jour J — préparation (30 min)

| # | Action | Outil |
|---|--------|-------|
| 1 | Vérifier module actif | VPS `.env` |
| 2 | Créer taxe pilote `TAX-PILOTE` + taux | Admin fiscal |
| 3 | Agent connecté sur APK release | `mami_client` |
| 4 | Imprimante 58 mm appairée | Android Bluetooth |

## Jour J — exécution (45 min)

| # | Acteur | Action | Résultat attendu |
|---|--------|--------|------------------|
| 1 | Agent | Enrôler « Boutique Pilote SNI » | `OWE-COM-000001` + QR |
| 2 | Agent | Noter `scan_token` (business-card API) | UUID copié |
| 3 | Admin | Affecter `TAX-PILOTE` au commerce | `operator_tax_assignments` |
| 4 | Admin | Générer obligations | ≥ 1 obligation ouverte |
| 5 | Agent | Ouvrir caisse | Session `open` |
| 6 | Agent | Coller UUID → situation fiscale | Solde > 0 |
| 7 | Agent | Encaisser montant | Quittance créée |
| 8 | Agent | Imprimer Bluetooth | Ticket 58 mm + QR vérif |
| 9 | Superviseur | Vérifier dashboard web | Encaissement visible |
| 10 | Mairie | Scanner QR vérif publique | `mami.ga/public/receipts/verify/{token}` |

---

# 8. Décision — validation intégrale Sprint 3

# GO CONDITIONNEL TEST TERRAIN

Le Sprint 3 **peut être validé intégralement sur le terrain** avec un commerce réel, un agent réel et une imprimante Bluetooth réelle, **sous réserve** des conditions ci-dessous.

## Ce qui est prêt (confirmé par le code)

- Module Economic Operators complet (tables, API, enrôlement Flutter)
- Génération QR **automatique** à l'enrôlement
- Chaîne fiscal → encaissement → quittance → impression (code)
- Résolution QR sécurisée (UUID, anti-énumération)
- Dashboard superviseur web

## Conditions de passage à GO TERRAIN INTÉGRAL

| # | Condition | Type | Bloquant ? |
|---|-----------|------|------------|
| C1 | Enrôler ≥ 1 commerce pilote réel | Données | **Oui** |
| C2 | Affecter taxe + générer obligations | Données | **Oui** |
| C3 | Accepter saisie manuelle UUID (pas caméra) | UX | Non si mairie OK |
| C4 | Valider impression sur imprimante 58 mm réelle | Matériel | **Oui** |
| C5 | Confirmer préfixe `OWE-COM` vs `OWD-COM` | Métier | Non (documentaire) |

## Blocages restants

| ID | Blocage | Levée |
|----|---------|-------|
| **O1** | 0 commerce en base | Enrôlement terrain étape 1 |
| **O2** | 0 affectation / obligation | Backoffice fiscal |
| **O3** | Scan QR sans caméra | Contournement `scan_token` |
| **O4** | Bluetooth non validé matériel | Test impression terrain |
| **O5** | Pas d'écran admin opérateurs | Enrôlement mobile suffisant Sprint 3 |

## NO GO uniquement si

- Refus mairie du contournement scan manuel **et** exigence caméra immédiate (backlog BL-11)
- Échec impression Bluetooth sur matériel terrain après configuration correcte
- Impossibilité d'enrôler un commerce (GPS, permissions, API)

---

## Annexes

| Ressource | Chemin |
|-----------|--------|
| Script vérif opérateurs | `scripts/check_economic_operators.php` |
| Script vérif fiscal | `scripts/check_fiscal_tables.php` |
| Workflow QR (spec) | `docs/municipality-v3/06_WORKFLOW_QR_COLLECTION.md` |
| Préparation APK | `docs/RAPPORT_PREPARATION_TEST_TERRAIN_SPRINT3.md` |
| Checklist terrain | `docs/CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md` |

---

*Audit documentaire — aucune nouvelle fonctionnalité développée.*
