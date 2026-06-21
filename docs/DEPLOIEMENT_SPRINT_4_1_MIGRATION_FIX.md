# Déploiement VPS — correction migration Sprint 4.1

**Objectif :** appliquer la correction d'ordre des migrations sans `migrate:fresh`.

---

## Prérequis

- Backup base de données récent
- Sprint 4.0 déjà déployé **ou** migration 4.0 en attente dans la file
- PHP 8.2+, Composer, accès SSH VPS

---

## 1. Backup (obligatoire)

```bash
cd /var/www/mami.ga
php artisan down --retry=60

mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  > "/var/backups/mami_ga_$(date +%Y%m%d_%H%M%S).sql"
```

---

## 2. Déployer le code corrigé

```bash
cd /var/www/mami.ga
git fetch origin
git checkout feature/mami-taxi-v2-p2
git pull origin feature/mami-taxi-v2-p2

composer install --no-dev --optimize-autoloader
```

---

## 3. Vérifier l'état migrations (diagnostic)

```bash
php artisan migrate:status | grep -E "financial|sprint4|sprint41"
```

Résultat attendu :

| Migration | Statut typique prod |
|-----------|---------------------|
| `2026_06_28_100000_create_municipality_sprint4_financial_governance_tables` | **Ran** |
| `2026_06_16_200000_add_workflow_to_financial_missions_sprint41` | **Absent** (fichier supprimé) |
| `2026_06_29_100000_add_workflow_to_financial_missions_sprint41` | **Pending** |

Si une entrée `2026_06_16_200000_*` apparaît dans `migrate:status` **Ran** mais les colonnes workflow sont absentes :

```bash
mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e \
  "SHOW COLUMNS FROM financial_missions LIKE 'workflow_status';"
```

La migration corrective `2026_06_29_100000` supprime automatiquement l'entrée fantôme avant d'appliquer le schéma.

---

## 4. Exécuter les migrations

```bash
php artisan migrate --force
```

Sortie attendue (extrait) :

```
Migrating: 2026_06_29_100000_add_workflow_to_financial_missions_sprint41
Migrated:  2026_06_29_100000_add_workflow_to_financial_missions_sprint41
```

---

## 5. Permissions & cache

```bash
php artisan db:seed --class=RolePermissionSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 6. Vérifications post-déploiement

```bash
# Colonnes workflow présentes
mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e \
  "SHOW COLUMNS FROM financial_missions WHERE Field IN ('workflow_status','approved_at','rejection_reason');"

# Table approvals
mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e \
  "SHOW TABLES LIKE 'financial_mission_approvals';"

# API dashboard DAF (token DAF requis)
curl -s -H "Authorization: Bearer $DAF_TOKEN" \
  https://api.mami.ga/api/municipality/finance/dashboard | jq '.data.validation'

php artisan up
```

---

## 7. Rollback (urgence uniquement)

```bash
php artisan down
php artisan migrate:rollback --step=1 --force
# Restaurer backup si nécessaire
mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < /var/backups/mami_ga_YYYYMMDD_HHMMSS.sql
php artisan up
```

---

## Variables d'environnement (inchangées)

```env
MAMI_FINANCE_LEGACY_MISSION_AUTHORIZE=true
MAMI_MUNICIPALITY_REQUIRE_MISSION=false
```

---

## Cas particuliers

| Situation | Action |
|-----------|--------|
| `migrate` échoue « table financial_missions absente » | Exécuter d'abord `2026_06_28_100000` : `php artisan migrate --path=database/migrations/2026_06_28_100000_create_municipality_sprint4_financial_governance_tables.php --force` |
| Colonnes workflow déjà présentes | Migration 4.29 no-op — normal |
| Encaissement terrain | Non impacté (flags par défaut) |

---

*Guide déploiement MAMI.ga — correction migration Sprint 4.1*
