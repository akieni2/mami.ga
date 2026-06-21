# Audit migrations — Sprint 4.0 / 4.1

**Date :** juin 2026 · **Statut :** corrigé · **Commit :** à déployer

---

## 1. Constat production

| Élément | Détail |
|---------|--------|
| **Erreur** | `SQLSTATE[42S02] Table 'financial_missions' doesn't exist` |
| **Cause racine** | Ordre chronologique des fichiers de migration Laravel |
| **Migration fautive** | `2026_06_16_200000_add_workflow_to_financial_missions_sprint41.php` |
| **Migration créatrice** | `2026_06_28_100000_create_municipality_sprint4_financial_governance_tables.php` |

### Séquence incorrecte (avant correction)

```
2026_06_16_200000  →  ALTER financial_missions     ❌ table absente
2026_06_28_100000  →  CREATE financial_missions  ✓
```

Laravel exécute les migrations par **préfixe date** du nom de fichier. Toute migration Sprint 4.1 datée avant le 28 juin provoque l'échec sur une base vierge ou en cours de déploiement.

---

## 2. Inventaire migrations concernées

| Fichier | Rôle | Ordre |
|---------|------|-------|
| `2026_06_28_100000_create_municipality_sprint4_financial_governance_tables.php` | CREATE `financial_missions`, `municipal_finance_journal_entries`, `municipal_treasury_remittances` + ALTER `cash_sessions` | **1 — Sprint 4.0** |
| ~~`2026_06_16_200000_add_workflow_to_financial_missions_sprint41.php`~~ | **SUPPRIMÉ** — ordre invalide | — |
| `2026_06_29_100000_add_workflow_to_financial_missions_sprint41.php` | ALTER workflow + CREATE `financial_mission_approvals` (idempotent) | **2 — Sprint 4.1** |

### Autres migrations juin 2026 (non impactées)

Les migrations `2026_06_16_100000`, `2026_06_16_120000*`, `2026_06_17` … `2026_06_27` ne touchent pas `financial_missions`. Aucune modification requise.

---

## 3. Correction appliquée

1. **Suppression** du fichier `2026_06_16_200000_*` (jamais exécutable avant 4.0).
2. **Création** de `2026_06_29_100000_*` postérieure au 28 juin.
3. **Idempotence** :
   - vérifie `Schema::hasTable('financial_missions')` avant ALTER ;
   - vérifie `Schema::hasColumn(..., 'workflow_status')` avant ajout colonnes ;
   - vérifie `Schema::hasTable('financial_mission_approvals')` avant CREATE ;
   - migre les statuts `authorized` → `approved`, `closed` → `closed` uniquement si nécessaire.
4. **Réconciliation** : supprime l'entrée fantôme `2026_06_16_200000_*` dans `migrations` si enregistrée sans schéma appliqué.
5. **Test unitaire** `FinancialMissionsMigrationOrderTest` — empêche toute régression d'ordre.

---

## 4. Matrice de compatibilité

| Scénario | Comportement attendu |
|----------|----------------------|
| **Base vierge** | 4.0 CREATE puis 4.1 ALTER/CREATE — OK |
| **Prod Sprint 4.0 seule** (tables existent, pas workflow) | 4.1 idempotent ajoute colonnes — OK |
| **Prod échec 4.1** (16 juin non enregistré dans `migrations`) | `migrate` exécute 4.29 — OK |
| **Entrée fantôme 16 juin** dans `migrations` sans colonnes | Réconciliation + 4.29 — OK |
| **Prod déjà migrée 4.1 manuellement** | 4.29 no-op (colonnes présentes) — OK |

**Interdit :** `migrate:fresh` en production.

---

## 5. Non-régression code applicatif

Aucun changement PHP/Flutter requis. Le schéma cible reste identique ; seul l'ordre et l'idempotence des migrations changent.

---

## 6. Correctif index MySQL (1059)

| Problème | Index auto Laravel `financial_mission_approvals_financial_mission_id_created_at_index` (65 car.) |
| Correctif | Nom explicite `fma_mission_created_idx` (23 car.) |
| Rejeu prod | Migration idempotente : table partielle + index manquant → OK |

---

## 7. Validation

```bash
php artisan test tests/Unit/Migrations/FinancialMissionsMigrationOrderTest.php
php artisan migrate --force   # VPS / CI avec MySQL
php artisan test --filter=FinancialMission
```

---

*Rapport généré dans le cadre de l'audit Sprint 4.1 — MAMI.ga Owendo.*
