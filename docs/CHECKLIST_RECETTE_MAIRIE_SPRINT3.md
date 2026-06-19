# Checklist de recette mairie — Municipality V3 Sprint 3

**Version :** 1.0  
**Date :** juin 2026  
**Commune :** Owendo  
**Module :** Fiscalité et recouvrement — Quittances officielles Sprint 3

---

## Objet de la recette

Valider, au nom de la **Commune d'Owendo**, que le cycle fiscal municipal complet est opérationnel :

> Scan QR opérateur économique → Situation fiscale → Encaissement → Quittance officielle → Impression → Vérification publique

Cette recette constitue le **procès-verbal fonctionnel** requis avant la clôture officielle de la Priorité 1.

---

## Documents préalables

| Document | Statut requis |
|----------|---------------|
| [CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md](CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md) | ✅ Complétée |
| [CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md](CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md) | ✅ Complétée |
| [RAPPORT_VALIDATION_SPRINT3_MUNICIPALITE.md](RAPPORT_VALIDATION_SPRINT3_MUNICIPALITE.md) | Cases A–F cochées |

---

## Participants à la recette

| Rôle | Nom | Fonction | Présent |
|------|-----|----------|---------|
| Représentant mairie | | Maire / Délégué | ☐ |
| Superviseur recouvrement | | Services municipaux | ☐ |
| Agent municipal terrain | | Agent pilote | ☐ |
| Chef de projet MAMI | | Équipe technique | ☐ |
| Commerçant pilote | | Opérateur économique | ☐ |

**Date de la recette :** _________________________  
**Lieu :** _________________________  
**Zone opérationnelle :** _________________________

---

## 1. Présentation du périmètre livré

| # | Livrable | Démontré | Conforme | Observations |
|---|----------|----------|----------|--------------|
| 1.1 | Moteur fiscal configurable (taxes, taux, obligations) | ☐ | ☐ Oui ☐ Non | |
| 1.2 | Registre économique (opérateurs, QR) | ☐ | ☐ Oui ☐ Non | |
| 1.3 | Sessions de caisse agent | ☐ | ☐ Oui ☐ Non | |
| 1.4 | Encaissement espèces terrain | ☐ | ☐ Oui ☐ Non | |
| 1.5 | Quittance officielle PDF | ☐ | ☐ Oui ☐ Non | |
| 1.6 | Signature numérique (hash SHA-256) | ☐ | ☐ Oui ☐ Non | |
| 1.7 | Vérification publique en ligne | ☐ | ☐ Oui ☐ Non | |
| 1.8 | Impression thermique Bluetooth 58 mm | ☐ | ☐ Oui ☐ Non | |
| 1.9 | Dashboard maire | ☐ | ☐ Oui ☐ Non | |
| 1.10 | Annulation quittance (superviseur) | ☐ | ☐ Oui ☐ Non | |

---

## 2. Démonstration fonctionnelle (live)

### Partie A — Préparation administrative

| # | Action démontrée | Validé mairie | OK |
|---|------------------|---------------|-----|
| 2.A.1 | Consultation types de taxes configurés | ☐ | ☐ |
| 2.A.2 | Consultation opérateurs enrôlés zone pilote | ☐ | ☐ |
| 2.A.3 | Consultation obligations générées | ☐ | ☐ |

### Partie B — Parcours agent terrain

| # | Action démontrée | Validé mairie | OK |
|---|------------------|---------------|-----|
| 2.B.1 | Ouverture session de caisse | ☐ | ☐ |
| 2.B.2 | Scan QR commerce pilote | ☐ | ☐ |
| 2.B.3 | Affichage situation fiscale | ☐ | ☐ |
| 2.B.4 | Encaissement espèces | ☐ | ☐ |
| 2.B.5 | Émission quittance `OWE-RCP-*` | ☐ | ☐ |
| 2.B.6 | Impression ticket 58 mm remis au commerçant | ☐ | ☐ |

### Partie C — Vérification citoyenne

| # | Action démontrée | Validé mairie | OK |
|---|------------------|---------------|-----|
| 2.C.1 | Scan QR quittance par commerçant | ☐ | ☐ |
| 2.C.2 | Page vérification `mami.ga` — statut valide | ☐ | ☐ |
| 2.C.3 | Données affichées conformes au ticket | ☐ | ☐ |

### Partie D — Supervision

| # | Action démontrée | Validé mairie | OK |
|---|------------------|---------------|-----|
| 2.D.1 | Dashboard maire — quittances du jour | ☐ | ☐ |
| 2.D.2 | Montants par agent / quartier | ☐ | ☐ |
| 2.D.3 | Traçabilité (agent, date, commerce) | ☐ | ☐ |

---

## 3. Conformité réglementaire et sécurité

| # | Critère | Conforme | OK |
|---|---------|----------|-----|
| 3.1 | Quittance non modifiable après émission | ☐ Oui ☐ Non | ☐ |
| 3.2 | Annulation tracée (agent, date, motif) | ☐ Oui ☐ Non | ☐ |
| 3.3 | Seul le superviseur peut annuler | ☐ Oui ☐ Non | ☐ |
| 3.4 | Données fiscales non exposées publiquement | ☐ Oui ☐ Non | ☐ |
| 3.5 | HTTPS sur tous les accès production | ☐ Oui ☐ Non | ☐ |

---

## 4. Éléments hors périmètre (confirmés reportés)

La mairie confirme que les éléments suivants **ne font pas partie** de cette recette et sont reportés au backlog :

| Élément | Phase reportée |
|---------|----------------|
| Mobile Money (Airtel, Moov) | V3.1+ |
| Sync offline agent | Post-P1 |
| Brigades terrain | V3.4 |
| Remboursement automatisé API | Backlog BL-04 |

| # | Confirmé par la mairie | OK |
|---|------------------------|-----|
| 4.1 | Périmètre Sprint 3 accepté sans Mobile Money | ☐ |
| 4.2 | Report des éléments ci-dessus accepté | ☐ |

---

## 5. Réserves et observations mairie

| # | Réserve / observation | Gravité | Délai correction | Bloquante clôture |
|---|----------------------|---------|------------------|-------------------|
| 1 | | | | ☐ Oui ☐ Non |
| 2 | | | | ☐ Oui ☐ Non |
| 3 | | | | ☐ Oui ☐ Non |

---

## 6. Décision de recette

### Résultat global

| Décision | Cocher |
|----------|--------|
| ✅ **Recette acceptée** — Sprint 3 conforme, clôture P1 autorisée | ☐ |
| ⚠️ **Recette acceptée avec réserves** — clôture sous conditions (préciser) | ☐ |
| ❌ **Recette refusée** — corrections requises avant nouvelle recette | ☐ |

**Conditions éventuelles :**

_____________________________________________________________________________

_____________________________________________________________________________

---

## 7. Signatures

| Rôle | Nom | Date | Signature |
|------|-----|------|-----------|
| Représentant mairie / Owendo | | | |
| Superviseur recouvrement | | | |
| Chef de projet MAMI | | | |

---

## 8. Suite

En cas de **recette acceptée** :

1. Exécuter [PROCEDURE_CLOTURE_SPRINT3.md](PROCEDURE_CLOTURE_SPRINT3.md)
2. Mettre à jour [MAMI_2026_PROGRESS_TRACKER.md](MAMI_2026_PROGRESS_TRACKER.md) → P1 = 100 %
3. Autoriser officiellement le kick-off **Priorité 2 — Commerce & Services**

---

*Document à conserver dans le dossier de projet municipal et en copie numérique dans `docs/`.*
