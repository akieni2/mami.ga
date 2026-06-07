# Sprint 02 — Laravel Reverb (temps réel)

## Backend

### Installation (VPS / dev)

```bash
composer install
php artisan reverb:install   # si premier déploiement
```

`.env` :

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=mami-ga
REVERB_APP_KEY=mami-local-key
REVERB_APP_SECRET=mami-local-secret
REVERB_HOST=63.142.241.105
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Démarrer Reverb (production)

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

Supervisor recommandé pour garder Reverb actif.

### Auth WebSocket (Sanctum)

`POST /broadcasting/auth` — middleware `auth:sanctum`  
Headers : `Authorization: Bearer {token}`

### Canaux privés

| Canal | Abonnés |
|-------|---------|
| `private-user-{id}` | Client |
| `private-driver-{id}` | Chauffeur (+ client en course) |
| `private-ride-{id}` | Client + chauffeur assigné |

Canaux legacy `mami.*` conservés.

### Événements broadcastés

- `RideRequested`
- `RideAssigned` (nouveau)
- `RideAccepted`
- `RideArrived` (classe `DriverArrived`)
- `RideStarted`
- `RideCompleted`
- `DriverLocationUpdated`

Payload : `{ event, payload, occurred_at }`

## Flutter (client + chauffeur)

Package : `pusher_channels_flutter` (protocole Pusher / Reverb)

```bash
cd mobile/mami_client   # ou mami_driver
flutter pub get
flutter run \
  --dart-define=API_BASE_URL=http://63.142.241.105/api \
  --dart-define=REVERB_APP_KEY=mami-local-key \
  --dart-define=REVERB_HOST=63.142.241.105 \
  --dart-define=REVERB_PORT=8080
```

**Mode hybride** : WebSocket + polling REST (fallback inchangé).

## Tests

```bash
php artisan test --filter=Reverb
php artisan test --filter=Realtime
```
