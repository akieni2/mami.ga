# Audit integration HDV Owendo -> MAMI

Date: 2026-08-15

## 1. Constat

Le projet local `C:\Users\LENOVO\hdvowendo` est une application Laravel 8 orientee gestion municipale des taxis, TS, TM, chauffeurs, vehicules, cartes conducteur, QR codes et taxes/payouts. Elle tourne localement sur `http://127.0.0.1:8000/login`.

Le projet MAMI est une plateforme Laravel 13 modulaire avec un module Taxi deja operationnel: candidatures chauffeur, validation admin, creation `drivers` + `vehicles`, roles MAMI, courses, dispatch et supervision.

Les deux applications couvrent donc des perimetres voisins mais pas identiques:

- HDV Owendo: registre municipal officiel, enrÃ´lement, activation, cartes conducteur, numero NP/porte, QR officiel, categories Taxi/TM/TS.
- MAMI: super app municipale, mobilite, citoyens, chauffeurs connectes, courses, fiscalite, modules mairie et supervision.

## 2. Stack comparee

| Sujet | HDV Owendo | MAMI |
|---|---|---|
| Framework | Laravel 8 | Laravel 13 |
| PHP | ^7.3 ou ^8.0 | ^8.3 |
| API auth | `api_token` utilisateur | Sanctum / architecture MAMI |
| QR | `simplesoftwareio/simple-qrcode` | `endroid/qr-code` |
| Permissions | Spatie Permission 4 | Roles/permissions MAMI |
| Donnees taxi | `drivers`, `cars`, `tm_drivers`, `tmcars`, `ts_drivers`, `tscars` | `drivers`, `vehicles`, `driver_applications`, `rides` |

Conclusion: une fusion directe des deux codebases serait risquee. L'approche recommandee est une integration par API ou synchronisation controlee.

## 3. Entites HDV Owendo utiles pour MAMI

### Chauffeurs

Tables principales:

- `drivers` pour Taxi
- `tm_drivers` pour TM
- `ts_drivers` pour TS

Champs utiles identifies dans les modeles:

- identite: `name`, `last_name`, `date_of_birth`, `nationality`
- contact: `phone_number`, `address`, `area_of_residence`
- permis/documents: `license_no`, `category`, `passport_number`, `residence_card_numbe`, `state_identification_number`
- media: `picture`
- cycle: `year`, `position`, `is_activated`, `activated_at`

### Vehicules

Tables principales:

- `cars` pour Taxi
- `tmcars` pour TM
- `tscars` pour TS

Champs utiles identifies:

- `car_brand`, `car_name`
- `license_plate`
- `vin_number`
- `door_number`
- `tag_number`
- `rfid_number`
- `owner_name`
- `unique_number`
- `qr_code`
- `year`, `is_activated`, `activated_at`

### Cartes et QR

HDV Owendo sait deja generer des cartes conducteur avec photo, QR personnel, numero conducteur et numero de portiere. Il existe aussi un historique `driver_card_print_histories`.

## 4. Points de recouvrement avec MAMI

MAMI possede deja:

- `driver_applications`: candidature chauffeur depuis mobile/API
- `DriverEnrollmentService`: approbation, creation chauffeur, creation vehicule, attribution role `taxi_driver`
- `drivers`: chauffeur actif MAMI, GPS, disponibilite, statut course
- `vehicles`: vehicule lie a un chauffeur

HDV Owendo possede une logique plus officielle d'enrÃ´lement municipal:

- activation par categorie et annee
- affectation a un vehicule officiel
- generation de carte
- QR de controle
- numero de portiere / NP

## 5. Recommandation d'architecture

Ne pas remplacer MAMI Taxi par HDV Owendo.

Creer un module d'integration MAMI, par exemple `App\Modules\ExternalRegistry` ou `App\Modules\HdvOwendo`, avec trois objectifs:

1. Referencer le chauffeur MAMI vers son identifiant officiel HDV Owendo.
2. Synchroniser les donnees officielles utiles: categorie, numero NP/porte, statut d'activation, QR officiel, vehicule.
3. Permettre a l'admin MAMI de voir si un chauffeur est officiellement enrÃ´le a Owendo avant de l'autoriser pleinement sur MAMI.

## 6. Modele de donnees propose cote MAMI

Table satellite conseillee: `external_driver_registrations`.

Champs proposes:

- `id`
- `source` = `hdvowendo`
- `source_driver_type` = `taxi`, `tm`, `ts`
- `source_driver_id`
- `source_vehicle_id`
- `mami_driver_id` nullable
- `mami_user_id` nullable
- `license_number`
- `plate_number`
- `door_number`
- `qr_url`
- `official_status` = `pending`, `active`, `inactive`, `rejected`
- `activated_at`
- `last_synced_at`
- `raw_payload` JSON
- timestamps

Ce modele evite de modifier directement les tables Taxi historiques de MAMI.

## 7. Integration fonctionnelle proposee

### Phase A - Lecture seule

- Ajouter une configuration MAMI: `HDVOWENDO_BASE_URL`, `HDVOWENDO_API_TOKEN`, `HDVOWENDO_ENABLED`.
- Creer un client HTTP interne MAMI pour interroger HDV Owendo.
- Ajouter une commande `php artisan hdvowendo:sync-drivers`.
- Importer en lecture seule les chauffeurs/vehicules actifs.
- Afficher un badge dans l'admin MAMI: "Enrole HDV Owendo", "Non trouve", "A verifier".

### Phase B - Liaison candidature MAMI

- Lorsqu'une candidature chauffeur MAMI arrive, chercher dans HDV Owendo par permis, telephone ou plaque.
- Si trouve et actif: pre-remplir les donnees officielles et accelerer la validation.
- Si non trouve: garder la candidature MAMI mais afficher "enrÃ´lement municipal manquant".

### Phase C - Workflow officiel

- Ajouter un bouton admin: "Ouvrir fiche HDV Owendo".
- Ajouter un lien entre le QR officiel HDV Owendo et le profil chauffeur MAMI.
- Optionnel: exposer depuis HDV Owendo une API propre `/api/integration/v1/drivers`.

### Phase D - Unification progressive

- Decider si HDV Owendo reste systeme maitre pour l'enrÃ´lement officiel.
- MAMI consomme le statut officiel et gere les services digitaux: courses, signalements, paiements, recouvrement, tableau de bord.

## 8. Points d'attention

- Le repo `hdvowendo` contient beaucoup de modifications locales non commitees. Ne pas ecraser.
- Certaines migrations anciennes ne refletent pas tout le schema reel; il faut auditer la base locale avant tout script de migration.
- L'API actuelle HDV Owendo expose beaucoup de donnees et utilise `api_token`; il faut creer une API d'integration plus limitee avant production.
- Les categories HDV Owendo (`Taxi`, `Tm`, `Ts`) ne doivent pas etre confondues avec les profils MAMI (`taxi_driver`, futurs modules TM/transport).
- Les donnees personnelles et photos doivent rester protegees: pas de synchronisation brute inutile.

## 9. Decision recommandee

Pour une premiere version, faire une integration lecture seule depuis MAMI vers HDV Owendo:

- HDV Owendo reste registre officiel d'enrÃ´lement.
- MAMI affiche et exploite le statut officiel.
- Aucune fusion de code.
- Aucune modification destructive des tables Taxi.

Cette approche apporte vite de la valeur et garde les deux systemes stables pendant la transition.

