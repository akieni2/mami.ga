# MAMI Municipality V3 — Sprint 1 Report

**Sprint** : Fiscal Engine Core  
**Date** : juin 2026  
**Branche** : `feature/mami-taxi-v2-p2`  
**Statut** : ✅ Livré

---

## Objectif

Construire le **moteur fiscal configurable** d'Owendo : taxes, taux, objectifs, affectations et obligations — **sans** paiement, quittance ni Mobile Money.

---

## Livrables

| Livrable | Statut |
|----------|--------|
| Migrations (5 tables) | ✅ |
| Modèles Eloquent (5) | ✅ |
| Enums (`BillingPeriod`, `FiscalObligationStatus`) | ✅ |
| Services (6 + audit + références) | ✅ |
| Commande `municipality:fiscal-generate` | ✅ |
| API REST `/api/municipality/fiscal/*` | ✅ |
| Backoffice admin Blade (5 écrans) | ✅ |
| Permissions Spatie | ✅ |
| Audit `audit_logs` | ✅ |
| Tests Feature (33 nouveaux) | ✅ |

---

## Tables créées

Migration : `database/migrations/2026_06_23_100000_create_municipality_fiscal_engine_tables.php`

| Table | Rôle |
|-------|------|
| `municipal_tax_types` | Catalogue taxes (`TAX-COMMERCE`, etc.) |
| `municipal_tax_rates` | Montant, périodicité, validité (historique) |
| `municipal_collection_targets` | Objectifs annuels par taxe |
| `operator_tax_assignments` | N taxes / opérateur |
| `fiscal_obligations` | Dettes générées (`OWE-FO-YYYY-NNNNNN`) |

**Principes respectés** :
- Aucun montant codé en dur
- Pas de suppression physique (désactivation)
- Nouveau taux = nouvelle ligne (ancien taux désactivé)

---

## Services

| Service | Responsabilité |
|---------|----------------|
| `TaxTypeService` | CRUD types, activate/deactivate |
| `TaxRateService` | CRUD taux, versioning, résolution taux actif |
| `TargetService` | Objectifs annuels (upsert) |
| `FiscalAssignmentService` | Affectation opérateur ↔ taxe |
| `FiscalObligationGeneratorService` | Génération idempotente obligations |
| `FiscalAuditService` | Écriture `audit_logs` |
| `BillingPeriodResolver` | Périodes mensuel / trimestriel / semestriel / annuel |
| `FiscalObligationReferenceGenerator` | Numéros `OWE-FO-*` |

---

## API REST

Préfixe : `/api/municipality/fiscal` (auth Sanctum + `module:municipality`)

| Ressource | Endpoints |
|-----------|-----------|
| Taxes | `GET/POST /taxes`, `GET/PUT /taxes/{id}`, `POST .../activate\|deactivate` |
| Taux | `GET/POST /rates`, `GET /rates/{id}`, `POST .../deactivate` |
| Objectifs | `GET/POST /targets`, `GET /targets/{id}` |
| Affectations | `GET/POST /assignments`, `GET /assignments/{id}`, `POST .../activate\|deactivate` |
| Obligations | `GET /obligations`, `POST /obligations/generate`, `GET /obligations/{id}`, `POST .../cancel` |

---

## Backoffice admin

Menu **Fiscalité Owendo** → `/admin/municipality/fiscal/*`

| Écran | Route |
|-------|-------|
| Types de taxes | `admin.municipality.fiscal.tax-types` |
| Taux | `admin.municipality.fiscal.rates` |
| Objectifs annuels | `admin.municipality.fiscal.targets` |
| Affectations | `admin.municipality.fiscal.assignments` |
| Obligations | `admin.municipality.fiscal.obligations` |

---

## Permissions ajoutées

| Slug | Rôles |
|------|-------|
| `municipal.tax.view` | Admin |
| `municipal.tax.manage` | Admin |
| `municipal.tax.assign` | Admin |

---

## Commande Artisan

```bash
php artisan municipality:fiscal-generate
php artisan municipality:fiscal-generate --date=2026-06-01
```

Génère les obligations de la période courante pour chaque affectation active avec taux valide. **Idempotent.**

---

## Tests

| Fichier | Tests |
|---------|-------|
| `FiscalTaxTypeTest` | 7 |
| `FiscalRateTest` | 7 |
| `FiscalTargetTest` | 5 |
| `FiscalAssignmentTest` | 6 |
| `FiscalObligationGenerationTest` | 8 |
| **Total nouveaux** | **33** |

```bash
php artisan test tests/Feature/Municipality
# 70 passed (37 existants + 33 sprint 1)
```

---

## Hors périmètre (confirmé)

- ❌ `municipal_payments` / encaissement
- ❌ `municipal_receipts` / quittances
- ❌ Mobile Money
- ❌ Sessions de caisse
- ❌ Module Taxi (aucune modification)

---

## Déploiement VPS

```bash
git pull origin feature/mami-taxi-v2-p2
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache
```

Puis saisie dashboard : types de taxes → taux → objectifs → affectations → `php artisan municipality:fiscal-generate`

---

## Prochain sprint (V3.0 suite)

1. `FiscalCollectionService` — encaissement espèces
2. `CashSession` + impression thermique BT
3. Lien Core `payments` / `transactions`
4. Sync offline agent
