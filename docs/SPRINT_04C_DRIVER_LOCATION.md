# Sprint 04C — Fiche chauffeur & position live

## Objectif

Permettre à l'administrateur d'ouvrir la fiche d'un chauffeur depuis `/admin/drivers` et de suivre sa position en temps réel.

## Routes Admin

| Méthode | URL | Description |
|---------|-----|-------------|
| `GET` | `/admin/drivers/{driver}` | Fiche détaillée + carte OSM dernière position |
| `GET` | `/admin/drivers/{driver}/live` | Carte plein écran temps réel |
| `GET` | `/admin/live/drivers/{driver}` | JSON polling (fallback 10 s) |

Middleware : `auth` + `admin`.

## Fiche chauffeur (`/admin/drivers/{id}`)

Affiche :

- Nom, téléphone, email
- Statut métier (`online` / `offline` / `on_ride`)
- Disponibilité (présence via `DriverPresenceService`)
- Note
- Véhicule : marque, modèle, plaque, couleur, année
- Dernière activité (`last_seen_at`)
- Coordonnées GPS
- Carte Leaflet centrée sur la dernière position connue
- Bouton **Voir en temps réel** → `/admin/drivers/{id}/live`

## Page live (`/admin/drivers/{id}/live`)

- Carte quasi plein écran (OpenStreetMap)
- Abonnement Reverb canal public `mami.drivers.{id}` → event `DriverLocationUpdated`
- Polling `/admin/live/drivers/{id}` toutes les 10 s (fallback)
- Mise à jour marqueur, coordonnées affichées, badge présence

## Architecture

```
/admin/drivers
      │ clic nom / Fiche
      ▼
/admin/drivers/{id}          (carte statique dernière position)
      │ Voir en temps réel
      ▼
/admin/drivers/{id}/live
      ├── Reverb: mami.drivers.{id}
      └── Poll: GET /admin/live/drivers/{id}
```

### Service

`AdminLiveMapService::driverPayload(Driver $driver)` — payload JSON unifié pour polling et cohérence avec la carte opérationnelle.

## Rétrocompatibilité

- Aucune modification des API `/api/*` mobiles
- Aucun changement Reverb, dispatch, tracking
- Routes existantes `/admin/drivers`, `/admin/live/drivers`, `/admin/map` inchangées en comportement

## Tests

```bash
php artisan test --filter=AdminDriverDetail
```

Fichier : `tests/Feature/AdminDriverDetailTest.php`

## Déploiement VPS

```bash
cd /var/www/mami.ga
git pull origin main
npm ci && npm run build
php artisan config:cache
php artisan test --filter=AdminDriverDetail
```

Vérifier :

1. `/admin/drivers` — liens vers fiches
2. `/admin/drivers/1` — détail + carte
3. `/admin/drivers/1/live` — suivi Reverb + polling
