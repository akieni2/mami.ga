# Sprint 4.6 — Gestion des prestataires

**Circuit complet : bon de commande → service fait → visa DAF → ordonnancement → paiement**

| | |
|---|---|
| **Prérequis** | Sprint 4.3 (comptabilité) · Sprint 4.4 (budget) |
| **Priorité** | P2 |

---

## 1. Objectif fonctionnel

Digitaliser la **dépense publique locale** :

1. Répertoire **fournisseurs**
2. **Bon de commande** (BC)
3. **Certificat de service fait**
4. **Visa DAF** + contrôle budgétaire
5. **Ordonnancement** et **paiement**

Intégration comptable automatique (charges + dettes fournisseurs).

---

## 2. Workflow

```
Bon de commande (draft)
        │ submit
        ▼
   BC validated (DAF / SG selon seuil)
        │
        ▼
   Service fait (certificat)
        │
        ▼
   Visa DAF (contrôle budget + compta)
        │
        ▼
   Ordonnancement
        │
        ▼
   Paiement (virement / chèque / caisse)
        │
        ▼
   Écriture comptable auto
```

---

## 3. Modèle de données

### 3.1 `suppliers`

| Colonne | Description |
|---------|-------------|
| `reference` | OWE-SUP-XXXXXX |
| `legal_name` | Raison sociale |
| `nif` | Numéro identification fiscale |
| `bank_name` / `bank_account` | |
| `contact_phone` / `email` | |
| `status` | `active` · `suspended` |

### 3.2 `purchase_orders`

| Colonne | Description |
|---------|-------------|
| `reference` | OWE-PO-YYYY-XXXXXX |
| `supplier_id` | |
| `budget_line_id` | Lien budget (4.4) |
| `amount_xaf` | |
| `description` | |
| `status` | `draft` · `submitted` · `approved` · `cancelled` |
| `approved_by` / `approved_at` | |

### 3.3 `service_certificates`

| Colonne | Description |
|---------|-------------|
| `purchase_order_id` | |
| `delivered_at` | |
| `certificate_ref` | |
| `notes` | |
| `certified_by` | Agent / service demandeur |

### 3.4 `supplier_invoices`

| Colonne | Description |
|---------|-------------|
| `purchase_order_id` | |
| `invoice_number` | |
| `invoice_date` | |
| `amount_xaf` | |
| `status` | `pending` · `daf_validated` · `ordered` · `paid` |

### 3.5 `supplier_payments`

| Colonne | Description |
|---------|-------------|
| `supplier_invoice_id` | |
| `payment_method` | virement · chèque · caisse |
| `payment_reference` | |
| `paid_at` | |
| `amount_xaf` | |
| `status` | `pending` · `executed` |

---

## 4. API

Préfixe : `/api/municipality/finance/procurement`

| Domaine | Routes |
|---------|--------|
| Fournisseurs | `GET/POST /suppliers`, `GET/PUT /suppliers/{id}` |
| BC | `GET/POST /purchase-orders`, `POST /{id}/submit`, `POST /{id}/approve` |
| Service fait | `POST /purchase-orders/{id}/service-certificate` |
| Factures | `POST /invoices`, `POST /invoices/{id}/validate-daf` |
| Paiements | `POST /payments`, `POST /payments/{id}/execute` |

Permissions : `municipal.finance.procurement.view`, `.manage`, `.approve`

---

## 5. Contrôles métier

| Contrôle | Règle |
|----------|-------|
| Budget | `amount_xaf` BC ≤ disponible ligne budgétaire |
| Double paiement | Une facture = un paiement executed |
| Seuil BC | > X XAF → visa SG (config) |
| Compta | Paiement executed → écriture 401 / 512 |

---

## 6. Flutter

| Écran | Route |
|-------|-------|
| **Fournisseurs** | `/municipality/finance/suppliers` |
| **Bons de commande** | `/municipality/finance/purchase-orders` |
| **Factures & paiements** | `/municipality/finance/invoices` |
| **Validation DAF** | File commune avec 4.1 pattern |

---

## 7. Tests PHP

- BC approuvé consomme budget disponible
- Service fait requis avant facture
- Paiement → journal entry expense
- Rejet BC restitue budget

---

## 8. Recette

| # | Vérification |
|---|--------------|
| 1 | BC fournisseur travaux publics |
| 2 | Service fait + facture |
| 3 | Visa DAF + ordonnancement |
| 4 | Balance charges mise à jour |
| 5 | Recouvrement terrain non impacté |

---

## 9. Compatibilité

- Module procurement isolé sous prefix `/finance/procurement`
- Aucune modification tables recouvrement

---

## 10. Audit sécurité

- [ ] Séparation BC creator / approver / payer
- [ ] Pièces jointes factures scannées
- [ ] Journal immuable
- [ ] Export contrôle de gestion pour Trésor

---

## 11. Estimation

| Couche | Effort |
|--------|--------|
| Backend | 6–10 j |
| Flutter | 4–5 j |
| Tests + recette | 3 j |

---

## 12. Vision long terme

Intégration **marchés municipaux** (étals, redevances) via lien `suppliers` ↔ opérateurs économiques recensés (Sprint futur).

---

## 13. Sprint 4.6.x — Conformité administrative des fournisseurs et opérateurs économiques

> **Statut : documenté uniquement — NON implémenté**  
> **Périmètre :** spécification future · aucun code · aucune migration · aucune table en production  
> **Activation prévue :** après Sprint 4.6 core + prérequis Sprint 4.9 (Marchés Publics) partiels

### 13.1 Objectif

Garantir que **tout tiers payé par la commune** (fournisseur prestataire) et **tout opérateur économique recensé** (commerce terrain) dispose de pièces administratives **à jour**, **historisées** et **contrôlables** avant :

- ordonnancement / paiement fournisseur ;
- attribution de marché ou bon de commande ;
- renouvellement d'autorisation d'occupation ;
- contrôle terrain renforcé.

Ce sous-module complète le circuit procurement (§2) sans le remplacer : la conformité administrative est un **garde-fou transversal**.

### 13.2 Périmètre fonctionnel

| Domaine | Description |
|---------|-------------|
| **Fournisseurs** (`suppliers`) | Dossier administratif lié au répertoire prestataires Sprint 4.6 |
| **Opérateurs économiques** (`economic_operators`) | Dossier administratif lié au recensement Sprint 3.2 |
| **Historisation** | Versions successives des pièces, jamais écrasées |
| **Expiration** | Alertes et statuts `valid` · `expiring_soon` · `expired` · `missing` |
| **Contrôle pré-paiement** | Blocage ou alerte avant `supplier_payments.execute` |
| **Intégrations futures** | Marchés Publics 4.9 · Contrôles terrain 3.4 · Audit interne |

### 13.3 Types de documents prévus

| Code | Libellé | Obligatoire fournisseur | Obligatoire opérateur | Validité typique |
|------|---------|-------------------------|----------------------|------------------|
| `CNSS` | Attestation de paiement CNSS | ✅ | ✅ (si employeur) | 3 mois |
| `CNAMGS` | Attestation CNAMGS | ✅ | ✅ (si employeur) | 3 mois |
| `TAX_CERT` | Attestation fiscale / quitus | ✅ | ✅ | 6–12 mois |
| `RCCM` | Registre du Commerce | ✅ | ✅ | Annuelle |
| `NIF` | Numéro identification fiscale | ✅ | ✅ | Permanente (vérif. annuelle) |
| `INSURANCE_PRO` | Assurance professionnelle | Selon contrat | Selon activité | Annuelle |
| `OTHER` | Autres pièces (agrément, licence…) | Configurable | Configurable | Variable |

Référentiel extensible via table `administrative_document_types` (future).

### 13.4 Modèles de données envisagés (spécification — non créés)

#### `administrative_document_types`

| Colonne | Description |
|---------|-------------|
| `code` | CNSS, RCCM, … |
| `label` | Libellé affiché |
| `applies_to` | `supplier` · `economic_operator` · `both` |
| `is_mandatory` | bool |
| `default_validity_days` | nullable |
| `blocks_payment` | bool — bloque paiement si expiré |
| `blocks_contract` | bool — bloque marché / BC si expiré |

#### `administrative_documents`

| Colonne | Description |
|---------|-------------|
| `id` | |
| `documentable_type` | `supplier` · `economic_operator` |
| `documentable_id` | FK polymorphe |
| `document_type_id` | FK types |
| `reference_number` | N° attestation / RCCM |
| `issued_at` | Date émission |
| `expires_at` | Date expiration |
| `status` | `draft` · `submitted` · `validated` · `rejected` · `superseded` |
| `file_path` | Pièce scannée (PDF) |
| `file_hash` | SHA-256 intégrité |
| `validated_by` | FK users (DAF / contrôleur) |
| `validated_at` | |
| `rejection_reason` | |
| `superseded_by_id` | Lien version suivante |
| `version` | 1, 2, 3… |

#### `administrative_compliance_snapshots`

| Colonne | Description |
|---------|-------------|
| `id` | |
| `subject_type` / `subject_id` | Fournisseur ou opérateur |
| `checked_at` | |
| `overall_status` | `compliant` · `non_compliant` · `partial` |
| `missing_documents` | JSON liste codes |
| `expired_documents` | JSON liste codes |
| `triggered_by` | `payment` · `field_control` · `market_award` · `manual` |
| `trigger_reference` | ID paiement, visite, marché… |

#### `administrative_compliance_rules` (config métier)

| Colonne | Description |
|---------|-------------|
| `context` | `before_payment` · `before_purchase_order` · `field_control` |
| `document_type_id` | |
| `enforcement` | `block` · `warn` · `audit_only` |
| `min_amount_xaf` | Seuil BC / paiement |

**Lien existant :** `economic_operators.nif` et `suppliers.nif` deviennent champs de référence croisée, pas de duplication destructive.

### 13.5 Règles métier futures

| # | Règle | Contexte |
|---|-------|----------|
| R1 | Paiement fournisseur `executed` interdit si `overall_status != compliant` et règle `blocks_payment` | Ordonnancement |
| R2 | Nouvelle version document → ancienne passe `superseded`, historique conservé | Historisation |
| R3 | Document `expires_at` < aujourd'hui → statut calculé `expired` | Batch quotidien |
| R4 | Alerte J-30 / J-7 avant expiration → notification DAF + tiers | Exploitation |
| R5 | Opérateur sans RCCM valide → alerte contrôle terrain, pas blocage encaissement v1 | Non-régression terrain |
| R6 | Fournisseur suspendu (`suppliers.status=suspended`) → tous paiements bloqués | Procurement |
| R7 | Validation document ≠ uploader (ségrégation) | Contrôle interne |
| R8 | Pièce rejetée → motif obligatoire ≥ 10 caractères | Aligné workflow 4.1 |

**Feature flag envisagé :** `MAMI_ADMIN_COMPLIANCE_ENFORCE=false` (défaut) — alertes seules jusqu'à recette institutionnelle.

### 13.6 API futures (spécification — non exposées)

Préfixe envisagé : `/api/municipality/compliance`

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/document-types` | Référentiel pièces |
| GET | `/suppliers/{id}/documents` | Dossier fournisseur |
| GET | `/operators/{id}/documents` | Dossier opérateur |
| POST | `/documents` | Dépôt pièce (upload PDF) |
| POST | `/documents/{id}/validate` | Validation DAF / contrôleur |
| POST | `/documents/{id}/reject` | Rejet motivé |
| GET | `/suppliers/{id}/compliance-status` | Synthèse conformité |
| GET | `/operators/{id}/compliance-status` | Synthèse conformité |
| GET | `/alerts/expiring` | Pièces à échéance |
| POST | `/check-before-payment` | Pré-contrôle paiement (interne procurement) |

Permissions envisagées : `municipal.compliance.view`, `.manage`, `.validate`.

### 13.7 Écrans Flutter futurs (spécification — non développés)

| Écran | Route envisagée | Rôles |
|-------|-----------------|-------|
| **Dossier conformité fournisseur** | `/municipality/finance/suppliers/{id}/compliance` | DAF, adjoint, contrôleur |
| **Dossier conformité opérateur** | `/municipality/operators/{id}/compliance` | Agent, superviseur, DAF |
| **File pièces à valider** | `/municipality/compliance/queue` | DAF, contrôleur |
| **Alertes expiration** | `/municipality/compliance/expiring` | DAF, SG |
| **Historique versions** | nested / documents/{id}/history | Audit |

Intégration UI procurement : badge **Conforme / Non conforme** sur fiche fournisseur et avant bouton « Exécuter paiement ».

Intégration UI terrain : indicateur conformité sur fiche opérateur post-scan QR (lecture seule v1).

### 13.8 Règles d'audit futures

| Événement journal | Déclencheur |
|-------------------|-------------|
| `compliance.document.uploaded` | Dépôt pièce |
| `compliance.document.validated` | Validation |
| `compliance.document.rejected` | Rejet |
| `compliance.document.superseded` | Nouvelle version |
| `compliance.check.passed` | Pré-paiement OK |
| `compliance.check.blocked` | Paiement refusé |
| `compliance.status.changed` | Changement global compliant → non_compliant |

Journal : réutilisation `municipal_finance_journal_entries` + `FiscalAuditService` pour cohérence Sprint 4.0.

**Immutabilité :** aucune suppression physique de `administrative_documents` — soft delete interdit pour pièces validées.

### 13.9 Intégrations futures

```
                    ┌─────────────────────┐
                    │  4.6 Prestataires   │
                    │  (paiement)         │
                    └──────────┬──────────┘
                               │ check R1
                    ┌──────────▼──────────┐
                    │  4.6.x Conformité   │
                    │  administrative     │
                    └──────────┬──────────┘
           ┌───────────────────┼───────────────────┐
           ▼                   ▼                   ▼
   ┌───────────────┐  ┌───────────────┐  ┌───────────────┐
   │ 4.9 Marchés   │  │ 3.4 Contrôles │  │ Audit interne │
   │ Publics       │  │ terrain       │  │ (DAF/SG)      │
   └───────────────┘  └───────────────┘  └───────────────┘
```

| Module | Intégration |
|--------|-------------|
| **4.9 Marchés Publics** | Conformité obligatoire avant attribution marché / avenant |
| **3.4 Contrôles terrain** | Affichage statut conformité opérateur lors visite / scan QR |
| **Audit interne** | Export dossiers, snapshots, piste complète pour commission |
| **4.3 Comptabilité** | Aucune écriture si paiement bloqué par conformité |
| **CNSS / CNAMGS** (externe) | V2 : vérification API ou import batch — hors périmètre 4.6.x |

### 13.10 Non-régression (obligatoire à l'implémentation future)

- Encaissement terrain **inchangé** tant que `MAMI_ADMIN_COMPLIANCE_ENFORCE=false`
- Scan QR et situation fiscale **non bloqués** par défaut pour opérateurs
- Module 4.6.x **additif** — tables et routes isolées

### 13.11 Estimation indicative (implémentation future)

| Couche | Effort |
|--------|--------|
| Backend + stockage fichiers | 5–8 j |
| Flutter | 3–4 j |
| Tests + recette institutionnelle | 3 j |
| **Total 4.6.x** | **11–15 j** (après 4.6 core) |

---

*Sous-module 4.6.x — spécification documentaire uniquement. Aucun livrable code associé à ce stade.*
