# Sprint 04B — Enrôlement et validation des chauffeurs

## Architecture

```
Candidat (app mobile, Sanctum)
        │
        ▼ POST /api/driver-applications
DriverEnrollmentService::submit()
        │
        ▼ driver_applications (status: pending)
Admin /admin/driver-applications
        │
   ┌────┴────┐
   ▼         ▼
Approuver   Rejeter (+ rejection_reason)
   │         │
   ▼         ▼
User + Driver + Vehicle   status: rejected
status: approved          + notification + event Reverb-ready
```

### Entités

| Table | Rôle |
|-------|------|
| `driver_applications` | Candidature avec documents et métadonnées véhicule |
| `drivers` | Créé à l'approbation (workflow existant) |
| `vehicles` | Créé à l'approbation (workflow existant) |
| `notifications` | Historique notifications Laravel (mail + database) |

### Fichiers clés

- `app/Models/DriverApplication.php`
- `app/Services/DriverEnrollmentService.php`
- `app/Http/Controllers/Api/DriverApplicationController.php`
- `app/Http/Controllers/Admin/DriverApplicationController.php`
- `app/Notifications/DriverApplication*Notification.php`
- `app/Events/DriverApplicationApproved|Rejected.php` (canal `private-user-{id}`, prêt Reverb)

## Routes API (nouvelles — sans impact sur les routes existantes)

| Méthode | URL | Auth | Description |
|---------|-----|------|-------------|
| `POST` | `/api/driver-applications` | Sanctum | Soumettre candidature + 3 photos |
| `GET` | `/api/driver-applications/status` | Sanctum | Statut de la dernière candidature |

### POST /api/driver-applications

**Content-Type:** `multipart/form-data`

Champs texte : `first_name`, `last_name`, `phone`, `email`, `national_id_number`, `driving_license_number`, `vehicle_brand`, `vehicle_model`, `vehicle_color`, `vehicle_year`, `plate_number`, `vehicle_type` (`sedan|suv|taxi|van|moto`).

Fichiers : `driver_photo`, `license_photo`, `vehicle_photo` (jpg/png, max 5 Mo).

Réponse **201** :

```json
{
  "success": true,
  "message": "Candidature soumise avec succès.",
  "data": { "id": 1, "status": "pending", ... }
}
```

### GET /api/driver-applications/status

```json
{
  "success": true,
  "data": {
    "status": "pending",
    "application": { ... }
  }
}
```

## Routes Admin

| URL | Action |
|-----|--------|
| `/admin/driver-applications` | Liste + filtres `pending`, `approved`, `rejected` |
| `/admin/driver-applications/{id}` | Détail + photos |
| `POST .../approve` | Approuver → crée Driver + Vehicle |
| `POST .../reject` | Rejeter (motif obligatoire) |

Middleware : `auth` + `admin`.

## Workflow validation

### Approuver

1. Vérifier statut `pending` et unicité permis/plaque
2. Mettre à jour le `User` (nom, téléphone, email)
3. Créer `Driver` (`offline`, `is_available: false`) si absent
4. Créer `Vehicle` si absent
5. Passer la candidature en `approved`, renseigner `reviewed_by` / `reviewed_at`
6. Envoyer `DriverApplicationApprovedNotification`
7. Émettre `DriverApplicationApproved` (Reverb `private-user-{id}`)

### Rejeter

1. Motif obligatoire (min. 10 caractères)
2. Statut `rejected` + `reviewed_by` / `reviewed_at`
3. Notification + événement `DriverApplicationRejected`

## Dashboard KPI (`/admin/dashboard`)

- Candidatures en attente
- Candidatures approuvées
- Candidatures rejetées

(actualisation live via `/admin/live/dashboard`)

## Stockage documents

Disque `public` : `storage/app/public/driver-applications/{id}/...`

```bash
php artisan storage:link
```

## Tests

```bash
php artisan test --filter=DriverApplication
```

- `DriverApplicationSubmissionTest`
- `DriverApplicationApprovalTest`
- `DriverApplicationRejectionTest`
- `DriverApplicationAdminAccessTest`

## Rétrocompatibilité

- Aucune modification des endpoints `/api/drivers/*`, `/api/rides/*`, Reverb, dispatch, tracking
- Les chauffeurs seedés (`DriverSeeder`) contournent l'enrôlement (données démo)
- Les apps Flutter existantes continuent de fonctionner sans changement

## Déploiement VPS

```bash
cd /var/www/mami.ga
git pull origin main
composer install --no-dev
php artisan migrate --force
php artisan storage:link
npm ci && npm run build
php artisan config:cache
php artisan test --filter=DriverApplication
```
