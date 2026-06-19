# Checklist de validation terrain — Municipality V3 Sprint 3

**Version :** 1.0  
**Date :** juin 2026  
**Zone pilote recommandée :** Marché Central Owendo (ou zone économique validée par la mairie)  
**Participants :** 1 agent municipal, 1 superviseur, 1 commerçant pilote

---

## Prérequis

- [ ] [CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md](CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md) complétée
- [ ] APK agent installé sur terminal Android
- [ ] Imprimante thermique Bluetooth 58 mm chargée et appairée
- [ ] Compte agent municipal actif (`municipal_agent`)
- [ ] Opérateur économique pilote enrôlé avec QR valide
- [ ] Taxes affectées et obligations générées pour l'opérateur pilote
- [ ] Connexion réseau mobile ou Wi-Fi stable sur le terrain

---

## Matériel

| Élément | Modèle / référence | OK |
|---------|-------------------|-----|
| Smartphone agent | Android ≥ 10 | ☐ |
| Imprimante thermique | 58 mm ESC/POS Bluetooth | ☐ |
| Papier thermique | Rouleau 58 mm | ☐ |
| Smartphone vérification QR | Tout appareil avec caméra | ☐ |

> **Note :** noter le modèle d'imprimante testé pour le rapport de clôture (ex. Zjiang, MPT-II, etc.).

---

## Scénario 1 — Préparation agent

| # | Étape | Résultat attendu | OK |
|---|-------|------------------|-----|
| 1.1 | Ouvrir APK MAMI Client | Connexion possible | ☐ |
| 1.2 | Se connecter compte agent | Accès hub Agent / Recouvrement | ☐ |
| 1.3 | Naviguer vers Recouvrement | Hub recouvrement affiché | ☐ |
| 1.4 | Ouvrir session de caisse | Session ouverte, fond de caisse saisi | ☐ |
| 1.5 | Vérifier GPS actif | Position agent enregistrée | ☐ |

---

## Scénario 2 — Scan et situation fiscale

| # | Étape | Résultat attendu | OK |
|---|-------|------------------|-----|
| 2.1 | Scanner QR du commerce pilote | Opérateur identifié (`OWE-COM-*`) | ☐ |
| 2.2 | Affichage fiche commerce | Nom, adresse, catégorie corrects | ☐ |
| 2.3 | Consultation situation fiscale | Obligations et montants dus visibles | ☐ |
| 2.4 | Montant cohérent avec backoffice | Correspond aux taxes affectées | ☐ |

---

## Scénario 3 — Encaissement et quittance

| # | Étape | Résultat attendu | OK |
|---|-------|------------------|-----|
| 3.1 | Saisir montant encaissé | Montant ≤ solde dû | ☐ |
| 3.2 | Confirmer encaissement espèces | Succès, pas d'erreur API | ☐ |
| 3.3 | Quittance générée | Référence `OWE-RCP-2026-XXXXXX` | ☐ |
| 3.4 | Navigation automatique vers impression | Écran impression affiché | ☐ |
| 3.5 | Vérifier `print_payload` API | Hash, token, montant présents | ☐ |

**Référence quittance :** _________________________  
**Montant encaissé (FCFA) :** _________________________  
**Date / heure :** _________________________

---

## Scénario 4 — Impression Bluetooth 58 mm

| # | Étape | Résultat attendu | OK |
|---|-------|------------------|-----|
| 4.1 | Sélectionner imprimante appairée | Connexion Bluetooth établie | ☐ |
| 4.2 | Lancer impression | Ticket sort sans bourrage | ☐ |
| 4.3 | En-tête commune Owendo | Lisible | ☐ |
| 4.4 | Référence quittance | Correspond à C.3 | ☐ |
| 4.5 | Nom commerce | Correct | ☐ |
| 4.6 | Montant et période | Corrects | ☐ |
| 4.7 | Nom agent | Correct | ☐ |
| 4.8 | Hash document (extrait) | Présent | ☐ |
| 4.9 | QR code imprimé | Visible et contrasté | ☐ |
| 4.10 | Largeur 58 mm | Texte non tronqué | ☐ |

**Modèle imprimante testé :** _________________________

---

## Scénario 5 — Vérification publique QR

| # | Étape | Résultat attendu | OK |
|---|-------|------------------|-----|
| 5.1 | Scanner QR sur ticket imprimé | Ouverture navigateur | ☐ |
| 5.2 | URL | `https://mami.ga/public/receipts/verify/...` | ☐ |
| 5.3 | Statut affiché | **Quittance valide** | ☐ |
| 5.4 | Données affichées | Référence, montant, commerce cohérents | ☐ |
| 5.5 | Test sans connexion agent | Page accessible au public | ☐ |

---

## Scénario 6 — Réimpression et historique

| # | Étape | Résultat attendu | OK |
|---|-------|------------------|-----|
| 6.1 | Accéder « Mes quittances » | Quittance test listée | ☐ |
| 6.2 | Réimprimer quittance | Nouvelle impression OK | ☐ |
| 6.3 | Vérifier audit backend | `reprint_count` incrémenté | ☐ |
| 6.4 | Nouvelle version document | Entrée `municipal_receipt_documents` | ☐ |

---

## Scénario 7 — Superviseur (optionnel mais recommandé)

| # | Étape | Résultat attendu | OK |
|---|-------|------------------|-----|
| 7.1 | Connexion admin `https://admin.mami.ga` | Dashboard accessible | ☐ |
| 7.2 | Dashboard maire `/admin/municipality/mayor` | KPIs mis à jour | ☐ |
| 7.3 | Quittance test visible | Montant reflété | ☐ |
| 7.4 | Annulation quittance test (admin) | Statut **annulée** | ☐ |
| 7.5 | Re-scan QR quittance annulée | Statut **annulée** en ligne | ☐ |

---

## Scénario 8 — Sécurité terrain

| # | Test | Résultat attendu | OK |
|---|------|------------------|-----|
| 8.1 | Agent tente annulation quittance | **Refusé** (403) | ☐ |
| 8.2 | Citoyen accède API fiscal sans rôle | **Refusé** (403) | ☐ |
| 8.3 | Encaissement sans session ouverte | **Refusé** | ☐ |

---

## Scénario 9 — Clôture session de caisse

| # | Étape | Résultat attendu | OK |
|---|-------|------------------|-----|
| 9.1 | Fermer session de caisse | Clôture enregistrée | ☐ |
| 9.2 | Total encaissé cohérent | Correspond aux encaissements | ☐ |
| 9.3 | Nouvelle session impossible sans réouverture | Comportement correct | ☐ |

---

## Anomalies constatées

| # | Description | Gravité | Action | Backlog ID |
|---|-------------|---------|--------|------------|
| 1 | | Bloquante / Mineure | | |
| 2 | | | | |
| 3 | | | | |

> **Règle :** toute amélioration ou correction non bloquante → backlog, pas de développement immédiat hors Sprint 3.

---

## Validation terrain

| Rôle | Nom | Date | Signature | Parcours complet OK |
|------|-----|------|-----------|---------------------|
| Agent municipal | | | | ☐ |
| Superviseur | | | | ☐ |
| Commerçant pilote | | | | ☐ |

**Validation terrain acceptée :** ☐ Oui — ☐ Non (réserves : _______________)

**Prochaine étape :** [CHECKLIST_RECETTE_MAIRIE_SPRINT3.md](CHECKLIST_RECETTE_MAIRIE_SPRINT3.md)
