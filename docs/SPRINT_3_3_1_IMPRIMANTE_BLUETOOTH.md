# Sprint 3.3.1 — Sélection manuelle imprimante Bluetooth

## Objectif

Remplacer la sélection automatique (première imprimante détectée) par un choix manuel **persistant** utilisé pour toutes les quittances.

## Implémentation Flutter

### Persistance (`SharedPreferences`)

| Clé | Contenu |
|-----|---------|
| `municipality.printer_name` | Nom affiché |
| `municipality.printer_mac` | Adresse MAC |

Migration depuis l’ancienne clé `municipality.selected_printer_mac`.

### Écrans

| Écran | Rôle |
|-------|------|
| `select_printer_screen.dart` | Liste appareils **appairés** (nom + MAC), sélection radio, enregistrement |
| `print_receipt_screen.dart` | Utilise l’imprimante par défaut, bouton **Changer d'imprimante** |
| `recovery_hub_screen.dart` | Tuile **Imprimante Bluetooth** |

Route : `/municipality/recovery/printer`

### Service `PrinterService`

- `listPairedPrinters()` — scan appareils appairés (pas de découverte active)
- `selectPrinter()` — sauvegarde nom + MAC
- `prepareForPrint()` / `printReceipt()` — lève `PrinterException` détaillée

### Erreurs explicites (`PrinterException`)

| Raison | Message |
|--------|---------|
| Bluetooth désactivé | Activez le Bluetooth… |
| Permission refusée | BLUETOOTH_SCAN / BLUETOOTH_CONNECT |
| Aucune imprimante | Choisissez une imprimante appairée |
| Imprimante non trouvée | Vérifiez l'appairage système |
| Connexion refusée | Imprimante allumée / à portée |
| Timeout impression | Délai dépassé (15 s) |
| Échec envoi | Échec données imprimante |

## Android 12+

`AndroidManifest.xml` :

- `BLUETOOTH_SCAN` (`neverForLocation`)
- `BLUETOOTH_CONNECT`

Runtime : `permission_handler` demande `bluetoothScan` + `bluetoothConnect` avant le scan des appareils appairés.

## Tests

```bash
cd mobile/mami_client
flutter pub get
flutter test test/features/municipality/printer_selection_test.dart
```

## Validation terrain

1. Recouvrement → **Imprimante Bluetooth**
2. Appairer l’imprimante 58 mm dans les paramètres Android
3. Sélectionner l’imprimante → **Enregistrer**
4. Imprimer une quittance → utilise l’imprimante enregistrée
5. **Changer d'imprimante** depuis l’écran d’impression si besoin
