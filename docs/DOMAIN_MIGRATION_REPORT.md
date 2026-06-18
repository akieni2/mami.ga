# Migration infrastructure — IP publique → domaines MAMI.GA

**Date :** 18 juin 2026  
**Branche :** `feature/mami-taxi-v2-p2`

---

## Objectif

Supprimer l'utilisation de l'IP publique `63.142.241.105` au profit des domaines HTTPS/WSS officiels.

---

## Mapping des URLs

| Usage | Ancienne URL | Nouvelle URL |
|-------|--------------|--------------|
| API REST | `http://63.142.241.105/api` | `https://api.mami.ga/api` |
| Backoffice admin | `http://63.142.241.105/login` | `https://admin.mami.ga` |
| Portail public | — | `https://mami.ga` |
| WebSocket Reverb | `ws://63.142.241.105:8080` | `wss://ws.mami.ga` |
| Vérification quittance (QR) | `{APP_URL}/public/receipts/verify/{token}` | `https://mami.ga/public/receipts/verify/{token}` |
| Auth broadcasting | `{API}/broadcasting/auth` | `https://api.mami.ga/broadcasting/auth` |

---

## Fichiers modifiés (code)

| Fichier | Changement |
|---------|------------|
| `mobile/mami_client/lib/core/config/app_config.dart` | `apiBaseUrl`, `portalUrl`, `adminUrl`, `websocketUrl` |
| `mobile/mami_driver/lib/core/config/app_config.dart` | idem |
| `mobile/mami_client/lib/core/config/reverb_config.dart` | dérivé de `AppConfig.websocketUrl` (`ws.mami.ga`, port 443, TLS) |
| `mobile/mami_driver/lib/core/config/reverb_config.dart` | idem |
| `config/mami.php` | section `urls` (api, portal, admin, websocket) |
| `.env.example` | `APP_URL`, `REVERB_*`, `MAMI_*_URL`, `SANCTUM_STATEFUL_DOMAINS` |
| `ReceiptVerificationUrlBuilder.php` | QR → `config('mami.urls.portal')` |
| `phpunit.xml` | URLs de test domaines |
| `tests/Feature/Municipality/ReceiptVerificationTest.php` | assertion URL portail |
| `mobile/mami_client/README.md` | URL prod documentée |

## Documentation mise à jour

Tous les fichiers `docs/*.md` référençant `63.142.241.105` ont été alignés sur les domaines.

---

## Impacts backend (VPS)

Mettre à jour `.env` production :

```env
APP_URL=https://admin.mami.ga
MAMI_API_URL=https://api.mami.ga
MAMI_PORTAL_URL=https://mami.ga
MAMI_ADMIN_URL=https://admin.mami.ga
MAMI_WEBSOCKET_URL=wss://ws.mami.ga
REVERB_HOST=ws.mami.ga
REVERB_PORT=443
REVERB_SCHEME=https
SANCTUM_STATEFUL_DOMAINS=mami.ga,admin.mami.ga,api.mami.ga
```

Puis :

```bash
php artisan config:cache && php artisan route:cache
```

**Nginx** : vhosts `mami.ga`, `api.mami.ga`, `admin.mami.ga`, `ws.mami.ga` doivent pointer vers Laravel / Reverb.

**Quittances** : les nouveaux QR utilisent `https://mami.ga/public/receipts/verify/{token}`.

---

## Impacts APK (Flutter)

Rebuild obligatoire pour embarquer les nouvelles URLs par défaut :

```bash
cd mobile/mami_client
flutter build apk --release \
  --dart-define=API_BASE_URL=https://api.mami.ga/api \
  --dart-define=WEBSOCKET_URL=wss://ws.mami.ga \
  --dart-define=PORTAL_URL=https://mami.ga

cd ../mami_driver
flutter build apk --release \
  --dart-define=API_BASE_URL=https://api.mami.ga/api \
  --dart-define=WEBSOCKET_URL=wss://ws.mami.ga
```

Sans `--dart-define`, les valeurs par défaut dans `AppConfig` pointent déjà vers les domaines.

---

## Modules vérifiés

| Module | Statut |
|--------|--------|
| MAMI Taxi Client | `AppConfig` + `ReverbConfig` |
| MAMI Driver | `AppConfig` + `ReverbConfig` |
| Municipality (QR quittances) | `ReceiptVerificationUrlBuilder` → portail |
| Reverb / notifications temps réel | `wss://ws.mami.ga` |
| Tests Municipality | `ReceiptVerificationTest` |

---

## Validation

```bash
php artisan test tests/Feature/Municipality/ReceiptVerificationTest.php
curl -s https://api.mami.ga/up
curl -s https://mami.ga/public/receipts/verify/00000000-0000-4000-8000-000000000000
```
