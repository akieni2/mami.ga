# Suivi d'avancement MAMI 2026

**Version :** 1.0  
**Date :** juin 2026  
**Document de référence :** [MAMI_2026_EXECUTION_PLAN.md](MAMI_2026_EXECUTION_PLAN.md)  
**Tag de référence :** `v1.6-roadmap-2026`

---

## IMPORTANT — Priorité absolue

**Le développement de Municipality V3 Sprint 3 est la priorité absolue.**

Aucun développement P2, P3, P4 ou P5 ne doit commencer tant que :

- Municipality V3 Sprint 3 n'est pas terminé ;
- les tests ne sont pas verts ;
- la validation terrain n'est pas effectuée ;
- le module n'est pas déclaré clôturé.

---

## Règle — Documentation avant amélioration

Toute suggestion d'amélioration, optimisation, nouvelle fonctionnalité ou refonte :

- doit être **documentée** ;
- doit être ajoutée dans la section **Idées reportées au backlog** ci-dessous ;
- **ne doit pas** être développée immédiatement ;
- **ne doit pas** modifier la feuille de route officielle 2026.

La feuille de route reste figée jusqu'à l'achèvement de la Priorité 5.

---

## Tableau de suivi

| Priorité | Module | Statut | % | Début | Fin prévue |
|----------|--------|--------|---|-------|------------|
| P1 | Municipality V3 Sprint 3 | En cours | 90 % | juin 2026 | juillet 2026 |
| P2 | Commerce & Services | Non démarré | 0 % | — | — |
| P3 | Main d'œuvre | Non démarré | 0 % | — | — |
| P4 | Covoiturage | Non démarré | 0 % | — | — |
| P5 | TM (Transport Marchandises) | Non démarré | 0 % | — | — |

### Détail Priorité 1 — Municipality V3 Sprint 3

| Livrable | Statut |
|----------|--------|
| Quittances PDF (A4 + thermique 58 mm) | ✅ Livré |
| Signature numérique (SHA-256) | ✅ Livré |
| Vérification publique (`/public/receipts/verify/{token}`) | ✅ Livré |
| Impression Bluetooth (Flutter agent) | ✅ Livré |
| Tests backend (~170 cas Municipality) | ✅ Livrés |
| Activation production (`MAMI_MODULE_MUNICIPALITY=true`) | ⏳ En attente |
| Validation terrain imprimante Bluetooth | ⏳ En attente |
| Recette mairie / procès-verbal signé | ⏳ En attente |
| Déclaration officielle « module clôturé » | ⏳ En attente |

**Prochaine action :** exécuter la checklist de clôture P1 décrite dans [MAMI_2026_EXECUTION_PLAN.md](MAMI_2026_EXECUTION_PLAN.md#14-critères-de-validation).

---

## Idées reportées au backlog

> Toute nouvelle idée est ajoutée ici. Aucun développement sans passage par ce backlog et sans clôture de la priorité en cours.

| ID | Idée / amélioration | Module concerné | Date | Phase suggérée |
|----|---------------------|-----------------|------|----------------|
| BL-01 | Mobile Money (Airtel, Moov) | Municipality V3.1+ | juin 2026 | Après clôture P1 |
| BL-02 | Sync offline SQLite agent | Municipality | juin 2026 | Après clôture P1 |
| BL-03 | Brigades terrain | Municipality V3.4 | juin 2026 | 2026 T4 |
| BL-04 | Route API remboursement quittance | Municipality | juin 2026 | Post-P1 |
| BL-05 | Paiement contribution covoiturage | Covoiturage | juin 2026 | Après P4 |
| BL-06 | Messagerie in-app Main d'œuvre | Main d'œuvre | juin 2026 | Après P3 |
| BL-07 | Scission APK Agent / Municipal Services | Infrastructure | juin 2026 | Q3 2026 |
| BL-08 | Tarification zone/surface | Municipality V3.5 | juin 2026 | 2026 T4 |
| BL-09 | Avis et modération avancée | Commerce | juin 2026 | Après P2 |
| BL-10 | Preuve livraison signature | TM | juin 2026 | Après P5 |

---

## Historique des mises à jour

| Date | Modification |
|------|--------------|
| juin 2026 | Création du tracker — référence tag `v1.6-roadmap-2026` |

---

*Ce document est mis à jour manuellement à chaque jalon. Il ne remplace pas le plan d'exécution ; il en suit l'avancement.*
