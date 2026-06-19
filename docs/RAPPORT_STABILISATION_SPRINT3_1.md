# Rapport de stabilisation — Sprint 3.1

**Version :** 1.0  
**Date :** juin 2026  
**Objectif :** Sprint 3 opérationnel avant recette terrain (hors P2–P5)

---

## Synthèse

| Étape | Statut | Action réalisée |
|-------|--------|-----------------|
| Tables fiscalité | ✅ Vérifiées (local) | Migration `2026_06_23_100000` appliquée |
| Création données fiscal | ✅ Backend OK | Service + routes POST fonctionnels |
| Affichage erreurs fiscal | ✅ Corrigé | Layout + champs `@error` |
| Rôle `taxi_driver` à l'approbation | ✅ Corrigé | `DriverEnrollmentService::approve()` |
| Admin agents municipaux | ✅ Implémenté | `/admin/users` + création agent |
| Tests automatisés | ⚠️ Écrits | MySQL test `mami_ga_testing` requis en CI |

---

## Étape 1 — Fiscalité

### 1.1 Tables (migration `2026_06_23_100000`)

| Table | Statut local | Migration |
|-------|--------------|-----------|
| `municipal_tax_types` | ✅ OK | Batch 6 |
| `municipal_tax_rates` | ✅ OK | Batch 6 |
| `municipal_collection_targets` | ✅ OK | Batch 6 |
| `operator_tax_assignments` | ✅ OK | Batch 6 |
| `fiscal_obligations` | ✅ OK | Batch 6 |

**Vérification VPS :**

```bash
php scripts/check_fiscal_tables.php
php artisan migrate:status | grep 2026_06_23
```

### 1.2 Formulaires — diagnostic par type d'erreur

| Type | Cause | Symptôme avant correctif | Après correctif |
|------|-------|--------------------------|-----------------|
| **Validation Laravel** | Code invalide (`taxe commerce`), champs requis | Page recharge, 0 ligne en base | Liste rouge + `@error` par champ |
| **Validation métier** | Code doublon (`assertCodeAvailable`) | Idem | Message « Ce code de taxe existe déjà » |
| **SQL** | Migration absente sur VPS | Erreur 500 | Logs Laravel — appliquer migrations |
| **Permissions** | `MAMI_MODULE_MUNICIPALITY=false` | 403 GET/POST | Activer module + `config:cache` |
| **CSRF** | Session/domaine `admin.mami.ga` | 419 Page Expired | Vérifier `APP_URL`, cookies, HTTPS |
| **Routage** | `route:cache` obsolète | 404/405 | `php artisan route:clear` |

### 1.3 Routes POST vérifiées

| Route nommée | Méthode | URI |
|--------------|---------|-----|
| `admin.municipality.fiscal.tax-types.store` | POST | `admin/municipality/fiscal/tax-types` |
| `admin.municipality.fiscal.rates.store` | POST | `admin/municipality/fiscal/rates` |
| `admin.municipality.fiscal.targets.store` | POST | `admin/municipality/fiscal/targets` |
| `admin.municipality.fiscal.assignments.store` | POST | `admin/municipality/fiscal/assignments` |
| `admin.municipality.fiscal.obligations.generate` | POST | `admin/municipality/fiscal/obligations/generate` |

**Middleware :** `auth` + `admin` + `module:municipality`

### 1.4 Correctifs UX appliqués

- `resources/views/admin/partials/alerts.blade.php` — erreurs globales
- `resources/views/layouts/admin.blade.php` — inclusion alerts
- Toutes les vues `admin/municipality/fiscal/*.blade.php` — `@error` + `old()` + aides contextuelles
- Message enrichi si génération obligations = 0

### 1.5 Procédure de test manuel (recette)

1. Connexion `https://admin.mami.ga` (admin)
2. Fiscalité → Types : créer `TAX-PILOTE` + nom lisible
3. Taux : associer un montant et une période
4. Affectations : choisir un opérateur économique existant
5. Obligations : générer — vérifier compteur > 0

---

## Étape 2 — Utilisateurs

### 2.1 Correctif chauffeur taxi

`DriverEnrollmentService::approve()` attribue désormais le rôle `taxi_driver` via `syncWithoutDetaching` avec `assigned_by`.

### 2.2 Administration utilisateurs

| Fonction | URL | Description |
|----------|-----|-------------|
| Liste utilisateurs | `GET /admin/users` | Filtre nom/email/rôle |
| Fiche utilisateur | `GET /admin/users/{id}` | Rôles + ajout/retrait |
| Créer agent municipal | `GET /admin/users/agents/create` | Formulaire |
| Enregistrer agent | `POST /admin/users/agents` | User + `municipal_agent` auto |

**Service :** `MunicipalAgentProvisioningService`

### 2.3 Procédure APK terrain

1. Admin → Utilisateurs → **Créer un agent municipal**
2. Saisir email/mot de passe → noter les identifiants
3. Vérifier fiche : rôle `Agent Municipal` présent
4. Compiler APK client/agent avec API production
5. Connexion agent → hub Recouvrement Owendo
6. Exécuter checklist terrain Sprint 3 existante

### 2.4 Chauffeur taxi (recette)

1. Candidature via app chauffeur
2. Admin → Candidatures → Approuver
3. Vérifier fiche utilisateur : rôle `Chauffeur Taxi`
4. Chauffeur peut passer en ligne sur `mami_driver`

---

## Fichiers modifiés / ajoutés

| Fichier | Type |
|---------|------|
| `resources/views/layouts/admin.blade.php` | Erreurs globales |
| `resources/views/admin/partials/alerts.blade.php` | Nouveau |
| `resources/views/admin/partials/field-error.blade.php` | Nouveau |
| `resources/views/admin/municipality/fiscal/*.blade.php` | Erreurs champs |
| `app/Services/DriverEnrollmentService.php` | Rôle taxi |
| `app/Services/MunicipalAgentProvisioningService.php` | Nouveau |
| `app/Http/Controllers/Admin/UserAdminController.php` | Nouveau |
| `resources/views/admin/users/*.blade.php` | Nouveau |
| `routes/web.php` | Routes users |
| `resources/views/admin/partials/sidebar.blade.php` | Lien Utilisateurs |
| `scripts/check_fiscal_tables.php` | Vérif ops |
| `scripts/verify_fiscal_stabilization.php` | Vérif routes + service |
| `tests/Feature/Municipality/FiscalAdminWebTest.php` | Tests web |
| `tests/Feature/Admin/UserAdminTest.php` | Tests admin users |

---

## Checklist go-live recette terrain

### Ops VPS

- [ ] `MAMI_MODULE_MUNICIPALITY=true`
- [ ] Migrations batch 6+ appliquées
- [ ] `php artisan config:cache` après `.env`
- [ ] `php scripts/check_fiscal_tables.php` → 5× OK

### Fiscalité

- [ ] Création type taxe avec code valide visible en liste
- [ ] Code invalide affiche erreur rouge
- [ ] Chaîne taux → affectation → obligations complète

### Utilisateurs

- [ ] Agent municipal créé via admin (sans Tinker)
- [ ] Connexion agent sur APK terrain OK
- [ ] Chauffeur approuvé avec rôle `taxi_driver`

### Recette

- [ ] Encaissement bout-en-bout
- [ ] Quittance PDF + vérification publique
- [ ] Signature recette mairie

---

*Sprint 3.1 — stabilisation livrée. P2–P5 hors scope.*
