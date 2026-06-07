# Sprint 03 — Cartographie temps réel (OSM)

## Backend

### RouteService
- Tracé `straight_line` (24 segments)
- Interface `RouteCalculatorInterface` — prêt pour OSRM / GraphHopper / Valhalla

### EstimatedArrivalService
- Distance + ETA via `DistanceRefreshService`
- Méthode `betweenPoints()` pour futur routage

### Tracking API (inchangé, enrichi)
`GET /api/rides/{id}/tracking` inclut désormais :
- `route` : `{ provider, distance_km, coordinates[] }`
- `tracking.estimated_arrival` : `{ distance_km, eta_minutes }`

## Flutter

### Widget `MamiMap`
- `flutter_map` + tuiles OpenStreetMap
- Marqueurs : utilisateur, client, chauffeur, départ, destination
- Polyligne de route
- Mode `fullScreen` + `onTap` (réservation)

### Client
| Écran | Carte |
|-------|-------|
| Home | Plein écran + position utilisateur |
| Booking | Tap départ / destination |
| Active ride | Chauffeur live (WebSocket `DriverLocationUpdated`) + client + destination |

### Chauffeur
| Écran | Carte |
|-------|-------|
| Active ride | GPS chauffeur + client (pickup) + destination + route |

Polling REST + WebSocket conservés (Sprint 02).
