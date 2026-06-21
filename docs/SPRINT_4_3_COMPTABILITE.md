# Sprint 4.3 — Comptabilité municipale

**Journal comptable, grand livre et balance — alimentation automatique depuis le recouvrement**

| | |
|---|---|
| **Prérequis** | Sprint 4.2 (reversements confirmés) |
| **Norme cible** | SYSCOHADA révisé (plan simplifié commune) |
| **Priorité** | P1 |

---

## 1. Objectif fonctionnel

Centraliser les **écritures comptables** générées par :

- Encaissements terrain (`municipal_payments`)
- Clôtures de caisse (`cash_sessions`)
- Reversements Trésor (`municipal_treasury_remittances` confirmed)
- (Futur 4.6) Paiements prestataires

Produire **journal**, **grand livre** et **balance** pour le DAF et le Trésor.

---

## 2. Modèle de données

### 2.1 `financial_accounts` (plan comptable)

| Colonne | Description |
|---------|-------------|
| `code` | Ex. `5311`, `7011`, `5121` |
| `label` | Libellé compte |
| `account_class` | 5 Caisse · 6 Charges · 7 Produits · … |
| `account_type` | `asset` · `liability` · `revenue` · `expense` |
| `is_active` | |
| `parent_code` | Hiérarchie |

### 2.2 `journal_entries` (écritures)

| Colonne | Description |
|---------|-------------|
| `reference` | OWE-JE-YYYY-XXXXXX |
| `entry_date` | Date comptable |
| `journal_code` | `REC` Recettes · `CAI` Caisse · `BAN` Banque · `TRE` Trésor |
| `description` | |
| `source_type` / `source_id` | Polymorphe (payment, remittance, …) |
| `status` | `draft` · `posted` · `cancelled` |
| `created_by` | |

### 2.3 `journal_entry_lines`

| Colonne | Description |
|---------|-------------|
| `journal_entry_id` | |
| `financial_account_id` | |
| `debit` / `credit` | decimal(14,2) — un seul non nul |
| `label` | |

### 2.4 `general_ledger` (vue matérialisée ou table dénormalisée)

| Colonne | Description |
|---------|-------------|
| `financial_account_id` | |
| `entry_date` | |
| `journal_entry_id` | |
| `debit` / `credit` | |
| `running_balance` | Solde progressif par compte |

*Alternative : grand livre calculé à la volée depuis `journal_entry_lines` pour v1.*

---

## 3. Écritures automatiques

| Événement source | Débit | Crédit |
|------------------|-------|--------|
| Encaissement espèces | 531 Caisse | 701 Recettes fiscales |
| Clôture caisse (écart) | 658 / 758 | 531 |
| Reversement confirmé | 512 Banque | 531 Caisse |
| (4.6) Paiement fournisseur | 601 / 602 | 512 |

Service : `AutomaticJournalEntryService` écoute événements domaine (`PaymentCompleted`, `TreasuryRemittanceConfirmed`).

**Idempotence :** clé `(source_type, source_id, journal_code)` unique.

---

## 4. API

Préfixe : `/api/municipality/finance/accounting`

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/accounts` | Plan comptable |
| POST | `/accounts` | Créer compte (DAF) |
| GET | `/journal` | Journal paginé (filtres date, code) |
| GET | `/journal/{id}` | Détail écriture + lignes |
| POST | `/journal/manual` | Écriture manuelle (DAF, contrôle) |
| POST | `/journal/{id}/post` | Comptabiliser brouillon |
| POST | `/journal/{id}/cancel` | Contrepassation |
| GET | `/ledger` | Grand livre (compte, période) |
| GET | `/balance` | Balance générale (date) |
| GET | `/balance/trial` | Balance auxiliaire |

Permissions : `municipal.finance.accounting.view`, `municipal.finance.accounting.manage`

---

## 5. Flutter

| Écran | Route |
|-------|-------|
| **Comptabilité (hub)** | `/municipality/finance/accounting` |
| **Journal** | `/municipality/finance/accounting/journal` |
| **Grand Livre** | `/municipality/finance/accounting/ledger` |
| **Balance** | `/municipality/finance/accounting/balance` |

Filtres : exercice, mois, compte, journal.

---

## 6. Tests PHP

- Équilibre débit = crédit par écriture
- Encaissement → écriture auto REC
- Reversement confirmed → écriture TRE
- Annulation génère contrepassation
- Grand livre cohérent avec balance

---

## 7. Recette DAF

| # | Vérification |
|---|--------------|
| 1 | Encaissement terrain visible au journal J+0 |
| 2 | Balance recettes = total encaissements période |
| 3 | Export CSV balance |
| 4 | Recouvrement terrain inchangé |

---

## 8. Déploiement

- Migration 4 tables + seeder plan comptable minimal Owendo
- Job async `PostJournalEntryJob` si volume élevé
- Index `(entry_date, journal_code)`, `(financial_account_id, entry_date)`

---

## 9. Compatibilité

- Comptabilité **opt-in** : `MAMI_FINANCE_ACCOUNTING_AUTO_POST=false` par défaut
- Aucun impact encaissement si auto-post désactivé

---

## 10. Estimation

| Couche | Effort |
|--------|--------|
| Backend + events | 5–8 j |
| Flutter | 3–4 j |
| Tests + recette | 3 j |
