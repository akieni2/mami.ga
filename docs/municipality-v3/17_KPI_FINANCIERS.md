# 17. KPI financiers

## 17.1 Vue d'ensemble

Indicateurs pour **Maire**, **finance** et **superviseurs** — calculés server-side, exposés dashboard (doc 9).

## 17.2 KPI exécutifs (Maire)

| KPI | Formule | Fréquence |
|-----|---------|-----------|
| **Encaissements jour** | `SUM(amount) WHERE status=completed AND DATE(collected_at)=today` | Temps réel |
| **Encaissements MTD** | SUM mois courant | Quotidien |
| **Encaissements YTD** | SUM année fiscale | Quotidien |
| **Taux recouvrement global** | `collected_MTD / assessed_MTD * 100` | Quotidien |
| **Solde impayé total** | `SUM(balance_due) WHERE status IN (overdue, partial)` | Temps réel |
| **Opérateurs à jour** | `COUNT status=current / COUNT active` | Quotidien |
| **Répartition canaux** | % cash, airtel, moov | Quotidien |

## 17.3 KPI opérationnels (superviseur)

| KPI | Formule |
|-----|---------|
| Quittances / agent / jour | COUNT receipts GROUP BY agent |
| Montant moyen / quittance | AVG(amount) |
| Taux conversion scan → paiement | payments / unique scans |
| Délai moyen sync offline | AVG(synced_at - device_collected_at) |
| Sessions caisse ouvertes > 12 h | COUNT |
| Écart caisse moyen | AVG(ABS(variance)) |
| Taux annulation | voided_amount / collected_amount |
| Taux remboursement | refund_amount / collected_amount |

## 17.4 KPI par zone économique

```
recovery_rate_zone = collected_zone_MTD / due_zone_MTD * 100
overdue_count_zone = COUNT operators WHERE fiscal_status=overdue AND zone_id=Z
density_impayes = overdue_count / area_km2
```

Alimente carte choroplèthe (doc 10).

## 17.5 KPI brigade (V3.4)

| KPI | Formule |
|-----|---------|
| Objectif campagne % | realized / target_amount |
| Couverture cibles | visited / total_targets |
| Yield visite | paid_visits / total_visits |

## 17.6 KPI qualité données

| KPI | Seuil alerte |
|-----|--------------|
| Paiements sans GPS | > 5 % |
| Sync failed rate | > 2 % |
| QR scan rejetés | pic > 10/h (formation) |
| MM pending > 1 h | COUNT > 0 |

## 17.7 Implémentation technique

### V3.0 — Requêtes live + cache Redis

```sql
-- Exemple taux recouvrement MTD
SELECT
  COALESCE(SUM(mp.amount), 0) AS collected,
  COALESCE(SUM(fo.amount_due - fo.amount_paid), 0) AS remaining
FROM fiscal_obligations fo
LEFT JOIN municipal_payments mp ON ...
WHERE fo.period_start >= start_of_month
```

### V3.3 — Table `fiscal_daily_snapshots`

| Colonne | Description |
|---------|-------------|
| `snapshot_date` | Date |
| `territory_id` | Owendo |
| `economic_zone_id` | nullable |
| `collected_amount` | |
| `assessed_amount` | |
| `recovery_rate` | |
| `metrics_json` | KPI secondaires |

Job `BuildFiscalDailySnapshotJob` à 00:30 Libreville.

## 17.8 Exports

| Format | Contenu |
|--------|---------|
| CSV | KPI quotidiens 30 jours |
| Excel | Détail par agent + zone |
| PDF | Rapport mensuel Maire (V3.3) |

## 17.9 Objectifs Owendo (indicatifs déploiement)

| Phase | Objectif taux recouvrement |
|-------|---------------------------|
| V3.0 (pilote 1 zone) | Baseline mesure |
| V3.2 (3 zones) | +15 % vs baseline |
| V3.5 (Owendo complet) | +30 % vs baseline |

## 17.10 Non double-comptage

- Paiements `voided` exclus des encaissements
- Remboursements `completed` déduits
- Pas de comptage `pending` ou `pending_sync` dans encaissements officiels
- MM : uniquement `completed`

## 17.11 Lien V2.5

Endpoint `v3_preparatory` remplacé progressivement ; mapping KPI :

| v3_preparatory | V3 KPI |
|----------------|--------|
| operators_count | Opérateurs actifs |
| qr_issued | QR actifs |
| visits_mtd | Visites terrain |
| payments_structure | Encaissements réels |
