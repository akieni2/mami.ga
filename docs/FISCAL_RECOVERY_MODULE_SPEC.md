# MAMI — Spécification Module Recouvrement Fiscal

**Version** : 1.0 (document de conception — **aucun code**)  
**Date** : juin 2026  
**Module** : `Municipality / FiscalRecovery`  
**Référence** : `GIS_ARCHITECTURE.md`, `GIS_DATABASE_DESIGN.md`

---

## 1. Objectif

Permettre aux services municipaux d'Owendo de :

1. **Identifier** les opérateurs économiques non conformes fiscalement  
2. **Prioriser** les zones et cibles de recouvrement  
3. **Organiser** des campagnes et visites de brigades  
4. **Suivre** les montants recouvrés vs en attente  
5. **Visualiser** l'état fiscal sur la carte SIG  

---

## 2. Statuts fiscaux

### 2.1 Définition

| Statut | Code | Couleur carte | Condition |
|--------|------|---------------|-----------|
| Contribuable à jour | `a_jour` | **VERT** `#43A047` | Aucune dette OU dernier paiement couvre période courante |
| Retard < 90 jours | `retard_90` | **ORANGE** `#FB8C00` | Dette > 0 ET retard ≤ 90 jours |
| Retard > 90 jours | `retard_plus_90` | **ROUGE** `#E53935` | Dette > 0 ET retard > 90 jours |
| Non enregistré | `non_enregistre` | **NOIR** `#212121` | Pas d'immatriculation communale valide |

### 2.2 Calcul (`TaxStatusResolverService`)

**Entrées** :
- `economic_operators.registration_date`
- Dernière ligne `economic_operator_tax_status`
- Somme `municipal_revenues` sur période fiscale courante
- Montant dû théorique (paramètre communal — `config/municipality.php`)

**Sortie** :
- Mise à jour `current_tax_status` sur l'opérateur
- Insertion historique `economic_operator_tax_status` si changement
- Event `TaxStatusChanged` → Reverb + `audit_logs`

**Fréquence recalcul** :
- À chaque encaissement (`municipal_revenues` créé)
- Job nocturne `RecalculateTaxStatusesJob` (batch)

---

## 3. Dashboard recouvrement

### 3.1 Indicateurs

| KPI | Source | Formule |
|-----|--------|---------|
| Nombre total d'opérateurs | `economic_operators` | COUNT active |
| Opérateurs à jour | | `current_tax_status = a_jour` |
| Opérateurs en retard | | `retard_90 OR retard_plus_90` |
| Non enregistrés | | `non_enregistre` |
| Montants recouvrés (mois) | `municipal_revenues` | SUM mois courant |
| Montants recouvrés (année) | | SUM année courante |
| Montants en attente | Calcul | SUM dettes ouvertes |
| Taux recouvrement | | recouvré / (recouvré + attente) |

### 3.2 API

`GET /api/municipality/recovery/dashboard` — voir `GIS_API_SPECIFICATION.md`

### 3.3 Interface web

Page `/admin/municipality/recovery` :
- Cartes KPI en haut
- Graphique évolution mensuelle recettes
- Carte zones prioritaires (embed SIG)
- Table top 20 opérateurs en retard

---

## 4. Zones prioritaires de recouvrement

### 4.1 Score de priorité

Par `municipal_sectors` et `economic_zones` :

```
priority_score = (
  0.35 × (opérateurs_retard_plus_90 / total_opérateurs_secteur) +
  0.25 × (montant_dettes_secteur / montant_dettes_commune) +
  0.20 × (opérateurs_non_enregistres / total) +
  0.10 × (densité_commerciale) +
  0.10 × (retard_par_zone_economique)
)
```

### 4.2 Visualisation carte

- Heatmap ou polygones secteurs colorés par `priority_score`
- Clic secteur → liste opérateurs à visiter
- Export PDF liste brigade (V1.1)

---

## 5. Campagnes de recouvrement

### 5.1 Cycle de vie

```
draft → active → paused → completed
```

### 5.2 Création campagne

**Champs** :
- Nom, description, dates
- Secteurs cibles (`target_sectors[]`)
- Statuts fiscaux cibles (`target_tax_statuses[]`)
- Montant objectif (`target_amount`)

**Automatique à l'activation** :
- Génération `recovery_visits` pour chaque opérateur correspondant
- Assignment suggéré aux brigades par secteur

### 5.3 Suivi campagne

| Métrique | Description |
|----------|-------------|
| Visites planifiées | COUNT recovery_visits |
| Visites complétées | status = completed |
| Montant collecté | SUM amount_collected |
| Taux de succès | visits paid / visits completed |

---

## 6. Visites de recouvrement

### 6.1 Planification

`recovery_visits` liées à :
- `recovery_campaigns`
- `economic_operators`
- `field_teams` (optionnel à la création, obligatoire avant visite)

### 6.2 Exécution terrain

Flux brigade :

1. Consulter liste visites du jour (`GET /municipality/recovery/visits?team_id=&date=today`)
2. Se rendre sur site → ouvrir fiche opérateur sur carte
3. Créer `field_intervention` type `fiscal_visit`
4. Enregistrer constat + photos (`attachments`)
5. Si paiement : `POST /municipality/operators/{id}/revenues`
6. Compléter visite : `POST /municipality/recovery/visits/{id}/complete`

**Body complete** :

```json
{
  "outcome": "paid",
  "amount_collected": 150000,
  "notes": "Reçu délivré",
  "field_intervention_id": 42
}
```

### 6.3 Outcomes

| Outcome | Effet |
|---------|-------|
| `paid` | Création `municipal_revenue` + recalcul statut fiscal |
| `promise` | Note + date promesse dans metadata |
| `refused` | `audit_logs` + statut inchangé |
| `absent` | Replanification automatique (+7 jours) |

---

## 7. Encaissements (`municipal_revenues`)

### 7.1 V1 — Enregistrement manuel

Agent ou brigade saisit montant + référence reçu.

### 7.2 V2 — Intégration `payments`

| Champ | Lien |
|-------|------|
| `municipal_revenues.payment_id` | → `payments` |
| `payable_type` | `economic_operator` |
| Méthodes | cash, airtel_money, moov_money |

---

## 8. Permissions

| Permission | Rôles |
|------------|-------|
| `municipality.recovery.view` | fiscal_officer, municipal_agent, admin |
| `municipality.recovery.manage` | fiscal_officer, admin |
| `municipality.recovery.collect` | field_agent, field_team_leader, fiscal_officer |
| `municipality.operators.manage` | municipal_agent, admin |

---

## 9. Notifications

| Événement | Destinataire | Canal |
|-----------|--------------|-------|
| Campagne activée | Brigades concernées | push + `notifications` |
| Visite assignée | Chef brigade | push |
| Paiement enregistré | fiscal_officer | email (optionnel) |
| Statut → rouge | fiscal_officer | dashboard alert |

---

## 10. Rapports

| Rapport | Fréquence | Format |
|---------|-----------|--------|
| État recouvrement mensuel | Mensuel | PDF + API |
| Performance brigades | Hebdomadaire | Web |
| Opérateurs non conformes | À la demande | CSV export |
| Carte fiscalité | Temps réel | SIG |

---

## 11. Intégration SIG

Couche `fiscal` sur `GET /gis/map` :
- Points opérateurs colorés par `current_tax_status`
- Filtre `tax_status=retard_plus_90`
- Clustering si > 100 points dans bbox

Légende carte :
- 🟢 À jour
- 🟠 Retard < 90j
- 🔴 Retard > 90j
- ⚫ Non enregistré

---

## 12. Données de référence Owendo (à fournir)

| Donnée | Exemple | Responsable |
|--------|---------|-------------|
| Montant taxe type par catégorie | Commerce 50 000 FCFA/mois | Commune |
| Période fiscale | Calendrier mensuel | Commune |
| Liste opérateurs existants | Export Excel | Services techniques |
| Brigades et secteurs | 4 brigades / 12 quartiers | Mairie |

---

## 13. Tests prévus

| Test | Scénario |
|------|----------|
| `TaxStatusResolverTest` | Calcul vert/orange/rouge/noir |
| `RecoveryCampaignActivationTest` | Génération visites |
| `RecoveryVisitCompletePaidTest` | Paiement → revenue → statut vert |
| `RecoveryDashboardTest` | KPIs corrects |
| `FiscalMapLayerTest` | Couleurs carte par statut |
| `TaxiNotAffectedTest` | Aucune table Taxi modifiée |

---

## 14. Hors périmètre V1

- Intégration DGI nationale  
- Paiement en ligne citoyen/opérateur  
- Relances SMS automatiques  
- Contentieux juridique  
- Multi-communes (hors Owendo)  

---

*Spécification module recouvrement — validation requise avant développement.*
