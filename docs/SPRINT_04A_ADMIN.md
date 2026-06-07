# Sprint 04A — Dashboard d'exploitation

## Objectif

Interface opérateur pour superviser l'activité MAMI.GA : courses, chauffeurs, clients, carte temps réel et rapports.

## URLs (authentification admin requise)

| Page | URL |
|------|-----|
| Tableau de bord | `/admin/dashboard` |
| Courses | `/admin/rides` |
| Détail course | `/admin/rides/{id}` |
| Chauffeurs | `/admin/drivers` |
| Clients | `/admin/clients` |
| Historique client | `/admin/clients/{id}` |
| Carte opérationnelle | `/admin/map` |
| Rapports | `/admin/reports?period=day\|week\|month` |

Les anciennes URLs (`/dashboard`, `/drivers`, …) redirigent vers le préfixe `/admin/`.

## Fonctionnalités

### Tableau de bord
- Courses aujourd'hui, en cours
- Chauffeurs en ligne / hors ligne / en course
- Chiffre d'affaires estimé (courses terminées du jour)
- Dernières courses (polling 10 s via `/admin/live/dashboard`)

### Courses
- Liste paginée avec filtres : `pending`, `accepted`, `arrived`, `started`, `completed`, `cancelled`
- Détail : client, chauffeur, véhicule, prix, distance/ETA (via `RideTrackingService`)

### Chauffeurs
- Statut métier, présence, note, dernière position
- Carte Leaflet intégrée + refresh live `/admin/live/drivers`

### Clients
- Utilisateurs non-chauffeurs avec compteur de courses
- Page historique par client

### Carte opérationnelle
- Plein écran (Leaflet / OSM)
- Polling 10 s + abonnement Reverb sur canaux publics `mami.drivers.{id}` pour `DriverLocationUpdated`

### Rapports
- Agrégats jour / semaine / mois : volumes, CA estimé, répartition par statut

## Services

- `AdminDashboardService` — KPIs tableau de bord
- `AdminReportsService` — statistiques par période
- `AdminLiveMapService` — payload JSON chauffeurs (inchangé)

## API REST

Aucune modification des endpoints `/api/*`. Les endpoints admin live restent sous `/admin/live/*`.

## Déploiement

```bash
composer install --no-dev
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
```

Compte admin : `admin@mami.ga` / `password`
