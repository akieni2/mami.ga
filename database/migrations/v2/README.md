# Migrations futures MAMI Taxi V2

Les migrations de ce dossier seront ajoutées phase par phase. Ne pas exécuter avant la phase correspondante.

| Phase | Fichier prévu | Table / objet |
|-------|---------------|---------------|
| P3 | `create_ride_offers_table` | `ride_offers` |
| P3 | `create_ride_dispatch_waves_table` | `ride_dispatch_waves` |
| P7 | `create_scheduled_ride_deposits_table` | `scheduled_ride_deposits` |
| P7 | `create_driver_availability_locks_table` | `driver_availability_locks` |
| P6 | `create_payments_table` | `payments` |
| P8 | `create_driver_reviews_table` | `driver_reviews` |

La migration `2026_05_25_100000_add_v2_prepared_fields_to_rides_table.php` prépare les colonnes `rides` (P0).
