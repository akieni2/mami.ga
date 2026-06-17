# MAMI — Référentiel territorial communal d'Owendo

**Version** : 1.1 (validé en principe — **aucun code**)  
**Date** : juin 2026  
**Commune** : Owendo, province de l'Estuaire, département du Komo-Mondah  
**Références** : `GIS_DATABASE_DESIGN.md`, `GIS_ARCHITECTURE.md`, Journal Officiel PR 2018 (découpage électoral)

> **Statut** : référentiel de travail V1 — centroids GPS et polygones marqués *à affiner* doivent être validés par la Direction des Services Techniques de la Mairie d'Owendo avant seed production.

---

## 1. Structure territoriale d'Owendo

### 1.1 Hiérarchie administrative

```
République Gabonaise
└── Province de l'Estuaire
    └── Département du Komo-Mondah
        └── Commune d'Owendo  [code OWE]
            ├── 1er arrondissement  [OWE-ARR-01]
            │   └── 8 quartiers
            ├── 2e arrondissement  [OWE-ARR-02]
            │   └── 5 quartiers
            ├── 4 zones opérationnelles brigades  [OWE-ZOP-01 … 04]
            │   └── rattachement transversal aux quartiers
            └── N zones économiques  [OWE-ZEC-NN]
                ├── Marchés
                ├── Zones industrielles
                ├── Zones portuaires
                └── Zones commerciales
```

**Chaîne de rattachement opérateur économique** (résolution automatique à l'enregistrement) :

```
Commune (OWE)
  → Arrondissement (OWE-ARR-NN)     … via parent_id du quartier
  → Quartier (OWE-Q-NNN)            … GPS §7
  → Zone opérationnelle (OWE-ZOP-NN) … matrice §4.1
  → Zone économique (OWE-ZEC-NN)    … GPS §7.5 + type d'activité
```

### 1.2 Modèle de données (alignement `municipal_sectors`)

| Niveau | `sector_type` | `parent_id` | Table |
|--------|---------------|-------------|-------|
| Commune | — | — | `municipal_territories` (`code = OWE`) |
| Arrondissement | `secteur` | `territory_id` | `municipal_sectors` |
| Quartier | `quartier` | arrondissement | `municipal_sectors` |
| Zone opérationnelle | `zone` | `territory_id` | `municipal_sectors` |
| Zone économique | — | `territory_id` | `economic_zones` (table dédiée) |

### 1.3 Enveloppe géographique commune (bbox V1)

| Point | Latitude | Longitude |
|-------|----------|-----------|
| Sud-Ouest (SW) | 0.2750 | 9.4450 |
| Nord-Est (NE) | 0.3650 | 9.5250 |
| **Centroïde administratif** | **0.3408** | **9.4822** |

Limites naturelles (référence cartographique) :
- **Ouest / Sud** : estuaire du Gabon  
- **Nord** : rivière Lowé  
- **Est** : rivière Angoumé  

### 1.4 Principes de gouvernance territoriale MAMI

| # | Principe |
|---|----------|
| T1 | Un objet géolocalisé possède **toujours** `latitude` + `longitude` |
| T2 | Le rattachement quartier/secteur est **calculé** à partir du GPS (règles §7–8), pas saisi librement par le citoyen |
| T3 | Le champ texte `quartier` n'est utilisé qu'en **secours** si le GPS est absent ou hors commune |
| T4 | Les zones opérationnelles servent au **pilotage des brigades**, pas au découpage électoral |
| T5 | Toute modification du référentiel est tracée dans `audit_logs` |
| T6 | Chaque opérateur économique est rattaché à la **chaîne complète** commune → zone économique (§5) |
| T7 | Le modèle prévoit dès V1 les emplacements `parcel_reference` et `land_parcels` pour le cadastre futur (§13) |

---

## 2. Quartiers

Source officielle : décret de répartition des sièges de députés — commune d'Owendo (Journal Officiel, 2018).  
**13 quartiers reconnus V1** (8 + 5).

### 2.1 1er arrondissement — `OWE-ARR-01`

| Code | Nom officiel | Slug | Centroïde V1 (*à affiner*) | Lat | Lng |
|------|--------------|------|----------------------------|-----|-----|
| `OWE-Q-001` | Akournam 1 | `akournam-1` | Nord-est résidentiel | 0.3520 | 9.4980 |
| `OWE-Q-002` | Cité SNI | `cite-sni` | Carrefour SNI (cœur communal) | 0.3380 | 9.4710 |
| `OWE-Q-003` | Cité OCTRA | `cite-octra` | Zone OCTRA / logements | 0.3310 | 9.4780 |
| `OWE-Q-004` | Awoungou | `awoungou` | Quartier Awoungou | 0.3180 | 9.4650 |
| `OWE-Q-005` | Service Civique | `service-civique` | Ancien camp / zone civique | 0.3250 | 9.4720 |
| `OWE-Q-006` | Alénakiri | `alenakiri` | Zone portuaire nord | 0.3050 | 9.4580 |
| `OWE-Q-007` | Owendo Port | `owendo-port` | Port marchand / industriel | 0.2920 | 9.4520 |
| `OWE-Q-008` | Virié | `virie` | Virié 1 / zone aéroport | 0.3280 | 9.4880 |

### 2.2 2e arrondissement — `OWE-ARR-02`

| Code | Nom officiel | Slug | Centroïde V1 (*à affiner*) | Lat | Lng |
|------|--------------|------|----------------------------|-----|-----|
| `OWE-Q-009` | Akournam 2 | `akournam-2` | Extension Akournam | 0.3480 | 9.5050 |
| `OWE-Q-010` | Igoumié | `igoumie` | Igoumié | 0.3350 | 9.5100 |
| `OWE-Q-011` | Mbila-Nyambi | `mbila-nyambi` | Mbila-Nyambi | 0.3220 | 9.5020 |
| `OWE-Q-012` | Pointe Claire | `pointe-claire` | Littoral est | 0.3100 | 9.5150 |
| `OWE-Q-013` | Île Coniquet | `ile-coniquet` | Île / zone insulaire | 0.2980 | 9.5200 |

### 2.3 Quartiers complémentaires (hors liste électorale — validation mairie)

Mentionnés dans les sources locales et usages MAMI Taxi ; **non activés V1** tant que non validés :

| Nom usuel | Contexte MAMI | Action V1 |
|-----------|---------------|-----------|
| Cité COMILOG | Cité historique industrielle | Mapper vers `cite-octra` ou créer `OWE-Q-014` post-validation |
| Aérodrome | Proximité Virié / régularisation foncière 2026 | Rattacher à `virie` ou quartier dédié V1.1 |
| Carrefour STFO | Repère taxi (`pickup_label`) | Point d'intérêt `gis_features`, pas quartier autonome |
| Centre administratif | Mairie (Base Socoba) | Point `gis_features` type `admin_building` |

### 2.4 Repères géographiques clés (points d'intérêt)

| Repère | Usage | Lat approx. | Lng approx. |
|--------|-------|-------------|-------------|
| Carrefour SNI | Hub commercial, dispatch taxi | 0.3380 | 9.4710 |
| Gare ferroviaire Owendo | Transport | 0.3410 | 9.4830 |
| Port d'Owendo | Zone industrielle / logistique | 0.2920 | 9.4520 |
| Mairie d'Owendo (Base Socoba) | Administration | 0.3390 | 9.4790 |
| CHUO Owendo | Santé publique | 0.3360 | 9.4750 |

---

## 3. Secteurs (arrondissements)

Les **secteurs** au sens MAMI correspondent aux **2 arrondissements communaux** officiels.

| Code | Nom | Slug | Quartiers rattachés | Sièges conseil |
|------|-----|------|---------------------|----------------|
| `OWE-ARR-01` | 1er arrondissement | `arrondissement-1` | Q-001 à Q-008 (8) | 1 |
| `OWE-ARR-02` | 2e arrondissement | `arrondissement-2` | Q-009 à Q-013 (5) | 1 |

**Rôle dans MAMI** :
- Agrégation statistique (signalements / fiscalité par arrondissement)
- Filtre carte SIG niveau « macro »
- Reporting dashboard maire (comparaison Est vs Ouest communal)

---

## 4. Zones opérationnelles

Les **zones opérationnelles** (`sector_type = zone`) découpent la commune pour le **pilotage terrain** : brigades, campagnes de recouvrement, priorisation SIG.  
Elles ne remplacent pas les quartiers officiels ; chaque quartier appartient à **exactement une** zone opérationnelle.

| Code | Nom | Slug | Quartiers couverts | Profil territorial |
|------|-----|------|--------------------|--------------------|
| `OWE-ZOP-01` | Zone Port & Industrie | `zop-port-industrie` | Q-006, Q-007, Q-004, Q-003 | Port, OPRAG, activité logistique |
| `OWE-ZOP-02` | Zone Centre & SNI | `zop-centre-sni` | Q-002, Q-005, Q-008 | Cœur économique, marchés, admin |
| `OWE-ZOP-03` | Zone Akournam | `zop-akournam` | Q-001, Q-009 | Résidentiel dense nord-est |
| `OWE-ZOP-04` | Zone Littoral Est | `zop-littoral-est` | Q-010, Q-011, Q-012, Q-013 | Littoral, île, extension urbaine |

### 4.1 Matrice quartier → zone opérationnelle

| Quartier | Zone |
|----------|------|
| Akournam 1 | ZOP-03 |
| Cité SNI | ZOP-02 |
| Cité OCTRA | ZOP-01 |
| Awoungou | ZOP-01 |
| Service Civique | ZOP-02 |
| Alénakiri | ZOP-01 |
| Owendo Port | ZOP-01 |
| Virié | ZOP-02 |
| Akournam 2 | ZOP-03 |
| Igoumié | ZOP-04 |
| Mbila-Nyambi | ZOP-04 |
| Pointe Claire | ZOP-04 |
| Île Coniquet | ZOP-04 |

---

## 5. Zones économiques (référentiel économique communal)

Les **zones économiques** qualifient l'espace d'activité au sein de la commune. Elles complètent le découpage administratif et alimentent les **statistiques fiscales** et le **ciblage des brigades**.

### 5.1 Types de zones économiques

| Type | Code `zone_kind` | Description | Exemple Owendo |
|------|------------------|-------------|----------------|
| **Marché** | `marche` | Marché couvert ou de plein vent | Marché Carrefour SNI |
| **Zone industrielle** | `zone_industrielle` | Parc industriel, usines, transformation | Zone industrielle Port / Awoungou |
| **Zone portuaire** | `zone_portuaire` | Domaine OPRAG, quais, logistique portuaire | Port marchand Owendo |
| **Zone commerciale** | `zone_commerciale` | Concentration boutiques, restaurants, services | Axe commercial Cité SNI |

### 5.2 Catalogue V1 (à affiner par la mairie)

| Code | Nom | Type | ZOP | Quartier(s) | Centroïde V1 |
|------|-----|------|-----|-------------|--------------|
| `OWE-ZEC-01` | Marché Carrefour SNI | `marche` | ZOP-02 | Q-002 | 0.3375, 9.4705 |
| `OWE-ZEC-02` | Marché Port | `marche` | ZOP-01 | Q-007 | 0.2930, 9.4530 |
| `OWE-ZEC-03` | Zone portuaire OPRAG | `zone_portuaire` | ZOP-01 | Q-006, Q-007 | 0.2950, 9.4510 |
| `OWE-ZEC-04` | Zone industrielle Port | `zone_industrielle` | ZOP-01 | Q-007 | 0.2900, 9.4500 |
| `OWE-ZEC-05` | Zone industrielle Awoungou | `zone_industrielle` | ZOP-01 | Q-004 | 0.3170, 9.4640 |
| `OWE-ZEC-06` | Zone commerciale SNI | `zone_commerciale` | ZOP-02 | Q-002 | 0.3385, 9.4715 |
| `OWE-ZEC-07` | Zone commerciale Service Civique | `zone_commerciale` | ZOP-02 | Q-005 | 0.3245, 9.4715 |
| `OWE-ZEC-08` | Zone commerciale Akournam | `zone_commerciale` | ZOP-03 | Q-001, Q-009 | 0.3500, 9.5010 |

> Une zone économique peut couvrir **plusieurs quartiers** ; un opérateur n'appartient qu'à **une** zone économique principale.

### 5.3 Rattachement opérateur économique

À la création ou mise à jour d'un `economic_operator` :

| Étape | Champ résolu | Méthode |
|-------|--------------|---------|
| 1 | `territory_id` | Toujours `OWE` |
| 2 | `sector_id` (quartier) | GPS → quartier (§7) |
| 3 | Arrondissement | `municipal_sectors.parent_id` du quartier |
| 4 | `operational_zone_id` | Matrice quartier → ZOP (§4.1) |
| 5 | `economic_zone_id` | GPS → zone économique (§7.5) ; secours : `category_id` + quartier |
| 6 | `parcel_reference` | **V5 cadastre** — NULL en V1–V4 |

Champs dénormalisés sur `economic_operators` pour requêtes fiscales rapides :
`sector_id`, `operational_zone_id`, `economic_zone_id`, `secteur` (libellé arrondissement).

### 5.4 Usage fiscal et brigades

| Besoin | Granularité zone économique |
|--------|---------------------------|
| Taux de recouvrement par marché | `zone_kind = marche` |
| Ciblage industrie / port | `zone_industrielle`, `zone_portuaire` |
| Campagne brigade BRG-01 | Opérateurs ZEC-03, ZEC-04, ZEC-05 |
| Carte fiscalité | Filtre `economic_zone_id` + couleur statut fiscal |
| KPI maire | Top zones économiques en retard > 90 jours |

---

## 6. Brigades associées

**4 brigades municipales V1**, une par zone opérationnelle.

| Code brigade | Nom | Zone | `assigned_sectors` (sector_id[]) | Chef (rôle) |
|--------------|-----|------|----------------------------------|-------------|
| `OWE-BRG-01` | Brigade Port & Industrie | ZOP-01 | quartiers Q-003, Q-004, Q-006, Q-007 | `field_team_leader` |
| `OWE-BRG-02` | Brigade Centre & SNI | ZOP-02 | quartiers Q-002, Q-005, Q-008 | `field_team_leader` |
| `OWE-BRG-03` | Brigade Akournam | ZOP-03 | quartiers Q-001, Q-009 | `field_team_leader` |
| `OWE-BRG-04` | Brigade Littoral Est | ZOP-04 | quartiers Q-010, Q-011, Q-012, Q-013 | `field_team_leader` |

### 6.1 Missions par brigade

| Brigade | Signalements prioritaires | Fiscalité prioritaire |
|---------|---------------------------|----------------------|
| BRG-01 | Voirie portuaire, déchets industriels, inondations estuaire | Opérateurs port / entrepôts |
| BRG-02 | Éclairage public, marchés, voirie centre | Commerces Carrefour SNI, PMI centre |
| BRG-03 | Voirie résidentielle, sécurité de quartier | Boutiques Akournam |
| BRG-04 | Environnement littoral, inondations | Pêche, activités littorales |

### 6.2 Règles d'assignation automatique

Lorsqu'un signalement ou une visite fiscale est créé :

1. Résoudre `sector_id` (quartier) via GPS (§7)  
2. Déduire la zone opérationnelle (§4.1)  
3. Assigner `field_team_id` = brigade de la zone  
4. Si brigade inactive → file d'attente `assigned_to` agent municipal de l'arrondissement

---

## 7. Codification unique

### 7.1 Schéma général

```
OWE-{TYPE}-{NNNN}
```

| Préfixe | Type | Exemple | Portée |
|---------|------|---------|--------|
| `OWE` | Commune | `OWE` | `municipal_territories.code` |
| `OWE-ARR-NN` | Arrondissement | `OWE-ARR-01` | Secteur administratif |
| `OWE-Q-NNN` | Quartier | `OWE-Q-002` | Quartier officiel |
| `OWE-ZEC-NN` | Zone économique | `OWE-ZEC-06` | `economic_zones` |
| `OWE-ZOP-NN` | Zone opérationnelle | `OWE-ZOP-02` | Brigade / campagne |
| `OWE-PAR-NNNNNN` | Parcelle cadastrale *(V5)* | `OWE-PAR-000042` | `land_parcels.parcel_reference` |
| `OWE-BRG-NN` | Brigade | `OWE-BRG-02` | `field_teams` |
| `OWE-COM-NNNNNN` | Opérateur économique | `OWE-COM-000042` | `economic_operators.public_id` |
| `OWE-SIG-NNNNNN` | Signalement citoyen | `OWE-SIG-000015` | `citizen_reports.public_reference` |
| `OWE-RCV-NNNNNN` | Campagne recouvrement | `OWE-RCV-000003` | `recovery_campaigns` (proposé) |
| `OWE-INT-NNNNNN` | Intervention terrain | `OWE-INT-000128` | `field_interventions` (proposé) |

### 7.2 Slugs URL / API

| Entité | Convention | Exemple |
|--------|------------|---------|
| Quartier | kebab-case FR | `cite-sni` |
| Filtre API | `sector_id` (PK) ou `?quartier=cite-sni` | `GET /gis/map?sector_id=2` |
| Export CSV | code officiel | `OWE-Q-002` |

### 7.3 Unicité et séquence

- Les séquences `OWE-COM-*` et `OWE-SIG-*` sont **auto-incrémentées** par commune (pas par quartier).  
- Le code quartier est **immuable** après création ; renommage affiché via `name` uniquement.  
- Un opérateur ne change de quartier ou de zone économique que si son GPS est recalé et validé par un agent (`audit_logs`).

---

## 8. Règles de rattachement GPS → Quartier

Service cible : `TerritorialResolverService` (module GIS / Municipality).

### 8.1 Algorithme V1 (centroïde + rayon)

```
ENTRÉE : latitude, longitude
SORTIE : sector_id (quartier), confidence_score, method
```

| Étape | Règle |
|-------|-------|
| 1 | Vérifier que le point est **dans la bbox commune** (§1.3). Hors bbox → `sector_id = NULL`, flag `out_of_territory` |
| 2 | Calculer distance haversine vers le centroïde de chaque quartier (§2) |
| 3 | Sélectionner le quartier au **distance minimale** |
| 4 | Si distance ≤ **1 500 m** → rattachement **confirmé** (`confidence = high`) |
| 5 | Si 1 500 m < distance ≤ **3 000 m** → rattachement **probable** (`confidence = medium`) + revue agent optionnelle |
| 6 | Si distance > **3 000 m** → `sector_id = NULL`, conserver GPS, remplir `quartier` texte si fourni par l'utilisateur |

**Paramètres configurables** : `config/municipality.php` → `territory.quartier_max_distance_m`

### 8.2 Algorithme V2 (polygones — post-validation mairie)

| Étape | Règle |
|-------|-------|
| 1 | Point-in-polygon sur `municipal_sectors.polygon_geojson` |
| 2 | Si plusieurs polygones → priorité au plus petit périmètre (quartier le plus spécifique) |
| 3 | Si aucun polygone → repli algorithme V1 |

### 8.3 Cas particuliers

| Cas | Traitement |
|-----|------------|
| GPS absent (signalement texte seul) | `sector_id = NULL` ; matching fuzzy sur `quartier` texte vs noms officiels |
| GPS imprécis (`accuracy_meters` > 100) | Rattachement V1 + `confidence = low` |
| Point sur frontière estuaire | Priorité quartiers littoraux (Q-007, Q-012, Q-013) |
| Île Coniquet | Si lat/lng dans enveloppe insulaire → forcer Q-013 |

### 8.4 Champs persistés

| Table | Champs |
|-------|--------|
| `citizen_reports` | `latitude`, `longitude`, `sector_id`, `quartier` (secours) |
| `economic_operators` | idem + `operational_zone_id`, `economic_zone_id`, `secteur`, `parcel_reference` (V5) |
| `field_interventions` | GPS constat ; quartier recalculé à la soumission |
| `locations` | Historique ; `metadata.sector_id` à chaque snapshot |

---

## 9. Règles de rattachement GPS → Secteur (arrondissement)

Le secteur (arrondissement) est **dérivé** du quartier — jamais calculé indépendamment en V1.

```
sector_arrondissement_id = municipal_sectors.parent_id
WHERE municipal_sectors.id = quartier_resolu
  AND municipal_sectors.sector_type = 'quartier'
```

| Règle | Détail |
|-------|--------|
| S1 | Un quartier a **un seul** parent arrondissement |
| S2 | `economic_operators.secteur` = libellé lisible (« 1er arrondissement ») copié à l'enregistrement |
| S3 | Filtrage API `?arrondissement=OWE-ARR-01` → tous les `sector_id` enfants de ARR-01 |
| S4 | Statistiques maire : agrégation par `parent_id` des quartiers |

**Zone opérationnelle** : dérivée via matrice §4.1.  
**Zone économique** : résolution dédiée §10.

---

## 10. Règles de rattachement GPS → Zone économique

Applicable aux **opérateurs économiques** et, en V5, aux **parcelles cadastrales**.

### 10.1 Algorithme V1

| Étape | Règle |
|-------|-------|
| 1 | Quartier déjà résolu (§8) |
| 2 | Filtrer `economic_zones` actives du même `territory_id`, priorité celles liées au quartier ou à la ZOP |
| 3 | Point-in-polygon si `polygon_geojson` présent ; sinon distance au centroïde |
| 4 | Si plusieurs zones → priorité par **spécificité** : `marche` > `zone_commerciale` > `zone_portuaire` > `zone_industrielle` |
| 5 | Si aucune zone à ≤ **500 m** → inférence par `category_id` (ex. catégorie `marche` → ZEC marché du quartier) |
| 6 | Persister `economic_zone_id` + `operational_zone_id` sur l'opérateur |

### 10.2 Secours métier

| `category_id` | Zone économique suggérée |
|---------------|--------------------------|
| `marche` | ZEC type `marche` la plus proche |
| `pme`, `pmi`, `atelier` | `zone_industrielle` ou `zone_commerciale` selon distance |
| `boutique`, `restaurant` | `zone_commerciale` |
| Activité portuaire / logistique | `zone_portuaire` |

---

## 11. Utilisation par module

### 11.1 Signalements citoyens

| Besoin | Référentiel utilisé |
|--------|---------------------|
| Création mobile | GPS device → quartier (§8) → brigade (§6.2) |
| Filtre carte | `sector_id`, catégorie, statut |
| Assignation agent | Quartier + zone → `assigned_team_id` |
| Statistiques | Comptage par quartier / arrondissement / zone |
| Couleur carte | Par **statut** signalement (pas par quartier) |

**API** : `POST /municipality/reports` — voir `GIS_API_SPECIFICATION.md`

### 11.2 Commerces & opérateurs économiques

| Besoin | Référentiel utilisé |
|--------|---------------------|
| Enregistrement terrain | GPS → chaîne complète §5.3 + `OWE-COM-*` |
| Filtre carte commerces | `category_id`, `sector_id`, `economic_zone_id`, `tax_status` |
| Import CSV initial | Colonnes `quartier_nom`, `zone_economique_code` ; GPS recalculé si présent |
| Registre numérique | `public_id` + rattachement territorial ; `parcel_reference` réservé V5 |

### 11.3 Fiscalité & recouvrement

| Besoin | Référentiel utilisé |
|--------|---------------------|
| Score zone prioritaire | ZOP (§4) + **zone économique** (§5) |
| Campagne recouvrement | `target_sectors[]` et/ou `target_economic_zone_ids[]` |
| Visites brigade | Opérateurs par brigade + filtre ZEC (marchés, port…) |
| Carte fiscalité | Couleur statut fiscal ; contour par ZEC |
| KPI dashboard | Retard > 90j par quartier, ZOP et **zone économique** |

Voir `FISCAL_RECOVERY_MODULE_SPEC.md`.

### 11.4 Transport MAMI (couche lecture seule)

| Besoin | Référentiel utilisé |
|--------|---------------------|
| Position taxi (`drivers`) | GPS live → quartier calculé **à la volée** (non stocké sur `drivers`) |
| Analyse couverture | Densité chauffeurs connectés par quartier / zone |
| Supervision maire | Taxis disponibles vs occupés par ZOP |
| Corrélation signalements | Ex. voirie Q-002 + faible couverture taxi ZOP-02 |

**Contrainte** : aucune colonne ajoutée sur table `drivers` — résolution territoriale côté `GisLayerAggregatorService` uniquement.

### 11.5 Statistiques & reporting

| Indicateur | Granularité |
|------------|-------------|
| Signalements ouverts / résolus | Quartier, arrondissement, zone, commune |
| Opérateurs enregistrés | Quartier, catégorie, **zone économique** |
| Taux recouvrement | ZOP, ZEC, campagne |
| Interventions brigade | Brigade, quartier, période |
| Densité économique | Quartier (opérateurs / km² approximatif V2) |

Exports : CSV avec codes `OWE-Q-*` + libellés FR.

### 11.6 Dashboard Maire

| Widget | Source territoriale |
|--------|---------------------|
| Carte SIG temps réel | Toutes couches + filtre arrondissement / quartier |
| Top 5 quartiers signalements | Agrégation `citizen_reports` par `sector_id` |
| Zones fiscales rouges | `priority_score` par ZOP et par ZEC |
| Performance brigades | `field_interventions` par `OWE-BRG-*` |
| Couverture transport | Chauffeurs online par quartier (calcul live) |

**API** : `GET /municipality/mayor/dashboard`

---

## 12. Seeder & validation

### 12.1 `OwendoTerritorySeeder` (prévu)

Ordre d'insertion :
1. `municipal_territories` — Owendo (`OWE`)  
2. `municipal_sectors` — 2 arrondissements  
3. `municipal_sectors` — 13 quartiers (`parent_id`)  
4. `municipal_sectors` — 4 zones opérationnelles  
5. `economic_zones` — 8 zones économiques V1 (§5.2)  
6. `field_teams` — 4 brigades (`OWE-BRG-01` … `04`)  
7. Mise à jour `assigned_sectors` JSON sur chaque brigade  

### 12.2 Checklist validation mairie

- [ ] Liste des 13 quartiers confirmée  
- [ ] Centroïdes GPS relevés sur terrain (ou import shapefile)  
- [ ] Polygones quartiers fournis (V2)  
- [ ] Affectation brigades ↔ zones validée par les chefs de service  
- [ ] Quartiers complémentaires (COMILOG, Aérodrome) : créer ou fusionner  
- [ ] Signature Direction des Services Techniques  

- [ ] Zones économiques (marchés, port, industrie, commerce) : périmètres validés  
- [ ] Signature Direction des Services Techniques  

---

## 13. Couche cadastrale future (préparation modèle)

Le SIG municipal est conçu pour accueillir le **cadastre communal** sans refonte des modules V1–V4.

### 13.1 Tables futures (non migrées en V1)

| Table | Rôle | Statut |
|-------|------|--------|
| `land_parcels` | Parcelle cadastrale (géométrie, référence, type) | **V5** |
| `land_parcel_occupations` | Lien parcelle ↔ occupant / opérateur | **V5** |
| `land_concessions` | Concessions et titres fonciers communaux | **V5** |
| `communal_domain_parcels` | Domaine communal / OPRAG / équipements | **V5** |

### 13.2 Champs réservés dès V2 (opérateurs)

| Table | Champ | Usage futur |
|-------|-------|-------------|
| `economic_operators` | `parcel_reference` VARCHAR(50) NULL | Référence lisible `OWE-PAR-*` → `land_parcels` |
| `gis_features` | `parcel_reference` NULL | Équipement sur parcelle |
| `field_interventions` | `parcel_reference` NULL | Constat terrain lié au foncier |

### 13.3 `land_parcels` — spécification cible

| Colonne | Type | Notes |
|---------|------|-------|
| id | BIGINT PK | |
| parcel_reference | VARCHAR(50) UNIQUE | `OWE-PAR-000001` |
| territory_id | FK | Commune |
| sector_id | FK NULL | Quartier |
| economic_zone_id | FK NULL | Zone économique |
| parcel_type | ENUM | concession, occupation, domaine_communal, autre |
| area_sqm | DECIMAL(12,2) NULL | Surface |
| owner_name | VARCHAR(255) NULL | Propriétaire / État |
| occupant_name | VARCHAR(255) NULL | Occupant déclaré |
| polygon_geojson | JSON | Emprise parcelle |
| status | ENUM | active, disputed, regularized, archived |
| metadata | JSON NULL | Références DGI, OPRAG, etc. |
| timestamps | | |

### 13.4 Principes d'extensibilité

| # | Principe |
|---|----------|
| C1 | Les entités V1–V4 restent valides sans parcelle (`parcel_reference` NULL) |
| C2 | L'ajout cadastre = **nouvelles tables + nouvelle couche SIG** `cadastre` |
| C3 | Aucune modification des tables Taxi ni du schéma signalements existant |
| C4 | Un opérateur peut être relié à une parcelle sans changer sa zone économique |
| C5 | Occupations et concessions référencent `parcel_reference`, pas de fusion de tables |

---

## 14. Schéma d'évolution SIG Owendo

| Version | Périmètre | Modules / tables | Interface |
|---------|-----------|------------------|-----------|
| **V1** | Signalements citoyens | `citizen_reports`, couche SIG signalements | Mobile citoyen + carte admin |
| **V2** | Opérateurs économiques | `economic_operators`, `economic_zones`, registre `OWE-COM-*` | Carte économique + admin registre |
| **V3** | Fiscalité | `economic_operator_tax_status`, `municipal_revenues`, `recovery_*` | Dashboard recouvrement |
| **V4** | Brigades | `field_teams`, `field_interventions` | App terrain brigades |
| **V5** | Cadastre communal | `land_parcels`, `parcel_reference`, occupations, concessions | Couche cadastre SIG |
| **V6** | Urbanisme | Permis, zonage PLU communal, occupations irrégulières | Workflow urbanisme |
| **V7** | Aide à la décision Maire | BI agrégé, scénarios, indicateurs prédictifs | Dashboard exécutif avancé |

```mermaid
flowchart LR
    V1[V1 Signalements] --> V2[V2 Opérateurs]
    V2 --> V3[V3 Fiscalité]
    V3 --> V4[V4 Brigades]
    V4 --> V5[V5 Cadastre]
    V5 --> V6[V6 Urbanisme]
    V6 --> V7[V7 Aide décision Maire]
```

> Détail technique et jalons : `GIS_ARCHITECTURE.md` §13, `MUNICIPALITY_V1_IMPLEMENTATION_PLAN.md`

---

## 15. Fichier d'échange (format CSV proposé)

Pour import / mise à jour par la commune :

```csv
code,type,parent_code,name,slug,latitude,longitude,polygon_geojson
OWE,territory,,Owendo,owendo,0.3408,9.4822,
OWE-ARR-01,secteur,OWE,1er arrondissement,arrondissement-1,,
OWE-Q-002,quartier,OWE-ARR-01,Cité SNI,cite-sni,0.3380,9.4710,
OWE-ZOP-02,zone,OWE,Zone Centre & SNI,zop-centre-sni,0.3380,9.4710,
OWE-ZEC-06,economic_zone,OWE-ZOP-02,Zone commerciale SNI,zone-commerciale-sni,zone_commerciale,0.3385,9.4715,
OWE-BRG-02,brigade,OWE-ZOP-02,Brigade Centre & SNI,brigade-centre-sni,,,
```

---

## 16. Documents liés

| Document | Lien |
|----------|------|
| Architecture SIG | `GIS_ARCHITECTURE.md` |
| Schéma BDD | `GIS_DATABASE_DESIGN.md` — §2 référentiels, §11 cadastre futur |
| Évolution V1–V7 | `GIS_ARCHITECTURE.md` §13 |
| API REST | `GIS_API_SPECIFICATION.md` — filtres `sector_id`, `quartier` |
| Plan implémentation | `MUNICIPALITY_V1_IMPLEMENTATION_PLAN.md` — Phase P0.2 |
| Recouvrement fiscal | `FISCAL_RECOVERY_MODULE_SPEC.md` — zones prioritaires |

---

*Référentiel territorial v1.1 — validé en principe. Aucun code avant implémentation.*
