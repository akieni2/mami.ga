# Checklist de déploiement VPS — Municipality V3 Sprint 3

**Version :** 1.0  
**Date :** juin 2026  
**Environnement cible :** VPS Ubuntu — production Owendo  
**Domaines :** `mami.ga`, `api.mami.ga`, `admin.mami.ga`, `ws.mami.ga`

---

## Prérequis

- Accès SSH au VPS
- Branche `feature/mami-taxi-v2-p2` à jour (≥ tag `v1.6-roadmap-2026`)
- Certificats TLS (Let's Encrypt ou équivalent) pour les 4 domaines
- Sauvegarde BDD avant migration

---

## 1. Mise à jour du code

```bash
cd / or wherever the app lives on VPS
git fetch origin
git checkout feature/mami-taxi-v2-p2
git pull origin feature/mami-taxi-v2-p2
# Vérifier : git log -1 --oneline
# Attendu : ≥ 2396c1b (plan d'exécution 2026)
```

| # | Vérification | OK |
|---|--------------|-----|
| 1.1 | Code à jour sur branche cible | ☐ |
| 1.2 | Tag `v1.6-roadmap-2026` présent | ☐ |

---

## 2. Variables d'environnement (`.env` production)

Copier depuis `.env.example` les sections suivantes et **adapter** :

```env
APP_URL=https://admin.mami.ga
APP_ENV=production
APP_DEBUG=false

# Domaines MAMI
MAMI_API_URL=https://api.mami.ga
MAMI_PORTAL_URL=https://mami.ga
MAMI_ADMIN_URL=https://admin.mami.ga
MAMI_WEBSOCKET_URL=wss://ws.mami.ga

# Module municipalité — OBLIGATOIRE Sprint 3
MAMI_MODULE_MUNICIPALITY=true

# Sanctum
SANCTUM_STATEFUL_DOMAINS=mami.ga,admin.mami.ga,api.mami.ga

# Reverb
REVERB_APP_ID=mami-ga
REVERB_APP_KEY=<secret prod>
REVERB_APP_SECRET=<secret prod>
REVERB_HOST=ws.mami.ga
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Queue
QUEUE_CONNECTION=database
# ou redis si configuré :
# QUEUE_CONNECTION=redis
# REDIS_HOST=127.0.0.1
```

| # | Vérification | OK |
|---|--------------|-----|
| 2.1 | `MAMI_MODULE_MUNICIPALITY=true` | ☐ |
| 2.2 | Aucune URL IP legacy (`63.142.241.105`) | ☐ |
| 2.3 | `APP_DEBUG=false` | ☐ |
| 2.4 | Secrets Reverb uniques production | ☐ |

---

## 3. Dépendances et build

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=MunicipalityDatabaseSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

| # | Vérification | OK |
|---|--------------|-----|
| 3.1 | `composer install` sans erreur | ☐ |
| 3.2 | Migrations Municipality toutes `Ran` | ☐ |
| 3.3 | `RolePermissionSeeder` exécuté | ☐ |
| 3.4 | `MunicipalityDatabaseSeeder` exécuté | ☐ |
| 3.5 | Caches régénérés | ☐ |

### Vérification migrations

```bash
php artisan migrate:status | grep municipality
```

**Attendu :** 8 migrations Municipality en statut `Ran`, dont :
- `2026_06_25_100000_create_municipality_v3_sprint3_official_receipts`
- `2026_06_25_100001_widen_municipal_receipt_qr_value_column`

---

## 4. Infrastructure réseau (Nginx)

| Domaine | Rôle | Backend |
|---------|------|---------|
| `mami.ga` | Portail + vérification quittance | Laravel `public/` |
| `api.mami.ga` | API REST `/api/*` | Laravel `public/` |
| `admin.mami.ga` | Backoffice Blade | Laravel `public/` |
| `ws.mami.ga` | WebSocket Reverb | Proxy → port 8080 |

| # | Vérification | Commande / méthode | OK |
|---|--------------|-------------------|-----|
| 4.1 | `curl -I https://mami.ga` → 200/301 | ☐ |
| 4.2 | `curl -I https://api.mami.ga/api/municipality/status` → 401/403 (auth requise) | ☐ |
| 4.3 | `curl -I https://admin.mami.ga/login` → 200 | ☐ |
| 4.4 | Certificat TLS valide (pas d'avertissement navigateur) | ☐ |
| 4.5 | WebSocket Reverb connectable depuis APK | ☐ |

---

## 5. Services systemd (queues + Reverb)

### Queue worker

```ini
# /etc/systemd/system/mami-queue.service
[Service]
ExecStart=/usr/bin/php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
```

```bash
sudo systemctl enable mami-queue
sudo systemctl start mami-queue
sudo systemctl status mami-queue
```

### Reverb

```ini
# /etc/systemd/system/mami-reverb.service
[Service]
ExecStart=/usr/bin/php /path/to/artisan reverb:start
Restart=always
```

```bash
sudo systemctl enable mami-reverb
sudo systemctl start mami-reverb
sudo systemctl status mami-reverb
```

| # | Vérification | OK |
|---|--------------|-----|
| 5.1 | Queue worker actif et stable | ☐ |
| 5.2 | Reverb actif | ☐ |
| 5.3 | Logs sans erreur récurrente | ☐ |

---

## 6. Données métier pilote (backoffice admin)

Connexion : `https://admin.mami.ga` — compte admin (`admin@mami.ga` après seed).

| # | Action backoffice | OK |
|---|-------------------|-----|
| 6.1 | Créer au moins 1 type de taxe (ex. Boutique) | ☐ |
| 6.2 | Créer taux + périodicité | ☐ |
| 6.3 | Affecter taxes aux opérateurs pilotes (~50) | ☐ |
| 6.4 | Générer obligations période courante | ☐ |
| 6.5 | Vérifier opérateur pilote avec QR actif | ☐ |
| 6.6 | Créer compte agent municipal + rôle `municipal_agent` | ☐ |

> Les montants et taxes ne doivent **pas** être codés en dur — saisie dashboard uniquement.

---

## 7. Smoke tests API (post-déploiement)

```bash
# Health module (token agent requis)
curl -H "Authorization: Bearer <TOKEN_AGENT>" \
  https://api.mami.ga/api/municipality/status

# Vérification publique (sans auth — token test après encaissement)
curl https://mami.ga/public/receipts/verify/<verification_token>
```

| # | Test | Résultat attendu | OK |
|---|------|------------------|-----|
| 7.1 | GET `/api/municipality/status` avec token agent | 200 | ☐ |
| 7.2 | GET `/api/municipality/status` sans token | 401 | ☐ |
| 7.3 | GET verify token valide | Page statut **valide** | ☐ |
| 7.4 | GET verify token invalide | Statut **introuvable** | ☐ |

---

## 8. APK Flutter agent (rebuild obligatoire)

Les APK doivent embarquer les domaines `mami.ga` (commit `3f390a3`+).

```bash
cd mobile/mami_client
flutter pub get
flutter build apk --release
# APK : build/app/outputs/flutter-apk/app-release.apk
```

| # | Vérification | OK |
|---|--------------|-----|
| 8.1 | Build APK sans erreur | ☐ |
| 8.2 | `AppConfig.apiBaseUrl` = `https://api.mami.ga/api` | ☐ |
| 8.3 | Menu Agent / Recouvrement visible (module actif côté API) | ☐ |
| 8.4 | APK installé sur terminal agent pilote | ☐ |

---

## 9. Stockage et permissions fichiers

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

| # | Vérification | OK |
|---|--------------|-----|
| 9.1 | PDF quittances écrits dans `storage/app/municipality/receipts` | ☐ |
| 9.2 | Permissions storage correctes | ☐ |

---

## 10. Rollback d'urgence

En cas de blocage production :

```bash
# Désactiver encaissement sans rollback migration
# Dans .env :
MAMI_MODULE_MUNICIPALITY=false
php artisan config:cache
```

| # | Documenté | OK |
|---|-----------|-----|
| 10.1 | Procédure rollback connue équipe | ☐ |
| 10.2 | Sauvegarde BDD datée avant go-live | ☐ |

---

## Validation finale déploiement VPS

| Responsable | Date | Signature | Toutes cases cochées |
|-------------|------|-----------|---------------------|
| | | | ☐ Oui |

**Prochaine étape :** [CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md](CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md)
