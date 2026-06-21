# Rapport correctif — `operator_id` nullable sur `field_visits` (Sprint 3.2.5)

**Prérequis :** diagnostic validé dans `docs/RAPPORT_AUDIT_OUVERTURE_CAISSE_OPERATOR_ID.md`  
**Statut :** patch **proposé** — non appliqué en attente de validation

---

## 1. Audit table `field_visits`

### Migration initiale (`2026_06_21_100000_create_municipality_v25_foundation_tables.php`)

```php
$table->foreignId('operator_id')->nullable()->constrained('economic_operators')->restrictOnDelete();
$table->foreignId('agent_id')->constrained('users')->restrictOnDelete();
$table->string('visit_type', 30);
$table->date('visit_date');
// latitude, longitude, notes : nullable
```

Index : `(operator_id, visit_date)`, `(agent_id, visit_date)`.

### Migration FK restrict (`2026_06_21_110000_restrict_operator_dependent_foreign_keys.php`)

Remplace `cascadeOnDelete` par `restrictOnDelete` sur `field_visits.operator_id`. **Ne touche pas** à la nullabilité dans le code source.

### Migration Sprint 2 caisse (`2026_06_24_100000_create_municipality_v3_sprint2_cash_collection.php`)

Ajoute sur `field_visits` :

- `cash_session_id` → nullable, FK `cash_sessions`, `nullOnDelete`
- `municipal_payment_id` → nullable, FK `municipal_payments`, `nullOnDelete`

### Modèle `FieldVisit`

- `operator_id` dans `$fillable`
- Relation `operator()` → `BelongsTo(EconomicOperator::class)`
- Aucune validation applicative imposant `operator_id` non null

### Contrainte SQL en cause (production)

```text
SQLSTATE[23000]: 1048 Column 'operator_id' cannot be null
```

→ La colonne est **NOT NULL** sur l'instance MySQL terrain, contrairement au schéma attendu du dépôt.

---

## 2. Usages de `VisitType`

| Valeur enum | Label | `operator_id` attendu | Service / créateur |
|-------------|-------|----------------------|-------------------|
| `session_open` | Ouverture caisse | **NULL** | `CashSessionService::open()` |
| `session_close` | Fermeture caisse | **NULL** | `CashSessionService::close()` |
| `payment` | Encaissement | Commerce | `FiscalCollectionService::collectCash()` |
| `scan` | Scan QR | Commerce | `OperatorFiscalSummaryService::recordScan()` |
| `consultation` | Consultation fiscale | Commerce | `OperatorFiscalSummaryService::build()` |
| `inspection`, `verification`, `collection`, `awareness` | Visites classiques | Commerce | `FieldVisitService::record()` |

Aucun type « session » ne doit être rattaché à un commerce.

---

## 3. `operator_id` doit-il être nullable ?

**Oui**, pour le modèle métier actuel :

- `SessionOpen` / `SessionClose` = événements **agent + session**, pas commerce.
- Les visites commerce conservent `operator_id` obligatoire **métier** (toujours renseigné par les services concernés).
- La nullabilité SQL permet les deux cas sans contournement (faux commerce, omission du `FieldVisit`, etc.).

**Alternative écartée :** table séparée `cash_session_events` — plus lourd, duplication du journal terrain, sans gain pour le Sprint 3.2.5.

---

## 4. Correctif minimal proposé

### 4.1 Migration corrective (à créer)

Fichier suggéré : `database/migrations/2026_06_16_140000_make_field_visits_operator_id_nullable.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_visits', function (Blueprint $table): void {
            $table->dropForeign(['operator_id']);
        });

        Schema::table('field_visits', function (Blueprint $table): void {
            $table->unsignedBigInteger('operator_id')->nullable()->change();
            $table->foreign('operator_id')
                ->references('id')
                ->on('economic_operators')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('field_visits', function (Blueprint $table): void {
            $table->dropForeign(['operator_id']);
        });

        Schema::table('field_visits', function (Blueprint $table): void {
            $table->unsignedBigInteger('operator_id')->nullable(false)->change();
            $table->foreign('operator_id')
                ->references('id')
                ->on('economic_operators')
                ->restrictOnDelete();
        });
    }
};
```

**Prérequis :** `doctrine/dbal` ou Laravel 11+ avec support `change()` natif selon la version du projet.

**Déploiement :**

```bash
php artisan migrate
```

**Vérification manuelle MySQL :**

```sql
SHOW COLUMNS FROM field_visits LIKE 'operator_id';
-- Null doit être YES
```

### 4.2 Code applicatif

**Aucune modification** de `CashSessionService` : `operator_id => null` est le comportement voulu.

---

## 5. Impacts par domaine

| Domaine | Impact | Action |
|---------|--------|--------|
| **Fiscalité / encaissement** | Aucun | `municipal_payments.operator_id` reste NOT NULL |
| **Recouvrement** | Débloque ouverture caisse | Aucun changement API mobile |
| **Dashboard** | `field_visits_total` compte toutes les visites | Acceptable ; option future : filtrer `whereNotNull('operator_id')` pour « visites commerce » uniquement |
| **Rapports / stats terrain** | Les sessions apparaissent comme visites sans commerce | Documenter ; filtrer par `visit_type` si besoin |
| **Intégrité FK** | `restrictOnDelete` conservé | Suppression commerce impossible si visites liées (inchangé) |
| **Historique QR** | Charge `fieldVisits` sur l'opérateur | Les visites session n'ont pas d'`operator_id` → n'apparaissent pas dans l'historique commerce (correct) |

---

## 6. Tests de non-régression

### Existants (déjà dans le dépôt)

| Fichier | Cas |
|---------|-----|
| `tests/Feature/Municipality/CashSessionTest.php` | Ouverture caisse, `field_visit` `session_open` avec `operator_id` null |
| `tests/Feature/Municipality/CashSessionTest.php` | Fermeture caisse, `field_visit` `session_close` |
| `tests/Feature/Municipality/FieldVisitTest.php` | Visite commerce classique (`inspection`, `verification`) |
| `tests/Feature/Municipality/FiscalCollectionTest.php` | Encaissement → `field_visit` `payment` avec `operator_id` |

### Ajouts proposés

1. **`EconomicOperatorIntegrityTest::test_field_visits_operator_id_column_is_nullable`** — verrouille le schéma (échec si prod-like NOT NULL).
2. **`CashSessionTest::test_close_session_creates_field_visit_and_audit`** — assertion explicite `operator_id => null` (symétrie avec l'ouverture).

Exécution :

```bash
php artisan test --filter=CashSessionTest
php artisan test --filter=FieldVisitTest
php artisan test --filter=EconomicOperatorIntegrityTest
```

---

## 7. Procédure de validation terrain (après migration)

1. `php artisan migrate` sur l'environnement concerné.
2. Vérifier `SHOW COLUMNS` → `operator_id` nullable.
3. Application mobile → Recouvrement → **Ouvrir caisse** (GPS autorisé).
4. Confirmer HTTP 201 et snackbar succès.
5. Vérifier en base : une ligne `field_visits` avec `visit_type = session_open`, `operator_id IS NULL`, `cash_session_id` renseigné.
6. Fermer la caisse → `visit_type = session_close`, `operator_id IS NULL`.
7. Scanner un commerce et encaisser → `visit_type = payment`, `operator_id` = ID commerce.

---

## 8. Synthèse

| Élément | Détail |
|---------|--------|
| **Correctif** | Migration `operator_id` nullable sur `field_visits` |
| **Code métier** | Inchangé |
| **Risque** | Faible ; aligne prod sur le design V2.5 / V3 |
| **Prochaine étape** | Valider ce rapport → appliquer migration → `php artisan migrate` → test terrain |
