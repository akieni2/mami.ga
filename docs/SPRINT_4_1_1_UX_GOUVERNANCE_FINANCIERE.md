# Sprint 4.1.1 — UX Gouvernance Financière

**Portail Finance dédié et redirection post-connexion par rôle**

| | |
|---|---|
| **Prérequis** | Sprint 4.1 (workflow visa, API finance) |
| **Périmèuvre** | Flutter `mami_client` uniquement |
| **Non-régression** | Hub agent terrain inchangé pour `municipal_agent` seul |

---

## 1. Problème résolu

Les rôles `daf`, `daf_adjoint`, `controleur_financier`, `receveur_municipal` et `caissier_central` n'avaient pas d'accueil dédié et devaient passer par le hub **Recouvrement** (conçu pour les agents terrain).

---

## 2. Nouvelle route

| Route | Écran |
|-------|-------|
| `/municipality/finance/home` | `FinanceHomeScreen` — portail Finance |
| `/municipality/finance/dashboard` | `DafDashboardScreen` — KPI DAF |
| `/municipality/finance` | Redirige vers `/home` |

---

## 3. Redirection post-connexion

Priorité dans `UserModel.postAuthRoute` :

1. Rôle finance → `/municipality/finance/home`
2. `municipal_agent` seul → `/municipality/agent`
3. Autres → `/`

Si un utilisateur cumule `municipal_agent` + `daf`, le **portail Finance** est prioritaire.

---

## 4. Menus conditionnels

| Tuile | DAF | Adjoint | Contrôleur | Receveur | Caissier |
|-------|-----|---------|------------|----------|----------|
| Tableau de bord DAF | ✅ | ✅ | — | — | — |
| Validation missions | ✅ | ✅ | ✅ | — | — |
| Missions financières | ✅ | ✅ | — | — | ✅ |
| Supervision caisses | ✅ | ✅ | ✅ | — | ✅ |
| Reversements Trésor | ✅ | ✅ | — | ✅ | — |
| Comptabilité (4.3) | placeholder | placeholder | — | — | — |
| Budget (4.4) | placeholder | placeholder | — | — | — |
| RH (4.5) | placeholder | placeholder | — | — | — |
| Prestataires (4.6) | placeholder | placeholder | — | — | — |

**Clôture administrative caisse :** DAF et Contrôleur uniquement (pas DAF adjoint).

---

## 5. Fichiers

| Fichier | Rôle |
|---------|------|
| `domain/finance_home_access.dart` | Règles visibilité + routes |
| `presentation/screens/finance_home_screen.dart` | Portail |
| `auth/domain/models/user_model.dart` | Redirection login |
| `core/router/app_router.dart` | Routes |
| `cash_supervision_screen.dart` | Masque clôture admin si non autorisé |

---

## 6. Tests

```bash
flutter test test/features/municipality/finance_role_redirect_test.dart
flutter test test/features/municipality/finance_menu_visibility_test.dart
flutter test test/features/municipality/finance_home_navigation_test.dart
```

---

## 7. Déploiement

Rebuild APK après merge :

```bash
cd mobile/mami_client
flutter build apk --release
```

Aucune migration backend requise.

---

*MAMI.ga — Owendo · Gouvernance financière municipale.*
