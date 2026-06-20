# Rapport d'audit — Blocage GPS recensement économique

**Date :** 2026-06-16  
**Écran concerné :** Recensement économique (`EnrollEconomicOperatorScreen`)  
**Symptôme terrain :** coordonnées affichées, précision descend parfois à **12 m**, téléphone immobile, mode Android « Haute précision » activé — message persistant :

> Position GPS insuffisamment précise. Veuillez patienter.

**Périmètre :** diagnostic uniquement — aucune modification de code.

---

## 1. Fichier Flutter responsable

| Fichier | Rôle |
|---------|------|
| **`mobile/mami_client/lib/features/municipality/presentation/screens/enroll_economic_operator_screen.dart`** | Écran unique du recensement économique — capture GPS, validation, soumission |

Fichiers connexes (même message côté API, hors UI Flutter) :

| Fichier | Lignes | Rôle |
|---------|--------|------|
| `app/Modules/Municipality/Services/EconomicOperatorService.php` | 165–173 | Validation serveur `assertGpsAccuracy()` |
| `app/Modules/Municipality/Http/Requests/StoreEconomicOperatorRequest.php` | 23–34, 51 | Règle `max:20` + message identique |
| `config/municipality.php` | 22 | `gps_max_accuracy_m => 20` |

---

## 2. Seuil GPS exact exigé

### Flutter (client)

```dart
// enroll_economic_operator_screen.dart L27
static const _maxAccuracyM = 20.0;
```

**Condition d'acceptation :** `position.accuracy <= 20.0` (mètres, champ `Position.accuracy` du package `geolocator` ^13.0.2).

### Backend (aligné)

```php
// config/municipality.php L22
'gps_max_accuracy_m' => 20,

// StoreEconomicOperatorRequest.php L34
'gps_accuracy_m' => ['required', 'numeric', 'min:0', 'max:'.$maxAccuracy],
```

**Une précision affichée à 12 m devrait théoriquement passer** le seuil (12 ≤ 20). Le blocage à 12 m indique un problème de **logique d'état / concurrence**, pas un seuil trop strict.

### Affichage vs validation

| Élément | Ligne | Détail |
|---------|-------|--------|
| Affichage UI | L391 | `'Précision : ${p.accuracy.toStringAsFixed(0)} mètres'` — **arrondi à l'entier** |
| Validation | L99, L140 | Compare la valeur **brute** `position.accuracy` (double) à `20.0` |

**Effet de bord (bord de seuil, pas 12 m) :** une précision réelle de **20,1 à 20,49 m** s'affiche « 20 m » mais **échoue** la condition `<= 20.0`.

---

## 3. Conditions booléennes qui autorisent l'enregistrement

### A. GPS « prêt » — getter `_gpsReady`

```dart
// L139–140
bool get _gpsReady =>
    _position != null && _position!.accuracy <= _maxAccuracyM;
```

### B. Message d'avertissement GPS — `_gpsMessage`

```dart
// L99–101 (dans setState après capture)
_gpsMessage = position.accuracy <= _maxAccuracyM
    ? null
    : 'Position GPS insuffisamment précise. Veuillez patienter.';
```

### C. Réinitialisation confirmation carte

```dart
// L102–104
if (position.accuracy > _maxAccuracyM) {
  _locationConfirmed = false;
}
```

### D. Soumission — `_submit()`

| Étape | Lignes | Condition | Message si échec |
|-------|--------|-----------|------------------|
| Formulaire | L143 | `_formKey.validate()` | Validators champs |
| GPS | L145–153 | `!_gpsReady` | SnackBar « Position GPS insuffisamment précise… » |
| Photo façade | L156–160 | `_facadePhoto == null` | « La photo de façade est obligatoire. » |
| Confirmation carte | L163–172 | `!_locationConfirmed` | « Confirmez l'emplacement sur la carte… » |
| Catégorie | L174 | `_categoryId == null` | (return silencieux) |

**Enregistrement autorisé uniquement si :** `_gpsReady == true` **ET** `_locationConfirmed == true` **ET** photo façade présente **ET** formulaire valide.

### E. Visibilité UI dépendante du GPS

```dart
// L232–245 — carte + checkbox visibles SEULEMENT si _gpsReady
if (_gpsReady) ...[
  _buildMapPreview(),
  CheckboxListTile(value: _locationConfirmed, ...),
]
```

Tant que `_gpsReady` est faux, l'agent **ne voit pas** la case « Je confirme que ce commerce se trouve à cet emplacement » — impossibilité de valider même si les coordonnées s'affichent.

---

## 4. Réévaluation à chaque mise à jour GPS

### Mécanisme actuel

```dart
// L66–68
void _startGpsWatch() {
  _captureGps();
  _gpsTimer = Timer.periodic(const Duration(seconds: 3), (_) => _captureGps());
}
```

```dart
// L71–105 — _captureGps()
final position = await Geolocator.getCurrentPosition(
  locationSettings: const LocationSettings(accuracy: LocationAccuracy.high),
);
setState(() {
  _position = position;
  _loadingGps = false;
  _gpsMessage = ...;
  if (position.accuracy > _maxAccuracyM) {
    _locationConfirmed = false;
  }
});
```

| Question | Réponse |
|----------|---------|
| Réévaluation périodique ? | **Oui** — toutes les **3 secondes** + appel initial à `initState` (L50–52) |
| `setState()` présent ? | **Oui** — L80–83, L96–105, L108–111 |
| Riverpod pour le GPS ? | **Non** — état local `_EnrollEconomicOperatorScreenState` (`_position`, `_gpsMessage`, `_locationConfirmed`) |
| `getPositionStream()` ? | **Non** — snapshots discrets via `getCurrentPosition()` |

**Conclusion §4 :** la condition **est** réévaluée et **`setState()` n'est pas manquant**. Le problème n'est pas un oubli de rebuild, mais la **sémantique des appels GPS concurrents** (voir §7).

---

## 5. `setState` / Riverpod / notifier — analyse

| Composant | Statut |
|-----------|--------|
| `setState()` après capture GPS | **Présent** (L96–105) |
| Provider Riverpod GPS dédié | **Absent** — pas de `StateNotifier` / `AsyncNotifier` pour la position |
| `ref.watch` sur position | **Absent** — seul `economicOperatorCategoriesProvider` est observé (L217) |
| Invalidation après enrôlement | L198 — `ref.invalidate(economicOperatorDashboardProvider)` (hors GPS) |

**Pas de notifier manquant au sens Flutter/Riverpod** : l'architecture repose sur `StatefulWidget` + timer local. En revanche, il **manque une synchronisation** des appels async (mutex / annulation / un seul flux GPS).

---

## 6. Interface bloquée après une première mesure imprécise ?

### Oui — comportements identifiés

#### 6.1 Carte orange + message persistant

```dart
// L377–378
color: _gpsReady ? Colors.green.shade50 : Colors.orange.shade50,
// L393–398
if (_gpsMessage != null) ... Text(_gpsMessage!)
```

Tant que la **dernière** capture aboutit avec `accuracy > 20`, la carte reste orange et le message visible — **même si une capture intermédiaire avait affiché 12 m**.

#### 6.2 Confirmation carte effacée

```dart
// L102–104
if (position.accuracy > _maxAccuracyM) {
  _locationConfirmed = false;
}
```

Une mesure ultérieure > 20 m **annule** une confirmation déjà cochée.

#### 6.3 Carte et checkbox masquées

```dart
// L232
if (_gpsReady) ...[ _buildMapPreview(), CheckboxListTile(...) ]
```

Si `_gpsReady` repasse à `false`, la section confirmation **disparaît** — l'utilisateur ne peut plus cocher la case.

#### 6.4 SnackBar au submit (persistant visuellement)

```dart
// L145–153
if (!_gpsReady) {
  ScaffoldMessenger.of(context).showSnackBar(
    const SnackBar(content: Text('Position GPS insuffisamment précise. Veuillez patienter.')),
  );
}
```

Un appui sur « Enregistrer le commerce » alors que `_gpsReady` est faux affiche **le même texte** en SnackBar, indépendamment de la valeur momentanée affichée sur la carte si un rebuild n'a pas encore eu lieu.

---

## 7. Cause racine exacte

### Cause principale — **courses async GPS concurrentes (race condition)**

**Fichier :** `enroll_economic_operator_screen.dart`  
**Lignes :** 66–68, 71–105

`_captureGps()` est **async** et **non sérialisée**. `Timer.periodic` en lance une nouvelle instance **toutes les 3 s** sans attendre la fin de la précédente ni annuler les requêtes en cours.

**Scénario terrain typique :**

```
t=0s   → capture #1 démarre (getCurrentPosition, peut durer 5–15 s)
t=3s   → capture #2 démarre (parallèle)
t=6s   → capture #3 démarle (parallèle)
t=7s   → capture #1 termine : accuracy = 12 m → setState OK (_gpsMessage = null)
t=9s   → capture #2 termine : accuracy = 35 m → setState KO (_gpsMessage réaffiché)
t=12s  → capture #3 termine : accuracy = 28 m → message persiste
```

**Effet observé :** l'agent voit la précision **varier** (parfois 12 m) mais le message **reste** car la **dernière** requête `getCurrentPosition` terminée écrase l'état avec une mesure > 20 m.

`getCurrentPosition()` peut renvoyer un **fix cache** ou une estimation pessimiste selon l'ordre de complétion des requêtes Android — d'où des valeurs fluctuantes sur téléphone immobile.

**Pourquoi 12 m ne « débloque » pas durablement :** le code est correct **par capture isolée** (`12 <= 20` → message effacé), mais **l'état final** dépend de la **dernière** capture async terminée, pas de la meilleure mesure affichée un instant.

---

### Cause secondaire — **double verrou métier (GPS + confirmation carte)**

Même avec GPS stable ≤ 20 m, l'enregistrement reste impossible sans :

1. `_gpsReady == true` (L140, L145)
2. `_locationConfirmed == true` (L163–172) — checkbox L235–243, visible seulement si `_gpsReady`

Un agent peut interpréter le blocage comme « GPS insuffisant » alors que le GPS est OK mais la **case de confirmation** n'est pas accessible (GPS instable) ou pas cochée.

---

### Cause tertiaire — **arrondi affichage (bord 20 m uniquement)**

L391 arrondit à l'entier ; la validation L99/L140 utilise le double brut. Cas limite 20,1–20,49 m affichés « 20 m » mais rejetés — **hors cas 12 m** mais pertinent pour la recette.

---

### Ce qui n'est PAS la cause

| Hypothèse écartée | Preuve |
|-------------------|--------|
| Seuil à 12 m | Seuil = **20 m** (L27) ; 12 m devrait passer |
| `setState()` manquant | Présent L96–105 |
| Pas de réévaluation GPS | Timer 3 s L68 |
| Feature flag / module | Hors scope GPS |
| Backend seul | Blocage UI client avant appel API |

---

## 8. Synthèse lignes critiques

| # | Fichier | Ligne(s) | Élément |
|---|---------|----------|---------|
| 1 | `enroll_economic_operator_screen.dart` | **27** | Seuil `_maxAccuracyM = 20.0` |
| 2 | idem | **66–68** | Timer 3 s sans garde concurrence |
| 3 | idem | **88–92** | `getCurrentPosition` (snapshot, pas stream) |
| 4 | idem | **96–105** | `setState` : position, message, reset confirmation |
| 5 | idem | **99–101** | Texte « Position GPS insuffisamment précise… » |
| 6 | idem | **102–104** | Reset `_locationConfirmed` si > 20 m |
| 7 | idem | **139–140** | Getter `_gpsReady` |
| 8 | idem | **145–153** | Blocage submit + SnackBar même message |
| 9 | idem | **163–172** | Blocage submit sans confirmation carte |
| 10 | idem | **232–245** | Carte/checkbox cachées si `!_gpsReady` |
| 11 | idem | **377–398** | Carte orange + affichage message |
| 12 | idem | **391** | Affichage précision arrondi |
| 13 | `config/municipality.php` | **22** | Seuil serveur 20 m |
| 14 | `StoreEconomicOperatorRequest.php` | **34, 51** | Validation API identique |

---

## 9. Correctifs minimaux recommandés (non implémentés)

1. **Sérialiser les captures GPS** — ignorer un tick si une capture est en cours, ou utiliser `getPositionStream()` avec debounce / « meilleure précision depuis N secondes ».
2. **Conserver la meilleure mesure** — ne mettre à jour `_position` que si `new.accuracy < current.accuracy` (ou si `new.accuracy <= 20` stabilisé sur 2 lectures consécutives).
3. **Afficher la précision réelle** — `toStringAsFixed(1)` pour éviter l'écart affichage/validation autour de 20 m.
4. **UX confirmation** — ne pas masquer la checkbox quand le GPS est passé de bon → mauvais ; ou message distinct si GPS OK mais confirmation manquante.

---

## 10. Vérification recette terrain (sans patch)

Pour confirmer la cause race en conditions réelles :

1. Activer les logs Flutter (`debugPrint` dans `_captureGps` avec timestamp + accuracy).
2. Observer si plusieurs `getCurrentPosition` se chevauchent (fin hors ordre de démarrage).
3. Noter si le message disparaît **brièvement** à 12 m puis revient (signature race).
4. Vérifier si la carte verte + checkbox apparaissent même une seconde à ≤ 20 m.

**Critère succès attendu avec code actuel :** enregistrement possible uniquement si une capture ≤ 20 m est la **dernière** à terminer **et** checkbox cochée **et** photo façade prise.
