# MAMI Super App — Rapport de migration

**Date** : 17 juin 2026  
**Migration** : `2026_06_17_120000_create_super_app_core_tables.php`  
**Commit** : `d12d49ada80521d55b18e21b1ee32595a6671412`  
**Type** : **additive uniquement** — `Schema::create` sur tables nouvelles, aucun `Schema::table` sur l'existant

---

## 1. Synthèse

| Action | Nombre |
|--------|--------|
| Tables créées | **11** |
| Tables modifiées | **0** |
| Colonnes ajoutées (tables existantes) | **0** |
| Colonnes supprimées | **0** |
| Index ajoutés (hors tables Taxi) | **12** (+ contraintes UNIQUE / PRIMARY KEY) |

---

## 2. Tables créées

| # | Table | Description |
|---|-------|-------------|
| 1 | `roles` | Rôles globaux Super App |
| 2 | `permissions` | Permissions par module |
| 3 | `user_roles` | Pivot utilisateur ↔ rôle |
| 4 | `permission_role` | Pivot rôle ↔ permission |
| 5 | `addresses` | Adresses normalisées (futur usage cross-modules) |
| 6 | `locations` | Historique GPS polymorphique |
| 7 | `ratings` | Notations polymorphiques |
| 8 | `attachments` | Fichiers polymorphiques |
| 9 | `payments` | Paiements polymorphiques |
| 10 | `transactions` | Mouvements liés à un paiement |
| 11 | `audit_logs` | Journal d'audit cross-modules |

### Détail colonnes par table

#### `roles`
`id`, `slug` (unique), `name`, `module`, `description`, `created_at`, `updated_at`

#### `permissions`
`id`, `slug` (unique), `name`, `module`, `created_at`, `updated_at`

#### `user_roles`
`user_id` (FK → `users`), `role_id` (FK → `roles`), `assigned_at`, `assigned_by` (FK → `users`, nullable) — **PK** (`user_id`, `role_id`)

#### `permission_role`
`role_id` (FK → `roles`), `permission_id` (FK → `permissions`) — **PK** (`role_id`, `permission_id`)

#### `addresses`
`id`, `label`, `latitude`, `longitude`, `source`, `commune`, `quartier`, `plus_code`, `metadata`, `created_at`, `updated_at`

#### `locations`
`id`, `locatable_type`, `locatable_id`, `latitude`, `longitude`, `accuracy_meters`, `heading`, `speed_kmh`, `recorded_at`, `context`, `created_at`, `updated_at`

#### `ratings`
`id`, `rater_id` (FK → `users`), `rateable_type`, `rateable_id`, `score`, `comment`, `module`, `context`, `created_at`, `updated_at`

#### `attachments`
`id`, `attachable_type`, `attachable_id`, `disk`, `path`, `mime_type`, `size_bytes`, `uploaded_by` (FK → `users`), `created_at`, `updated_at`

#### `payments`
`id`, `payer_id` (FK → `users`), `payee_id` (FK → `users`), `payable_type`, `payable_id`, `amount`, `currency`, `method`, `status`, `external_reference`, `idempotency_key` (unique), `metadata`, `authorized_at`, `captured_at`, `failed_at`, `failure_reason`, `created_at`, `updated_at`

#### `transactions`
`id`, `payment_id` (FK → `payments`), `type`, `amount`, `currency`, `status`, `provider`, `provider_reference`, `payload`, `processed_at`, `created_at`, `updated_at`

#### `audit_logs`
`id`, `actor_id` (FK → `users`), `subject_type`, `subject_id`, `action`, `module`, `properties`, `ip_address`, `user_agent`, `created_at` (pas de `updated_at`)

---

## 3. Tables modifiées

**Aucune.**

La migration Super App ne contient aucun appel `Schema::table()`.

---

## 4. Colonnes ajoutées (tables préexistantes)

**Aucune.**

Les clés étrangères des nouvelles tables **référencent** `users` (et entre elles), mais la structure de `users` n'est pas modifiée par cette migration.

---

## 5. Colonnes supprimées

**Aucune.**

---

## 6. Index et contraintes ajoutés

### Index composites / nommés

| Table | Index | Colonnes |
|-------|-------|----------|
| `locations` | `locations_locatable_recorded_idx` | `locatable_type`, `locatable_id`, `recorded_at` |
| `ratings` | `ratings_rateable_type_rateable_id_index` | `rateable_type`, `rateable_id` |
| `attachments` | `attachments_attachable_type_attachable_id_index` | `attachable_type`, `attachable_id` |
| `payments` | `payments_payable_type_payable_id_index` | `payable_type`, `payable_id` |
| `payments` | `payments_payer_id_status_index` | `payer_id`, `status` |
| `transactions` | `transactions_payment_id_type_index` | `payment_id`, `type` |
| `audit_logs` | `audit_logs_subject_type_subject_id_index` | `subject_type`, `subject_id` |
| `audit_logs` | `audit_logs_module_created_at_index` | `module`, `created_at` |

### Contraintes UNIQUE

| Table | Colonnes |
|-------|----------|
| `roles` | `slug` |
| `permissions` | `slug` |
| `ratings` | `rater_id`, `rateable_type`, `rateable_id`, `context` (`ratings_unique_per_context`) |
| `payments` | `idempotency_key` |

### Clés primaires composites

| Table | Colonnes |
|-------|----------|
| `user_roles` | `user_id`, `role_id` |
| `permission_role` | `role_id`, `permission_id` |

### Clés étrangères (références sortantes — sans altération des tables cibles)

| Table source | FK | Table cible |
|--------------|-----|-------------|
| `user_roles` | `user_id`, `assigned_by` | `users` |
| `user_roles` | `role_id` | `roles` |
| `permission_role` | `role_id`, `permission_id` | `roles`, `permissions` |
| `ratings` | `rater_id` | `users` |
| `attachments` | `uploaded_by` | `users` |
| `payments` | `payer_id`, `payee_id` | `users` |
| `transactions` | `payment_id` | `payments` |
| `audit_logs` | `actor_id` | `users` |

---

## 7. Tables Taxi — vérification de compatibilité

Tables auditées : **`drivers`**, **`rides`**, **`ride_offers`**, **`ride_dispatch_waves`**, **`vehicles`**

| Table | `Schema::table` dans migration Super App | Colonnes modifiées | Index modifiés | Verdict |
|-------|------------------------------------------|--------------------|----------------|---------|
| `drivers` | **Non** | Aucune | Aucun | **Compatible** |
| `rides` | **Non** | Aucune | Aucun | **Compatible** |
| `ride_offers` | **Non** | Aucune | Aucun | **Compatible** |
| `ride_dispatch_waves` | **Non** | Aucune | Aucun | **Compatible** |
| `vehicles` | **Non** | Aucune | Aucun | **Compatible** |

### Tables Taxi connexes (hors périmètre demandé, même constat)

| Table | Touchée par migration Super App |
|-------|--------------------------------|
| `driver_locations` | Non |
| `ride_events` | Non |
| `driver_applications` | Non |

### Confirmation technique

```bash
# Aucune occurrence dans la migration Super App :
grep -E "Schema::table\('(drivers|rides|ride_offers|ride_dispatch_waves|vehicles)" \
  database/migrations/2026_06_17_120000_create_super_app_core_tables.php
# → résultat vide
```

Les APIs Taxi (`/api/rides/*`, `/api/drivers/*`) et les modèles Eloquent existants (`App\Models\Ride`, `Driver`, etc.) **ne dépendent d'aucune colonne nouvelle** sur ces tables.

---

## 8. Table `notifications`

Table Laravel **déjà existante** (`2026_05_26_000002_create_notifications_table.php`) — **non recréée**, **non modifiée** par la migration Super App.

---

## 9. Rollback (`down()`)

Suppression **uniquement** des 11 tables listées en §2, dans l'ordre inverse des dépendances FK. Aucune table Taxi n'est impactée par le rollback.

---

## 10. Commandes de vérification post-migration

```bash
php artisan migrate --force
php artisan db:show --table=drivers
php artisan db:show --table=rides
php artisan test --filter="RideWorkflowTest|RideLifecycleV2Test|RideDispatchV2Test|SuperAppArchitectureTest"
```

**Résultat attendu** : schéma Taxi inchangé ; 19+ tests Taxi/Super App passants.

---

*Rapport généré à partir de l'analyse statique de `database/migrations/2026_06_17_120000_create_super_app_core_tables.php` et recoupement avec l'ensemble des migrations Taxi du dépôt.*
