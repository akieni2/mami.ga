# 10. Cartographie SIG fiscale

## 10.1 Mission

Visualiser sur la **carte Owendo** l'état fiscal des commerces : paiements, impayés, visites, campagnes — sans dupliquer les géométries du registre V2.

## 10.2 Fondation existante

Réutilisation directe (voir `GIS_ARCHITECTURE.md`, `GIS_DATABASE_DESIGN.md`) :

| Couche | Source |
|--------|--------|
| Territoire Owendo | `territories` |
| Quartiers | `neighborhoods` |
| ZOP | `zops` |
| Zones économiques | `economic_zones` |
| Opérateurs | `economic_operators.location` (Point) |

Service existant : `LayerEconomicOperators` — **étendre** avec attributs fiscaux.

## 10.3 Nouvelles couches fiscales V3

### 10.3.1 `LayerFiscalStatus` (points)

Chaque opérateur actif = point coloré selon `operator_fiscal_accounts.status` :

| Statut | Couleur | Icône |
|--------|---------|-------|
| `current` | Vert #22c55e | ✓ |
| `overdue` | Rouge #ef4444 | ! |
| `partial` | Orange #f97316 | ◐ |
| `exempt` | Gris #94a3b8 | — |
| `disputed` | Violet #a855f7 | ? |

### 10.3.2 `LayerRecentPayments` (points + halo)

Paiements 7 derniers jours : halo vert pulsé, tooltip montant + date.

### 10.3.3 `LayerFieldVisits` (points)

Visites 30 jours : symbole distinct paiement / refus / absent.

### 10.3.4 `LayerRecoveryHeatmap` (raster / hex)

Densité impayés par zone — agrégation côté serveur (GeoJSON polygons zones économiques avec `recovery_rate`).

## 10.4 API GeoJSON

| Route | Description |
|-------|-------------|
| `GET /gis/fiscal/operators` | Points + properties fiscales |
| `GET /gis/fiscal/payments?from=&to=` | Paiements géolocalisés |
| `GET /gis/fiscal/zones/summary` | Polygones zones + KPI |
| `GET /gis/fiscal/heatmap` | Grille ou hex bins |

### Exemple feature opérateur

```json
{
  "type": "Feature",
  "geometry": { "type": "Point", "coordinates": [9.3456, 0.6543] },
  "properties": {
    "operator_id": 42,
    "public_id": "OWE-COM-000042",
    "business_name": "Boulangerie XYZ",
    "fiscal_status": "overdue",
    "balance_due": 15000,
    "taxes": ["TAX-BOUTIQUE"],
    "last_payment_at": "2026-05-10T11:00:00Z",
    "economic_zone": "Marché Central"
  }
}
```

## 10.5 Filtres carte (UI)

- Statut fiscal (multi-select)
- Période paiements
- Zone économique / quartier
- Agent collecteur
- Campagne brigade (V3.4)
- Montant dû min/max

## 10.6 Mobile agent — mini-carte

Dans workflow QR :
- Position agent (GPS live)
- Position commerce scanné
- Cercle rayon validation 20 m
- Opérateurs impayés proches (V3.1)

## 10.7 Cache et performance

| Couche | Stratégie |
|--------|-----------|
| Opérateurs + statut | Redis 10 min, invalidation on payment |
| Heatmap zones | Recalcul job hourly |
| Paiements récents | Pas de cache (temps réel) |

Index PostGIS : `GIST` sur `economic_operators.location` (existant).

## 10.8 Intégration dashboard Maire

Widget carte embarqué (Leaflet / MapLibre) avec mêmes endpoints GeoJSON.

## 10.9 Offline mobile

Tuiles carte : cache MBTiles secteur agent (option V3.2). Points impayés : snapshot sync matinale GeoJSON léger (max 500 features / secteur).

## 10.10 Non-régression SIG

- Aucune modification schéma `territories`, `neighborhoods` sans migration dédiée SIG
- Couches Taxi (si existantes) sur groupe calque séparé — non affichées mode Municipality
