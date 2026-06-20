# Rapport d'audit — Hub Municipalité Flutter (mami_client)

**Date :** 2026-06-16  
**Contexte terrain confirmé :**
- `MAMI_MODULE_MUNICIPALITY=true` (Laravel)
- `GET /api/app/features` → `modules.municipality = true`
- Utilisateur `agent.owendo@mami.ga` → rôle `municipal_agent` vérifié en base
- Connexion APK réussie
- **Symptôme :** après login, écran citoyen uniquement (Accueil / Historique / Profil), aucun accès visible au Hub Municipal

**Périmètre :** diagnostic uniquement — aucune modification de code effectuée.

---

## Verdict exécutif

| Hypothèse | Verdict |
|-----------|---------|
| **A** — Le rôle n'arrive pas dans Flutter | **Non** (backend + parsing OK) |
| **B** — Le rôle arrive mais n'est pas exploité | **Partiellement** (utilisé dans `/municipality`, ignoré pour l'accueil et la navigation) |
| **C** — Écrans municipalité existent mais aucune entrée UI | **Oui — cause principale** |

**Cause racine :** l'APK release ne consulte jamais `GET /api/app/features` lorsque `MAMI_TAXI_V2=true` (valeur **par défaut** au build). La tuile « Mairie » reste donc désactivée (`municipality: false` en dur), indépendamment du rôle utilisateur et de la config serveur.

**Correctif minimal recommandé (priorité 1) :** corriger `app_features_provider.dart` pour **toujours** appeler `/app/features` (ou au minimum ne pas court-circuiter l'appel quand `MAMI_TAXI_V2=true`).

**Correctif complémentaire (priorité 2, UX agent) :** rediriger `municipal_agent` vers `/municipality/agent` après login (splash / login / redirect router).

---

## 1. API `/api/login` et `/api/me`

### Backend — rôles et permissions envoyés

**Fichier :** `app/Http/Controllers/Api/AuthController.php`

| Méthode | Lignes | Comportement |
|---------|--------|--------------|
| `login()` | 36–38 | Charge `roles.permissions` via `->with(['driver.vehicle', 'roles.permissions'])` |
| `me()` | 64 | Recharge `roles.permissions` |
| `userPayload()` | 71–98 | Retourne `roles[]` (slugs) et `permissions[]` (slugs uniques) |

**Forme JSON attendue (enveloppe ApiResponse) :**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 42,
      "name": "...",
      "email": "agent.owendo@mami.ga",
      "phone": "...",
      "is_driver": false,
      "roles": ["municipal_agent"],
      "permissions": [
        "municipality.dashboard.view",
        "economic_operator.create",
        "..."
      ],
      "driver": null
    },
    "token": "..."
  }
}
```

Pour `/api/me`, `data` ne contient que `{ "user": { ... } }` (pas de token).

### Flutter — parsing auth

**Fichier :** `mobile/mami_client/lib/features/auth/data/auth_repository.dart`

| Action | Lignes | Détail |
|--------|--------|--------|
| Login | 34 | `UserModel.fromJson(data['user'])` |
| Restore session | 87 | Idem sur `/me` |

**Conclusion §1 :** si l'utilisateur a bien le rôle en base et que Spatie/`user_roles` est cohérent, **les rôles et permissions arrivent côté Flutter**. Aucun bug de parsing identifié.

---

## 2. UserModel

**Fichier :** `mobile/mami_client/lib/features/auth/domain/models/user_model.dart`

| Élément | Lignes | Détail |
|---------|--------|--------|
| Champ `roles` | 17–18, 34–37 | `List<String>` depuis `json['roles']` |
| Champ `permissions` | 18, 38–41 | `List<String>` depuis `json['permissions']` |
| `isMunicipalAgent` | 20 | `roles.contains('municipal_agent')` |
| `canEnrollEconomicOperators` | 24–25 | permission `economic_operator.create` **OU** `isMunicipalAgent` |

**Conclusion §2 :** le modèle est correct et prêt à exploiter le rôle agent. Il n'est **pas** utilisé pour afficher la tuile Mairie sur l'accueil.

---

## 3. HomeScreen / affichage des cartes municipalité

### HomeScreen — pas de test de rôle

**Fichier :** `mobile/mami_client/lib/features/home/presentation/screens/home_screen.dart`

| Ligne | Code / comportement |
|-------|---------------------|
| 45 | `ref.watch(appFeaturesProvider)` — source unique des modules |
| 53–56 | `modules = featuresAsync.data?.modules ?? AppFeatures.defaults().modules` |
| 37–38 | Si tuile Mairie **activée** → `context.push('/municipality')` |
| 27–32 | Si tuile **désactivée** → SnackBar « Mairie — bientôt disponible » |

**Aucune référence à `isMunicipalAgent`, `canEnrollEconomicOperators` ou `authStateProvider`.**

### ServicePortalGrid — règle d'activation des tuiles

**Fichier :** `mobile/mami_client/lib/features/home/presentation/widgets/service_portal_grid.dart`

| Ligne | Règle |
|-------|-------|
| **26** | `enabled = modules[module.slug] == true \|\| module.slug == 'taxi'` |

Seul **Taxi** est forcé actif. **Mairie** exige `modules['municipality'] == true`.

### AppFeatures — defaults locaux

**Fichier :** `mobile/mami_client/lib/core/config/app_features.dart`

| Ligne | Problème |
|-------|----------|
| **14–17** | `MAMI_TAXI_V2` dart-define, **`defaultValue: true`** |
| **43** | `AppFeatures.defaults()` → `'municipality': false` |

### AppFeaturesProvider — cause racine

**Fichier :** `mobile/mami_client/lib/core/config/app_features_provider.dart`

```dart
// Lignes 7–21 (logique actuelle)
if (!AppFeatures.taxiV2FromEnvironment) {
  // GET /app/features  ← exécuté SEULEMENT si MAMI_TAXI_V2=false au build
}
return AppFeatures.defaults();  // municipality: false
```

**Chaîne causale :**

1. APK release buildé avec `--dart-define=API_BASE_URL=...` **sans** `MAMI_TAXI_V2=false`
2. `taxiV2FromEnvironment == true` (défaut compile-time)
3. `/api/app/features` **jamais appelé** malgré `municipality=true` côté serveur
4. Tuile Mairie grisée (« Bientôt disponible »)
5. Agent municipal ne voit que le portail taxi / citoyen

**Contre-preuve terrain :** l'utilisateur confirme `/api/app/features` → `municipality=true` via curl/Postman, mais l'APK n'utilise pas cette réponse.

### MunicipalityHomeScreen — le rôle EST exploité (si on y accède)

**Fichier :** `mobile/mami_client/lib/features/municipality/presentation/screens/municipality_home_screen.dart`

| Ligne | Comportement |
|-------|--------------|
| 14 | `isAgent = user?.canEnrollEconomicOperators ?? false` |
| 27–46 | Section « Espace agent municipal » + bouton → `/municipality/agent` |

**Conclusion §3 :** l'UI municipalité agent **existe et fonctionne par rôle**, mais l'utilisateur n'y accède jamais car la tuile d'entrée est désactivée par le bug feature flags.

---

## 4. GoRouter — routes et démarrage post-login

**Fichier :** `mobile/mami_client/lib/core/router/app_router.dart`

### Redirect post-authentification

| Ligne | Comportement |
|-------|--------------|
| **51–52** | Si `user != null` et path `/login`, `/register` ou `/splash` → **`target = '/'`** |
| **36** | `initialLocation: '/splash'` |

Aucune branche `municipal_agent` → `/municipality/agent`.

### Routes municipalité (présentes, sans garde de rôle)

| Path | Ligne | Écran |
|------|-------|-------|
| `/municipality` | 121–122 | `MunicipalityHomeScreen` |
| `/municipality/agent` | 125–126 | `MunicipalityAgentHomeScreen` |
| `/municipality/recovery/*` | 141–195 | Recouvrement, caisse, quittances, etc. |

Les routes sont **accessibles par navigation directe** (deep link / `context.go`) — pas de middleware Flutter bloquant le rôle.

### SplashScreen

**Fichier :** `mobile/mami_client/lib/features/splash/presentation/screens/splash_screen.dart`

| Ligne | Comportement |
|-------|--------------|
| **55** | Après bootstrap → **`context.go('/')`** (toujours accueil citoyen) |

### LoginScreen

**Fichier :** `mobile/mami_client/lib/features/auth/presentation/screens/login_screen.dart`

| Ligne | Comportement |
|-------|--------------|
| **50** | Succès login → **`context.go('/')`** |

### MainShell — navigation basse citoyenne

**Fichier :** `mobile/mami_client/lib/features/shell/presentation/screens/main_shell.dart`

| Lignes | Onglets |
|--------|---------|
| 34–49 | Accueil / Historique / Profil — **aucun onglet Mairie ou Agent** |

### ProfileScreen

**Fichier :** `mobile/mami_client/lib/features/profile/presentation/screens/profile_screen.dart`

Aucun lien vers municipalité ou hub agent (logout, thème, URL API uniquement).

**Conclusion §4 :** le parcours post-login est **100 % orienté citoyen/taxi**. Même avec tuile Mairie activée, l'agent devrait manuellement la toucher ; il n'y a pas de redirection automatique vers le hub agent.

---

## 5. Classification A / B / C

### A — Le rôle n'arrive pas dans Flutter → **NON**

- Backend envoie `roles` et `permissions` (`AuthController::userPayload`, L71–98)
- `UserModel.fromJson` mappe correctement les tableaux de strings
- Tant que `agent.owendo@mami.ga` a `municipal_agent` en base, Flutter le reçoit au login et au `/me`

*Note :* `register()` ne charge pas les rôles (L19–29 AuthController) — hors scope login agent existant.

### B — Le rôle arrive mais n'est pas exploité → **PARTIEL**

| Zone | Exploite le rôle ? |
|------|-------------------|
| Tuile Mairie (HomeScreen) | **Non** — feature flag uniquement |
| Bottom nav / Profil | **Non** |
| Splash / Login redirect | **Non** — toujours `/` |
| MunicipalityHomeScreen | **Oui** — `canEnrollEconomicOperators` |
| MunicipalityAgentHomeScreen | Pas de garde UI (API backend protège) |

### C — Écrans existent, pas d'entrée visible → **OUI (cause principale)**

- Routes `/municipality/*` implémentées
- Hub agent (`MunicipalityAgentHomeScreen`) opérationnel si navigation manuelle
- **Aucune tuile active** + **aucun onglet** + **aucune redirection** = expérience 100 % citoyenne

---

## 6. Correctifs minimaux proposés (sans implémentation)

### Correctif 1 — Feature flags (obligatoire)

**Fichier :** `mobile/mami_client/lib/core/config/app_features_provider.dart`  
**Lignes :** 7–21

**Option A (recommandée) :** supprimer la condition `if (!AppFeatures.taxiV2FromEnvironment)` et **toujours** tenter `GET /app/features`, avec fallback `AppFeatures.defaults()` en cas d'erreur réseau.

**Option B (contournement build, sans patch code) :** rebuilder l'APK avec :

```bash
flutter build apk --release \
  --dart-define=API_BASE_URL=https://api.mami.ga/api \
  --dart-define=MAMI_TAXI_V2=false
```

→ Force l'appel API features ; la tuile Mairie devrait s'activer si le serveur renvoie `municipality: true`.

### Correctif 2 — Redirection agent (UX, recommandé)

**Fichiers :**
- `splash_screen.dart` L55
- `login_screen.dart` L50
- `app_router.dart` L51–52

**Changement :** si `user.isMunicipalAgent` (ou `canEnrollEconomicOperators`), `context.go('/municipality/agent')` au lieu de `'/'`.

### Correctif 3 — Tuile Mairie pour agents (optionnel, défense en profondeur)

**Fichier :** `service_portal_grid.dart` L26 **ou** `home_screen.dart`

Activer la tuile si `modules['municipality'] == true` **OU** `user.isMunicipalAgent`.

---

## 7. Test de validation post-correctif

1. Rebuild APK avec correctif 1 (ou dart-define `MAMI_TAXI_V2=false`)
2. Login `agent.owendo@mami.ga`
3. **Attendu immédiat (correctif 1 seul) :** tuile « Mairie » active sur Accueil → tap → section « Espace agent municipal » → Hub agent
4. **Attendu (correctif 2) :** landing directe sur « Mairie — Agent terrain »
5. Vérifier `adb logcat` : présence de `GET /app/features` et `roles: [municipal_agent]` dans les logs login existants (`auth_repository.dart` L27–28)

---

## 8. Synthèse fichier / ligne / cause / correctif

| # | Fichier | Ligne(s) | Cause | Correctif minimal |
|---|---------|----------|-------|-------------------|
| 1 | `app_features_provider.dart` | 8–21 | Skip API si `MAMI_TAXI_V2=true` (défaut) | Toujours fetch `/app/features` |
| 2 | `app_features.dart` | 14–17, 43 | Default compile `MAMI_TAXI_V2=true`, `municipality: false` | Aligner avec serveur ou retirer le gate |
| 3 | `service_portal_grid.dart` | 26 | Mairie inactive sans flag module | Corrigé par #1 ; optionnel OR `isMunicipalAgent` |
| 4 | `home_screen.dart` | 45–56, 37–38 | Pas de lien rôle ↔ tuile | Corrigé par #1 ; pas de changement rôle requis |
| 5 | `splash_screen.dart` | 55 | Redirect `/` systématique | Redirect `/municipality/agent` si agent |
| 6 | `login_screen.dart` | 50 | Idem | Idem |
| 7 | `app_router.dart` | 51–52 | Redirect auth → `/` | Branche agent |
| 8 | `main_shell.dart` | 34–49 | Nav citoyenne uniquement | Hors scope minimal (hub accessible via redirect/tuile) |

---

## 9. Réponse aux questions précises

| Question | Réponse |
|----------|---------|
| `/api/login` envoie les rôles ? | **Oui** — `roles: string[]` si relation chargée |
| `/api/login` envoie les permissions ? | **Oui** — `permissions: string[]` agrégées des rôles |
| `/api/me` idem ? | **Oui** — même `userPayload()` |
| `UserModel.roles[]` ? | **Oui** — parsing L34–37 |
| `UserModel.isMunicipalAgent` ? | **Oui** — L20, non utilisé sur HomeScreen |
| Conditions cartes municipalité ? | **`modules['municipality'] == true`** via `appFeaturesProvider` ; pas le rôle |
| Route `/municipality/agent` ? | **Existe** — `app_router.dart` L125–126 |
| Route démarrage post-login ? | **`/`** (HomeScreen citoyen) — splash L55, login L50, router L52 |

**Diagnostic final : C (+ bug configuration feature flags), pas A.**
