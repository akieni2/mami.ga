# Sprint 4.4 — Budget municipal

**Prévisionnel, exécution et suivi budgétaire intégré au DAF Dashboard**

| | |
|---|---|
| **Prérequis** | Sprint 4.3 (recettes comptabilisées) |
| **Priorité** | P1 |

---

## 1. Objectif fonctionnel

Permettre au DAF et au Maire de :

- Saisir le **budget prévisionnel** par section et ligne
- Suivre le **budget exécuté** (recettes réelles + dépenses engagées)
- Calculer **taux d'exécution**, **disponible**, **dépassements**
- Comparer **N vs N-1**

---

## 2. Modèle de données

### 2.1 `budget_years`

| Colonne | Description |
|---------|-------------|
| `year` | Ex. 2026 |
| `label` | « Budget primitif 2026 » |
| `status` | `draft` · `voted` · `closed` |
| `voted_at` | Date vote conseil municipal |
| `total_revenue_forecast` | |
| `total_expense_forecast` | |

### 2.2 `budget_sections`

| Colonne | Description |
|---------|-------------|
| `budget_year_id` | |
| `code` | Ex. `CHAP-07` |
| `label` | Section / chapitre |
| `section_type` | `revenue` · `expense` |
| `sort_order` | |

### 2.3 `budget_lines`

| Colonne | Description |
|---------|-------------|
| `budget_section_id` | |
| `code` | Ligne budgétaire |
| `label` | |
| `forecast_amount_xaf` | Montant voté |
| `financial_account_id` | Lien compta (optionnel) |

### 2.4 `budget_revisions`

| Colonne | Description |
|---------|-------------|
| `budget_year_id` | |
| `revision_number` | 1, 2, … |
| `label` | Budget rectificatif |
| `approved_at` | |
| `delta_json` | Modifications par ligne |

### 2.5 Exécution (calculée)

Vue ou service `BudgetExecutionService` :

- **Réalisé recettes** : agrégation `journal_entry_lines` + mapping ligne budgétaire
- **Réalisé dépenses** : idem + prestataires (Sprint 4.6)
- **Disponible** = forecast − réalisé − engagements

---

## 3. API

Préfixe : `/api/municipality/finance/budget`

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/years` | Exercices budgétaires |
| POST | `/years` | Créer exercice |
| GET | `/years/{year}/summary` | Synthèse prévu / réalisé / dispo |
| GET | `/years/{year}/sections` | Arbre sections + lignes |
| POST | `/years/{year}/sections` | CRUD sections/lignes |
| PUT | `/lines/{id}` | Modifier forecast |
| POST | `/years/{year}/revisions` | Budget rectificatif |
| GET | `/years/{year}/compare/{previousYear}` | Comparatif annuel |
| GET | `/years/{year}/execution-rate` | Taux par section |

---

## 4. Dashboard DAF (enrichissement)

Ajouter au `DafDashboardService` :

| KPI | Source |
|-----|--------|
| Prévu recettes | `budget_lines` revenue |
| Réalisé recettes | compta / encaissements |
| Disponible | calcul |
| Dépassement | alerte si réalisé > forecast |
| Taux exécution global | % |

Flutter : cartes sur `DafDashboardScreen` + drill-down `/municipality/finance/budget`.

---

## 5. Flutter

| Écran | Route |
|-------|-------|
| **Budget overview** | `/municipality/finance/budget` |
| **Sections / lignes** | `/municipality/finance/budget/{year}` |
| **Comparatif** | `/municipality/finance/budget/compare` |

Graphiques : barres prévu vs réalisé (package `fl_chart` ou équivalent).

---

## 6. Tests PHP

- Création exercice + lignes
- Calcul exécution recettes = somme journal REC
- Révision met à jour forecast
- Taux exécution correct

---

## 7. Recette institutionnelle

| # | Vérification |
|---|--------------|
| 1 | Budget primitif saisi = document PDF conseil |
| 2 | Recettes terrain alimentent exécution |
| 3 | Alerte dépassement ligne PTA |
| 4 | Export PDF synthèse pour Maire |

---

## 8. Compatibilité

- Module budget isolé ; recouvrement terrain indépendant
- Exercice budget sans lignes = dashboard sans KPI budget (graceful)

---

## 9. Estimation

| Couche | Effort |
|--------|--------|
| Backend | 4–6 j |
| Flutter | 3–4 j |
| Tests + recette | 2 j |
