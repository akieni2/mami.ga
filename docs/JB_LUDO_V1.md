# JB Ludo V1 — Jeu de dames en ligne

**Intégration MAMI** · Module Laravel `JbLudo` + app Flutter `mobile/jb_ludo`

| | |
|---|---|
| **Règles** | Dames internationales 10×10 (20 pions) — autorité serveur |
| **Modes V1** | Partie amicale · Partie rapide |
| **Temps réel** | Reverb canal privé `jb-match-{id}` + polling Flutter 3 s |
| **Argent** | Points virtuels uniquement |

---

## Activation VPS

```bash
cd /var/www/mami.ga
git pull origin feature/mami-taxi-v2-p2

# .env
MAMI_MODULE_JBLUDO=true

composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

Admin : `https://admin.mami.ga/admin/jb-ludo`

---

## API (`/api/jb-ludo`)

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | `/profile` | Créer / maj profil (téléphone unique) |
| GET | `/profile/me` | Profil courant |
| POST | `/matches/invite` | Invitation amicale |
| POST | `/matches/invites/{id}/accept` | Accepter → démarre la partie |
| POST | `/matches/quick` | Matchmaking rapide |
| GET | `/matches/{id}` | État plateau + horloge |
| POST | `/matches/{id}/moves` | Coup (path `[{r,c},…]`) |
| POST | `/matches/{id}/resign` | Abandon (−5 pts) |
| POST | `/matches/{id}/disconnect` | Signal coupure |
| POST | `/matches/{id}/reconnect` | Reprise (grâce 90 s) |
| GET | `/leaderboard` | Classement général |
| GET | `/matches/history` | Historique |

---

## Points V1

| Événement | Delta |
|-----------|-------|
| Victoire | +10 |
| Nul | +3 |
| Défaite | 0 |
| Abandon | −5 |

---

## App Flutter

```bash
cd mobile/jb_ludo
flutter pub get
flutter build apk --release --dart-define=API_BASE_URL=https://api.mami.ga/api
```

Écrans : login, profil, accueil (rapide / amicale / classement / historique), plateau 10×10, validation de coup.

Reconnexion : cycle de vie app → `disconnect` / `reconnect` ; grâce serveur configurable (`MAMI_JBLUDO_RECONNECT_GRACE`, défaut 90 s).

---

## Tests

```bash
php vendor/bin/phpunit tests/Unit/JbLudo/CheckersEngineTest.php
php artisan test tests/Feature/JbLudo/JbLudoApiTest.php   # MySQL requis
```

---

## Hors V1 (prochaines itérations)

Tournois, clubs, Elo, chat, classements régionaux, paiements mobiles.

---

## Championnats admin

L'admin peut creer un championnat depuis `https://admin.mami.ga/admin/jb-ludo/championships`.

Fonctions V1 :

- definir un nombre maximum de participants, par exemple 1000 ;
- ajouter automatiquement les joueurs JB Ludo actifs et non suspendus ;
- choisir une repartition par classement ou par tirage aleatoire ;
- generer le premier tour en elimination directe ;
- creer automatiquement les parties de championnat ;
- faire avancer automatiquement les vainqueurs quand toutes les parties d'un tour sont terminees ;
- garder un bouton admin de secours pour generer le tour suivant si une verification manuelle est necessaire.

Exemple pour 1000 participants :

- grille calculee : 1024 places ;
- qualifications automatiques : 24 joueurs ;
- parties creees au premier tour : 488 ;
- tours necessaires jusqu'a la finale : 10.

Les joueurs voient ensuite leurs parties de championnat dans leur historique de parties.

### Progression automatique

A la fin de la derniere partie d'un tour, le backend recupere les vainqueurs, elimine les perdants, cree le tour suivant, puis repete ce cycle jusqu'a ce qu'il ne reste qu'un champion. Les matchs nuls doivent etre rejoues ou tranches avant d'avancer.
