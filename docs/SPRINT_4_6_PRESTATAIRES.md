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
