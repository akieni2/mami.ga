# MAMI GIS — Spécification API REST

**Version** : 1.0 (document de conception — **aucun code**)  
**Date** : juin 2026  
**Base URL** : `https://mami.ga/api` (prod)  
**Auth** : `Authorization: Bearer {sanctum_token}`  
**Flags requis** : `MAMI_MODULE_MUNICIPALITY=true`, `MAMI_MODULE_GIS=true`

---

## 1. Conventions

| Élément | Convention |
|---------|------------|
| Format | JSON |
| Enveloppe succès | `{ "success": true, "data": {...}, "message": "..." }` |
| Enveloppe erreur | `{ "success": false, "message": "...", "errors": {...} }` |
| Pagination | `?page=1&per_page=25` → `meta: { current_page, last_page, total }` |
| Géo filtre | `?bbox=sw_lat,sw_lng,ne_lat,ne_lng` |
| Filtre quartier | `?sector_id=` ou `?quartier=` |
| Middleware | `auth:sanctum`, `module:municipality` ou `module:gis` |

---

## 2. Feature flags

### `GET /app/features` (existant — extension)

**Réponse additionnelle** :

```json
{
  "data": {
    "modules": {
      "municipality": true,
      "gis": true,
      "taxi": true
    }
  }
}
```

---

## 3. Module GIS — Carte centrale

Prefix : `/api/gis`  
Middleware : `auth:sanctum`, `module:gis`, `module:municipality`

### 3.1 Couches disponibles

#### `GET /gis/layers`

Liste des couches activables.

**Réponse** :

```json
{
  "data": [
    {
      "slug": "citizen_reports",
      "name": "Signalements",
      "default_visible": true,
      "style": { "nouveau": "#E53935", "en_cours": "#FB8C00", "resolu": "#43A047" }
    }
  ]
}
```

---

### 3.2 Agrégat carte (endpoint principal dashboard)

#### `GET /gis/map`

**Query** :

| Param | Type | Description |
|-------|------|-------------|
| bbox | string | Bounding box obligatoire pour perf |
| layers | string | `citizen_reports,economic_operators,transport` |
| sector_id | int | Filtre quartier |
| economic_zone_id | int | Filtre zone économique (marché, port, industrie, commerce) |
| tax_status | string | Filtre fiscal (economic_operators) |
| report_status | string | Filtre signalements |
| category_id | int | Filtre catégorie opérateur |

**Réponse** :

```json
{
  "data": {
    "bbox": { "sw": { "lat": 0.28, "lng": 9.48 }, "ne": { "lat": 0.32, "lng": 9.52 } },
    "layers": {
      "citizen_reports": {
        "type": "FeatureCollection",
        "features": [
          {
            "id": 42,
            "type": "Feature",
            "geometry": { "type": "Point", "coordinates": [9.4673, 0.4162] },
            "properties": {
              "public_reference": "OWE-SIG-000042",
              "category": "voirie",
              "status": "nouveau",
              "title": "Nid de poule",
              "color": "#E53935"
            }
          }
        ]
      },
      "economic_operators": {
        "type": "FeatureCollection",
        "features": []
      },
      "transport": {
        "type": "FeatureCollection",
        "features": [
          {
            "properties": {
              "driver_id": 7,
              "status": "available",
              "color": "#43A047"
            }
          }
        ]
      }
    },
    "stats": {
      "citizen_reports_open": 12,
      "economic_operators_total": 340,
      "drivers_online": 8
    }
  }
}
```

> Format GeoJSON pour compatibilité Leaflet / flutter_map.

---

### 3.3 Recherche territoriale

#### `GET /gis/search`

| Param | Description |
|-------|-------------|
| q | Texte (nom commerce, référence signalement, OWE-COM-xxx) |
| types | `operator,report,facility` |

---

### 3.4 Équipements municipaux

#### `GET /gis/facilities`

CRUD admin : voir §5.4.

---

## 4. Module Municipality — Signalements citoyens

Prefix : `/api/municipality`  
Middleware : `auth:sanctum`, `module:municipality`

### 4.1 Créer un signalement (citoyen)

#### `POST /municipality/reports`

**Permission** : `municipality.reports.create` (citoyen)

**Body** :

```json
{
  "category": "voirie",
  "title": "Nid de poule avenue du Port",
  "description": "Trou profond dangereux",
  "latitude": 0.4162,
  "longitude": 9.4673,
  "quartier": "Centre-ville"
}
```

**Réponse** : `201` + `public_reference`, `status: nouveau`

**Events** : `CitizenReportCreated` → Reverb

---

### 4.2 Lister / filtrer (agent)

#### `GET /municipality/reports`

| Param | Description |
|-------|-------------|
| status | nouveau, assigne, en_cours, resolu, cloture |
| category | |
| sector_id | |
| mine | `true` — signalements du citoyen connecté |

---

### 4.3 Détail

#### `GET /municipality/reports/{id}`

Inclut : `attachments`, historique statuts (`audit_logs`), interventions liées.

---

### 4.4 Changer statut (agent)

#### `POST /municipality/reports/{id}/transition`

**Body** :

```json
{
  "status": "en_cours",
  "assigned_to": 15,
  "assigned_team_id": 2,
  "notes": "Équipe voirie dispatchée"
}
```

**Transitions valides** : voir machine à états dans `MUNICIPALITY_V1_IMPLEMENTATION_PLAN.md`

---

### 4.5 Ajouter photo

#### `POST /municipality/reports/{id}/attachments`

`multipart/form-data` → table `attachments`

---

## 5. Module Municipality — Recensement économique

### 5.1 Catégories

#### `GET /municipality/economic-categories`

---

#### `GET /municipality/economic-zones`

Liste des zones économiques (`marche`, `zone_industrielle`, `zone_portuaire`, `zone_commerciale`).

---

### 5.2 Opérateurs économiques

#### `GET /municipality/operators`

Filtres : `tax_status`, `category_id`, `sector_id`, `economic_zone_id`, `q`, `bbox`

#### `POST /municipality/operators`

**Permission** : `municipality.operators.manage`

**Body** :

```json
{
  "commercial_name": "Boulangerie du Port",
  "activity_label": "Boulangerie",
  "category_id": 3,
  "responsible_name": "Jean Mbina",
  "phone": "+241060000000",
  "latitude": 0.4165,
  "longitude": 9.4680,
  "sector_id": 12,
  "registration_date": "2026-01-15"
}
```

**Réponse** : `public_id: "OWE-COM-000123"` (auto-généré)

#### `GET /municipality/operators/{id}`

#### `PUT /municipality/operators/{id}`

#### `GET /municipality/operators/{id}/tax-history`

Retourne `economic_operator_tax_status` chronologique.

---

### 5.3 Revenus municipaux

#### `POST /municipality/operators/{id}/revenues`

Enregistrement encaissement terrain ou backoffice.

#### `GET /municipality/revenues`

Agrégats par période.

---

## 6. Module Municipality — Brigades terrain

### 6.1 Équipes

#### `GET /municipality/field-teams`

#### `POST /municipality/field-teams` (admin)

### 6.2 Interventions

#### `GET /municipality/interventions`

Filtre : `team_id`, `agent_user_id`, `date`, `status`

#### `POST /municipality/interventions`

**Body** :

```json
{
  "field_team_id": 2,
  "intervention_type": "fiscal_visit",
  "economic_operator_id": 45,
  "latitude": 0.4162,
  "longitude": 9.4673,
  "accuracy_meters": 12,
  "report_text": "Visite effectuée, paiement partiel reçu",
  "recovery_visit_id": 8
}
```

#### `POST /municipality/interventions/{id}/submit`

Valide GPS + passe `status: submitted` → `audit_logs`

#### `POST /municipality/interventions/{id}/attachments`

Photos constat.

---

## 7. Module Fiscal Recovery

Prefix : `/api/municipality/recovery`  
Voir aussi `FISCAL_RECOVERY_MODULE_SPEC.md`

### 7.1 Dashboard

#### `GET /municipality/recovery/dashboard`

```json
{
  "data": {
    "operators_total": 450,
    "operators_compliant": 280,
    "operators_overdue": 120,
    "operators_unregistered": 50,
    "collected_month": 12500000,
    "pending_amount": 8300000,
    "priority_zones": [
      { "sector_id": 5, "name": "Quartier X", "overdue_count": 34, "priority_score": 0.92 }
    ]
  }
}
```

### 7.2 Campagnes

#### `GET /municipality/recovery/campaigns`

#### `POST /municipality/recovery/campaigns`

#### `GET /municipality/recovery/campaigns/{id}/map`

Opérateurs ciblés géolocalisés pour la campagne.

### 7.3 Visites planifiées

#### `GET /municipality/recovery/campaigns/{id}/visits`

#### `POST /municipality/recovery/visits/{id}/complete`

---

## 8. Tableau de bord Maire

#### `GET /municipality/mayor/dashboard`

**Permission** : `municipality.dashboard.view`

```json
{
  "data": {
    "reports": { "open": 45, "resolved_month": 120 },
    "operators": { "registered": 450, "non_compliant": 170 },
    "revenue": { "month": 12500000, "year": 98000000 },
    "field_teams": { "interventions_month": 89, "completion_rate": 0.87 },
    "map_embed_url": "/admin/gis"
  }
}
```

---

## 9. Temps réel (Reverb)

| Event | Canal | Déclencheur |
|-------|-------|-------------|
| `CitizenReportCreated` | `private-municipality-owendo` | Nouveau signalement |
| `CitizenReportStatusChanged` | `private-user-{id}`, `private-municipality-owendo` | Transition statut |
| `EconomicOperatorRegistered` | `private-municipality-owendo` | Nouvel opérateur |
| `TaxStatusChanged` | `private-municipality-owendo` | Changement fiscal |
| `FieldInterventionSubmitted` | `private-field-team-{id}` | Compte rendu brigade |
| `DriverLocationUpdated` | existant Taxi | Couche transport (lecture) |

---

## 10. Codes HTTP

| Code | Usage |
|------|-------|
| 200 | Succès lecture / mise à jour |
| 201 | Création |
| 403 | Module désactivé ou permission insuffisante |
| 404 | Ressource absente |
| 422 | Validation / transition statut invalide |

---

## 11. Compatibilité Taxi

Les endpoints `/api/rides/*` et `/api/drivers/*` **ne sont pas modifiés**.

Le GIS consomme uniquement :
- `GET` interne sur `drivers` (service layer)
- Events Reverb `DriverLocationUpdated` existants

---

## 12. Web Admin (routes Blade — hors API mobile)

| Route | Description |
|-------|-------------|
| `/admin/gis` | Carte SIG interactive centrale |
| `/admin/municipality/operators` | Gestion registre |
| `/admin/municipality/recovery` | Dashboard recouvrement |
| `/admin/municipality/mayor` | Tableau de bord maire |

---

*Spécification API — validation requise avant implémentation.*
