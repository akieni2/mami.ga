# JB Games V1 — Damier et Ludo

**Intégration MAMI** · Module Laravel `JbLudo` + app Flutter unique `mobile/jb_ludo`

| | |
|---|---|
| **Jeux** | Damier disponible · Ludo MVP 4 joueurs |
| **Règles Damier** | Dames internationales 10×10 (20 pions) — autorité serveur |
| **Modes Damier V1** | Entrainement IA · Partie amicale · Partie rapide · Championnat |
| **Modes Ludo V1** | Entrainement IA · Partie rapide 4 joueurs |
| **Temps réel** | Reverb canal privé `jb-match-{id}` + polling Flutter 3 s |
| **Argent** | Points virtuels uniquement |

## Profils joueurs

Chaque joueur peut creer son profil directement depuis l'APK avec:

- prenom et nom ;
- screen name / alias ;
- telephone unique ;
- pays ;
- ville ;
- quartier ;
- niveau sportif.

Ces informations servent a organiser les competitions par progression territoriale:

1. championnat de quartier ;
2. championnat de ville ;
3. championnat national.

## Equipes et organisation

Le module prepare aussi l'organisation sportive:

- equipes rattachees a un quartier (`jb_teams`) ;
- membres d'equipe avec roles: joueur, capitaine, manager, coach, arbitre, commissaire ;
- demandes de recrutement/transfert (`jb_transfer_requests`) ;
- toute arrivee d'un joueur venant d'une autre zone doit passer par la commission d'organisation.

La commission peut ensuite etre utilisee pour valider les transferts, programmer les championnats, designer les arbitres et definir les prix a gagner.

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

L'APK affiche d'abord un choix de jeu:

- **Damier** : branche sur le moteur existant et les competitions actuelles.
- **Ludo** : partie rapide 4 joueurs, de serveur, sortie sur 6, tours Rouge/Bleu/Vert/Jaune, captures hors cases protegees et progression des 4 pions par joueur.
- **Entrainement IA** : disponible gratuitement, sans API externe payante, avec profils virtuels crees cote serveur.

---

## API (`/api/jb-ludo`)

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | `/profile` | Créer / maj profil (téléphone unique) |
| GET | `/profile/me` | Profil courant |
| POST | `/matches/invite` | Invitation amicale |
| POST | `/matches/invites/{id}/accept` | Accepter → démarre la partie |
| POST | `/matches/quick` | Matchmaking rapide |
| POST | `/matches/solo` | Partie d'entrainement contre IA gratuite |
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
cp build/app/outputs/flutter-apk/app-release.apk jb-games-1.0.5.apk
```

Publication VPS :

```bash
# depuis le PC (après build)
scp jb-games-1.0.6.apk root@63.142.241.105:/var/www/mami.ga/public/apk/
ssh root@63.142.241.105 'ln -sfn jb-games-1.0.6.apk /var/www/mami.ga/public/apk/jb-games-latest.apk'
```

Téléchargement : `https://admin.mami.ga/apk/jb-games-1.0.6.apk`

L’APK 1.0.6+ utilise l’**IP VPS** (`63.142.241.105`) pour contourner les DNS mobiles qui bloquent `api.mami.ga` / `admin.mami.ga`. Sur l’écran login : `v1.0.6 · https://63.142.241.105/api`.

Écrans : login, profil, choix du jeu, accueil Damier (rapide / amicale / classement / historique), plateau 10×10, validation de coup.

Reconnexion : cycle de vie app → `disconnect` / `reconnect` ; grâce serveur configurable (`MAMI_JBLUDO_RECONNECT_GRACE`, défaut 90 s).

---

## Tests

```bash
php vendor/bin/phpunit tests/Unit/JbLudo/CheckersEngineTest.php
php artisan test tests/Feature/JbLudo/JbLudoApiTest.php   # MySQL requis
```

---

## Hors V1 (prochaines itérations)

Blocages Ludo complets, competitions Ludo, clubs, Elo, chat, classements regionaux, paiements mobiles.

---

## Championnats admin

L'admin peut creer un championnat depuis `https://admin.mami.ga/admin/jb-ludo/championships`.

Chaque championnat porte maintenant un `game_type`:

- `damier` : actif avec repartition automatique et moteur existant.
- `ludo` : reserve pour les competitions Ludo, a brancher apres finalisation du moteur Ludo complet.

Chaque championnat porte aussi un niveau territorial:

- `neighborhood` : quartier ;
- `city` : ville ;
- `national` : pays entier.

L'ajout automatique des joueurs respecte ce perimetre: un championnat de quartier ne prend que les joueurs de ce quartier, un championnat de ville prend les joueurs de cette ville, et un championnat national prend les joueurs du pays.

L'admin peut egalement renseigner les prix:

- titre du prix ;
- montant ;
- devise ;
- description.

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
