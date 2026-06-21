# Rapport de recette — Sprint 4.1 Visa financier

**Date :** juin 2026 · **Version :** 4.1 · **Environnement cible :** VPS Owendo

---

## Scénario principal

| # | Étape | Résultat attendu | Statut |
|---|-------|------------------|--------|
| 1 | DAF adjoint crée mission brouillon | `workflow_status=draft` | ☐ |
| 2 | Soumission | `submitted` + entrée journal `mission.submitted` | ☐ |
| 3 | Contrôleur valide | `controller_review` | ☐ |
| 4 | DAF revue | `daf_review` | ☐ |
| 5 | DAF approuve | `approved` + `status=authorized` | ☐ |
| 6 | Agent ouvre caisse | Session liée à mission | ☐ |
| 7 | Encaissement terrain | Quittance Bluetooth OK | ☐ |
| 8 | Clôture mission | `closed` | ☐ |

---

## Non-régression

| Parcours | Statut |
|----------|--------|
| Scan QR → situation fiscale | ☐ |
| Encaissement sans mission (flag off) | ☐ |
| Legacy `POST /authorize` (flag on) | ☐ |
| DAF dashboard Sprint 4.0 | ☐ |
| Supervision caisses | ☐ |

---

## Rejet

| # | Vérification | Statut |
|---|--------------|--------|
| 1 | Rejet contrôleur avec motif ≥ 10 car. | ☐ |
| 2 | Motif immuable en base | ☐ |
| 3 | Historique `financial_mission_approvals` | ☐ |

---

## Sécurité

| # | Contrôle | Statut |
|---|----------|--------|
| 1 | Même user ne peut pas submit + approve | ☐ |
| 2 | Agent terrain sans permission workflow | ☐ |
| 3 | Journal non supprimable | ☐ |

---

## Signatures

| Rôle | Nom | Date |
|------|-----|------|
| DAF | | |
| Contrôleur financier | | |
| DSI | | |
