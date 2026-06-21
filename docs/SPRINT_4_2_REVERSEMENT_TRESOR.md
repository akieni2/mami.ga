# Sprint 4.2 — Reversement Trésor Public

**Cycle complet de reversement des recettes vers le Trésor Public gabonais**

| | |
|---|---|
| **Prérequis** | Sprint 4.1 (visa DAF / Receveur) |
| **Base existante** | `municipal_treasury_remittances` (Sprint 4.0 — brouillon) |
| **Priorité** | P0 |

---

## 1. Objectif fonctionnel

Permettre au **Receveur municipal** et au **DAF** de constituer, valider, déposer et **confirmer** les reversements de recettes encaissées sur le terrain, avec pièces justificatives (bordereau, reçu Trésor).

---

## 2. Cycle de vie

```
draft ──► controlled ──► daf_validated ──► receveur_validated ──► deposited ──► confirmed
   │           │                │                    │
   └───────────┴────────────────┴────────────────────┴── reject → draft (correction)
```

| État | Acteur | Description |
|------|--------|-------------|
| `draft` | Caissier / DAF adjoint | Montant proposé, période |
| `controlled` | Contrôleur financier | Contrôle cohérence encaissements |
| `daf_validated` | DAF | Visa ordonnancement |
| `receveur_validated` | Receveur municipal | Validation avant dépôt |
| `deposited` | Receveur | Dépôt effectué (date, banque, ref) |
| `confirmed` | Receveur / import Trésor | Accusé réception Trésor |

---

## 3. Évolution table `municipal_treasury_remittances`

| Colonne | Type | Description |
|---------|------|-------------|
| `slip_number` | string(40) | Numéro bordereau |
| `bank_name` | string(120) | Banque de dépôt |
| `deposit_reference` | string(80) | Référence versement |
| `deposited_at` | datetime | Date effective dépôt |
| `treasury_receipt_ref` | string(80) | Reçu Trésor Public |
| `controlled_by` / `controlled_at` | | Contrôleur |
| `daf_validated_by` / `daf_validated_at` | | DAF |
| `receveur_validated_by` / `receveur_validated_at` | | Receveur |
| `confirmed_at` | datetime | |
| `rejection_reason` | text | |

### Pièces jointes — `municipal_treasury_remittance_attachments`

| Colonne | Description |
|---------|-------------|
| `remittance_id` | FK |
| `purpose` | `bordereau_pdf` · `treasury_receipt` · `bank_slip` |
| `path` / `disk` | Stockage objet |
| `uploaded_by` | FK users |

### Lien recettes

Table pivot `municipal_treasury_remittance_payments` :

- `remittance_id` + `municipal_payment_id` + `amount_allocated`

Permet de justifier le montant reversement vs encaissements terrain.

---

## 4. API

Préfixe : `/api/municipality/finance/remittances`

| Méthode | Route | Action |
|---------|-------|--------|
| GET | `/` | Liste (filtres statut, période) |
| POST | `/` | Créer brouillon (+ sélection paiements optionnelle) |
| GET | `/{id}` | Détail + attachments + paiements liés |
| PUT | `/{id}` | Modifier brouillon |
| POST | `/{id}/submit-control` | draft → controlled |
| POST | `/{id}/validate-daf` | controlled → daf_validated |
| POST | `/{id}/validate-receveur` | daf_validated → receveur_validated |
| POST | `/{id}/record-deposit` | + slip_number, bank, deposit_reference, deposited_at |
| POST | `/{id}/confirm` | + treasury_receipt_ref |
| POST | `/{id}/reject` | Retour draft ou controlled |
| POST | `/{id}/attachments` | Upload PDF (multipart) |
| GET | `/{id}/attachments/{attachment}` | Téléchargement |

### Agrégation automatique (option)

`POST /remittances/generate-from-period` — propose un brouillon à partir des `municipal_payments` non reversés sur `[period_start, period_end]`.

---

## 5. Flutter

| Écran | Route | Description |
|-------|-------|-------------|
| **Treasury Remittance List** | `/municipality/finance/remittances` | Liste par statut |
| **Treasury Remittance Detail** | `/municipality/finance/remittances/{id}` | Montant, paiements, PJ |
| **Treasury Validation Screen** | Actions selon rôle | Visa contrôleur / DAF / receveur |
| **Deposit Form** | Modal / écran | Banque, ref, date, upload bordereau |

---

## 6. Comptabilité (préparation 4.3)

À la confirmation (`confirmed`), émettre événement `TreasuryRemittanceConfirmed` pour écriture comptable :

- Débit : compte banque / Trésor
- Crédit : compte recettes collectées

---

## 7. Tests PHP

| Test | Scénario |
|------|----------|
| `TreasuryRemittanceWorkflowTest` | Cycle complet draft → confirmed |
| `TreasuryRemittanceWorkflowTest::test_payment_allocation` | Montant = somme paiements |
| `TreasuryRemittanceWorkflowTest::test_reject_returns_to_draft` | |
| `TreasuryRemittanceAttachmentTest` | Upload PDF |

---

## 8. Recette

| # | Vérification |
|---|--------------|
| 1 | Brouillon généré depuis encaissements période |
| 2 | Visa contrôleur + DAF + receveur |
| 3 | PDF bordereau attaché |
| 4 | Confirmation avec ref Trésor |
| 5 | Encaissement terrain non impacté |

---

## 9. Déploiement

- Migration colonnes + attachments + pivot payments
- Storage disk `municipal_finance` (S3 ou local chiffré)
- Seed permissions receveur étendues

---

## 10. Audit sécurité

- [ ] PDF scannés antivirus (ClamAV optionnel)
- [ ] Accès attachments par rôle
- [ ] Montant reversement ≤ solde encaissements non reversés
- [ ] Double confirmation interdite

---

## 11. Documentation Trésor

Export mensuel CSV / PDF format convenu avec le Trésor Public (spec à valider avec représentant Trésor).
