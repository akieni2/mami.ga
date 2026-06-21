# Sprint 4.5 — Ressources humaines

**Gestion des agents municipaux, grades, paie et congés**

| | |
|---|---|
| **Prérequis** | Sprint 4.0 (rôles, agents terrain identifiés via `users`) |
| **Priorité** | P2 |
| **Périmètre v1** | Owendo · agents municipaux et financiers |

---

## 1. Objectif fonctionnel

- Répertoire **employés** lié aux comptes MAMI (`users`)
- **Grades** et barèmes indiciaires
- **Paie** (bulletins simplifiés — interface, pas moteur paie complet)
- **Congés / absences** avec impact sur missions terrain

---

## 2. Modèle de données

### 2.1 `employees`

| Colonne | Description |
|---------|-------------|
| `user_id` | FK users (nullable si externe) |
| `matricule` | Ex. OWE-RH-000123 |
| `full_name` | |
| `grade_id` | FK |
| `service` | Recouvrement · DAF · État civil · … |
| `hire_date` | |
| `status` | `active` · `suspended` · `terminated` |
| `phone` / `email` | |

### 2.2 `grades`

| Colonne | Description |
|---------|-------------|
| `code` | |
| `label` | Agent terrain · Contrôleur · … |
| `base_salary_xaf` | Indiciaire de base |
| `category` | A · B · C |

### 2.3 `payrolls`

| Colonne | Description |
|---------|-------------|
| `employee_id` | |
| `period_month` | YYYY-MM |
| `gross_xaf` / `net_xaf` | |
| `status` | `draft` · `validated` · `paid` |
| `validated_by` | DAF / RH |

### 2.4 `leave_requests`

| Colonne | Description |
|---------|-------------|
| `employee_id` | |
| `leave_type` | `annual` · `sick` · `unpaid` · `mission` |
| `start_date` / `end_date` | |
| `status` | `pending` · `approved` · `rejected` |
| `approved_by` | |

**Lien missions :** si congé approuvé chevauche `valid_from`/`valid_until` mission → alerte supervision.

---

## 3. API

Préfixe : `/api/municipality/hr`

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/employees` | Liste effectifs |
| POST | `/employees` | Créer / lier user |
| GET | `/employees/{id}` | Fiche |
| GET | `/grades` | Référentiel grades |
| GET | `/payrolls` | Bulletins (filtre période) |
| POST | `/payrolls/generate` | Génération brouillon mensuel |
| GET | `/leave-requests` | Congés |
| POST | `/leave-requests` | Demande |
| POST | `/leave-requests/{id}/approve` | Validation supérieur |

Permissions : `municipal.hr.view`, `municipal.hr.manage`, `municipal.hr.payroll.validate`

---

## 4. Dashboard RH

| KPI | Description |
|-----|-------------|
| Effectifs actifs | Count employees active |
| Masse salariale mois | Sum net payroll |
| Congés en cours | Leave requests approved today |
| Absences non justifiées | (manuel v1) |

Flutter : `/municipality/hr` (hub) — accessible SG / DAF / RH.

---

## 5. Flutter

| Écran | Route |
|-------|-------|
| **RH Dashboard** | `/municipality/hr` |
| **Employés** | `/municipality/hr/employees` |
| **Congés** | `/municipality/hr/leave` |
| **Paie** | `/municipality/hr/payroll` |

---

## 6. Tests PHP

- CRUD employee + lien user
- Congé approuvé bloque alerte mission (notification)
- Payroll generate idempotent par mois

---

## 7. Recette

| # | Vérification |
|---|--------------|
| 1 | Agent terrain = fiche employee + user |
| 2 | Demande congé workflow approve |
| 3 | Dashboard effectifs cohérent |
| 4 | App agent recouvrement inchangée |

---

## 8. Compatibilité

- Module HR **additif** ; pas de modification table `users` destructive
- Agents sans fiche RH continuent de fonctionner

---

## 9. Estimation

| Couche | Effort |
|--------|--------|
| Backend | 4–5 j |
| Flutter | 3 j |
| Tests + recette | 2 j |
