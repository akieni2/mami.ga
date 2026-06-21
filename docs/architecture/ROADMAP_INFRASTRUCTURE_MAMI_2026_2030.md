# Roadmap Infrastructure MAMI 2026–2030

**Plateforme Municipale Unifiée — Mairie d'Owendo → Agglomération Libreville**

| | |
|---|---|
| **Version** | 1.0 |
| **Date** | juin 2026 |
| **Statut** | Document de référence — architecture cible |
| **Périmètre** | Infrastructure, hébergement, continuité, cybersécurité, monitoring |
| **Référence logicielle** | Tag `v1.0-recouvrement-terrain` · Sprint 4.0 Gouvernance financière |

**Destinataires :** Maire · Secrétaire Général · DAF · DSI · Trésor Public gabonais

**Documents associés (dossier `docs/architecture/`) :**

| Document | Rôle |
|----------|------|
| [ARCHITECTURE_LOGIQUE_MAMI.md](ARCHITECTURE_LOGIQUE_MAMI.md) | Couches applicatives et modules |
| [PRA_PCA_MAMI.md](PRA_PCA_MAMI.md) | Reprise et continuité d'activité détaillées |
| [CYBERSECURITE_MAMI.md](CYBERSECURITE_MAMI.md) | Politique de sécurité opérationnelle |

---

## Synthèse exécutive

MAMI (Mobility And Municipal Intelligence) est la **super-app municipale** portée par la Commune d'Owendo pour digitaliser le recensement économique, la fiscalité locale et le recouvrement terrain, avec extension prévue à l'échelle métropolitaine (Libreville et communes limitrophes).

La présente roadmap propose une montée en charge **progressive et maîtrisée** en trois phases (2026 → 2027–2028 → 2028–2030), depuis un VPS de développement actuel jusqu'à une architecture haute disponibilité multi-communes, compatible avec les exigences du Trésor Public en matière de traçabilité, reversement et audit.

---

## 1. Contexte

### 1.1 Présentation de MAMI

MAMI est une plateforme modulaire construite sur **Laravel 12** (API REST), **MySQL 8**, **Redis**, **Laravel Reverb** (WebSocket) et une application mobile **Flutter** pour les agents terrain. Le portail citoyen et le backoffice administratif sont exposés sur les domaines officiels :

| Service | URL |
|---------|-----|
| Portail citoyen | `https://mami.ga` |
| API REST | `https://api.mami.ga` |
| Administration | `https://admin.mami.ga` |
| WebSocket (temps réel) | `wss://ws.mami.ga` |

La gouvernance des accès repose sur un modèle **rôles / permissions** (agents municipaux, DAF, superviseurs, administrateurs).

### 1.2 Modules opérationnels ou en déploiement (2026)

| Module | Description | Maturité |
|--------|-------------|----------|
| **Recensement économique** | Enrôlement GPS des commerces, photos, catégories, zones | ✅ Production pilote Owendo |
| **Fiscalité municipale** | Moteur fiscal configurable (taxes, tarifs, obligations) | ✅ Opérationnel |
| **Recouvrement terrain** | Sessions caisse, encaissement espèces, quittances | ✅ Validé `v1.0-recouvrement-terrain` |
| **QR Commerce** | Identification commerce par scan (point d'entrée recouvrement) | ✅ Production |
| **Contrôles terrain** | Visites de contrôle (présence, licence, patente) | ✅ Sprint 3.4 |
| **Synchronisation** | État API, compteurs, préparation mode offline | ✅ Fondations V2.1 |
| **Gouvernance financière** | Missions terrain, supervision caisses, journal DAF | ✅ Sprint 4.0 |
| **Taxi** | Module transport MAMI Taxi V2 (courses, dispatch) | ✅ Parallèle / autre périmètre |

### 1.3 Modules futurs (2026–2030)

| Module | Horizon | Dépendances infrastructure |
|--------|---------|---------------------------|
| **DAF** (Directeur des Affaires Financières) | 2026–2027 | Tableaux de bord, journal, rôles — fondations Sprint 4.0 |
| **Comptabilité** | 2027 | Intégration écritures, export SYSCOHADA / Trésor |
| **Budget** | 2027–2028 | Prévision / exécution, lien recettes terrain |
| **RH** | 2028 | Agents, affectations, paie (interface) |
| **Prestataires** | 2028 | Marchés, factures, paiements |
| **État civil** | 2029+ | Registres, actes (haute criticité / conformité) |
| **Marchés municipaux** | 2029+ | Places, étals, redevances liées au recensement |

Chaque module futur **s'appuie sur la même API et la même base MySQL**, avec montée en charge progressive des ressources décrite ci-dessous.

---

## 2. État actuel (2026)

### 2.1 Infrastructure de développement / pré-production

| Composant | Spécification |
|-----------|---------------|
| OS | Ubuntu 24.04 LTS |
| Compute | 2 vCPU |
| Mémoire | 4 Go RAM |
| Stockage | 50 Go SSD |
| Stack | Laravel 12 · PHP 8.3 · MySQL 8 |
| Réseau | IP publique unique · HTTPS (Let's Encrypt) |

### 2.2 Limites identifiées

| Limite | Impact | Risque |
|--------|--------|--------|
| **4 Go RAM** | MySQL + PHP-FPM + Redis + Reverb sur un seul nœud → saturation mémoire | Lentitudes, OOM killer, indisponibilité |
| **2 vCPU** | Pic simultané : agents terrain + génération quittances + jobs queue | Timeouts API, files d'attente |
| **50 Go SSD** | Croissance `municipal_payments`, `municipal_receipts`, pièces jointes, logs | Disque plein |
| **Monolithique** | API + DB + cache + WS sur un VPS | Pas de redondance · SPOF |
| **Sauvegardes non industrialisées** | Restauration longue en cas incident | Perte de données possible |
| **Monitoring limité** | Détection tardive des pannes | SLA non garanti |
| **Montée charge modules DAF / compta** | Requêtes analytiques lourdes | Dégradation recouvrement terrain |

**Conclusion :** l'infrastructure actuelle est **adaptée au développement et au pilote Owendo** (quelques dizaines d'agents, centaines de commerces), **insuffisante pour une production municipale à l'échelle communale pleine**.

---

## 3. Phase Production Initiale (2026)

**Objectif :** mise en production stable pour **Owendo** — recouvrement terrain, QR, quittances, gouvernance DAF — avec séparation API / base / sauvegarde.

### 3.1 Architecture recommandée

#### Serveur API (APP)

| Ressource | Valeur |
|-----------|--------|
| vCPU | 8 |
| RAM | 32 Go |
| Stockage | 500 Go NVMe |
| Rôle | Nginx · PHP-FPM 8.3 · Laravel · Queue workers · Reverb |

#### Serveur Base de Données (DB)

| Ressource | Valeur |
|-----------|--------|
| vCPU | 8 |
| RAM | 32 Go |
| Stockage | 1 To NVMe |
| Rôle | MySQL 8 (InnoDB) · réplication future prête |

#### Serveur Sauvegarde (BKP)

| Ressource | Valeur |
|-----------|--------|
| vCPU | 4 |
| RAM | 8 Go |
| Stockage | 2 To (objet ou bloc) |
| Rôle | Sauvegardes quotidiennes · archives · restauration |

#### Services logiciels

| Service | Fonction |
|---------|----------|
| **Nginx** | Terminaison TLS, reverse proxy, rate limiting |
| **PHP-FPM** | Exécution Laravel (pools dédiés API / admin) |
| **MySQL 8** | Données transactionnelles (recouvrement, fiscalité) |
| **Redis** | Cache, sessions, queues Laravel, pub/sub Reverb |
| **Laravel Queue** | Jobs async (obligations fiscales, notifications, PDF) |
| **Reverb** | WebSocket (suivi courses taxi, notifications temps réel) |

### 3.2 Schéma ASCII — Phase 2026

```
                    [ Agents Flutter ]     [ Citoyens Web ]
                            |                    |
                            v                    v
                      +-----------+      +-----------+
                      |  Internet |      |  Internet |
                      +-----+-----+      +-----+-----+
                            |                  |
                            v                  v
                    +-------+------------------+-------+
                    |         Nginx (TLS)              |
                    |  api.mami.ga | admin.mami.ga    |
                    |  mami.ga     | ws.mami.ga        |
                    +-------+------------------+-------+
                            |
            +---------------+---------------+
            |                               |
            v                               v
   +----------------+              +----------------+
   |  SERVEUR API   |              |  (même API ou  |
   |  8 vCPU / 32Go |              |   CDN statique)|
   |  PHP-FPM       |              +----------------+
   |  Queue Workers |
   |  Reverb        |
   +-------+--------+
           |
           | 3306 (réseau privé)
           v
   +----------------+
   |  SERVEUR DB    |
   |  8 vCPU / 32Go |
   |  MySQL 8       |
   |  1 To NVMe     |
   +-------+--------+
           |
           | dump chiffré / réplication log
           v
   +----------------+
   | SERVEUR BKP    |
   | 4 vCPU / 8 Go  |
   | 2 To stockage  |
   +----------------+
```

### 3.3 Capacité estimée (phase 2026)

| Indicateur | Cible Owendo production |
|------------|-------------------------|
| Opérateurs économiques | 5 000 – 15 000 |
| Agents terrain | 50 – 150 |
| Quittances / an | 50 000 – 200 000 |
| Utilisateurs concurrents API | 100 – 300 |

---

## 4. Phase Extension (2027–2028)

### 4.1 Hypothèses de charge

| Métrique | Valeur cible |
|----------|--------------|
| Opérateurs économiques enregistrés | **100 000** |
| Agents municipaux actifs | **500** |
| Quittances cumulées | **1 000 000** |
| Communes connectées | Owendo + communes partenaires |
| Modules actifs | DAF, comptabilité, budget (v1) |

### 4.2 Architecture cible

| Composant | Rôle |
|-----------|------|
| **Load Balancer** | Répartition HTTPS, health checks, SSL |
| **WEB01 / WEB02** | Deux nœuds API identiques (8 vCPU / 32 Go chacun) |
| **DB01** | MySQL primary (lecture/écriture) |
| **DB02** (option) | Réplica lecture seule (reporting DAF) |
| **Redis** | Instance dédiée ou cluster 3 nœuds |
| **Object Storage** | PDF quittances, photos recensement, exports |

### 4.3 Schéma ASCII — Phase 2027–2028

```
 [ Mobile ] [ Web ] [ Admin DAF ]
        \      |      /
         v     v     v
    +---------------------+
    |   LOAD BALANCER     |
    |   (HTTPS / HAProxy) |
    +----------+----------+
               |
       +-------+-------+
       |               |
       v               v
 +-----------+   +-----------+
 |   WEB01   |   |   WEB02   |
 | Laravel   |   | Laravel   |
 | Queue     |   | Queue     |
 | Reverb*   |   | Reverb*   |
 +-----+-----+   +-----+-----+
       |               |
       +-------+-------+
               |
       +-------+-------+
       |               |
       v               v
 +-----------+   +-----------+
 |   Redis   |   |  Object   |
 |           |   |  Storage  |
 +-----------+   +-----------+
       |
       v
 +-----------+     réplication async
 |   DB01    | --------------------> DB02 (read replica)
 |  PRIMARY  |
 +-----------+
       |
       v
 +-----------+
 |    BKP    |
 +-----------+

* Reverb : sticky sessions ou broker Redis partagé
```

### 4.4 Stratégie de réplication MySQL

| Élément | Recommandation |
|---------|----------------|
| **Mode** | Réplication asynchrone MySQL 8 (binlog ROW) |
| **Primary (DB01)** | Toutes écritures : encaissements, quittances, missions |
| **Replica (DB02)** | Lectures analytiques DAF, exports comptables, BI |
| **Bascule** | Promotion manuelle replica → primary (PRA documenté) |
| **Lag monitoring** | Alerte si retard > 30 s |
| **Sauvegardes** | Full hebdomadaire + binlog continu sur BKP |

**Principe :** le recouvrement terrain reste sur le primary ; les modules DAF / comptabilité consomment le replica pour ne pas dégrader les encaissements.

---

## 5. Phase Métropolitaine (2028–2030)

### 5.1 Couverture territoriale

| Commune | Rôle |
|---------|------|
| **Libreville** | Chef-lieu · volume maximal |
| **Owendo** | Pilote historique MAMI |
| **Akanda** | Extension nord |
| **Ntoum** | Extension grande couronne |

Architecture **multi-tenant logique** : une instance MAMI, séparation par `municipal_territories` / communes, données isolées par clé territoriale.

### 5.2 Architecture haute disponibilité

| Composant | Quantité | Rôle |
|-----------|----------|------|
| Load Balancers | 2 (actif / passif ou actif-actif) | Entrée Internet redondante |
| Web Servers | 3 | Laravel horizontal scaling |
| DB Servers | 3 | Primary + 2 replicas (ou Galera/InnoDB Cluster*) |
| Redis Cluster | 3 nœuds minimum | Cache + queues haute dispo |
| Object Storage | S3-compatible | Fichiers, sauvegardes froides |
| Monitoring | Stack dédiée | Grafana · Prometheus · alertes |

\* *Choix Galera vs réplication async : décision DSI + Trésor selon RPO/RTO cibles (voir PRA_PCA_MAMI.md).*

### 5.3 Schéma ASCII — Phase 2028–2030

```
                         [ Internet ]
                              |
              +---------------+---------------+
              |                               |
              v                               v
        +-----------+                   +-----------+
        |    LB1    |<---- failover ---->|    LB2    |
        +-----+-----+                   +-----+-----+
              |                               |
              +---------------+---------------+
                              |
              +-------+-------+-------+
              |       |               |
              v       v               v
          +------+ +------+       +------+
          | WEB1 | | WEB2 |       | WEB3 |
          +---+--+ +---+--+       +---+--+
              |       |               |
              +-------+-------+-------+
                      |
          +-----------+-----------+
          |           |           |
          v           v           v
      +--------+ +--------+ +--------+
      | Redis  | | Redis  | | Redis  |
      | node 1 | | node 2 | | node 3 |
      +--------+ +--------+ +--------+
                      |
          +-----------+-----------+
          |                       |
          v                       v
      +--------+              +--------+
      |  DB P  |<-- sync -->  | DB R1  |
      +--------+              +--------+
          |                       |
          +-----------+-----------+
                      |
                      v
                  +--------+
                  | DB R2  |
                  +--------+
                      |
                      v
              +---------------+
              | Object Storage|
              | + Monitoring  |
              +---------------+
```

### 5.4 Capacité cible

| Indicateur | Ordre de grandeur |
|------------|-------------------|
| Opérateurs économiques | 250 000 – 400 000 |
| Agents | 1 500 – 2 500 |
| Quittances / an | 3 – 5 millions |
| Disponibilité visée | 99,5 % (hors maintenance planifiée) |

---

## 6. Sauvegarde et Continuité d'Activité

### 6.1 Politique de sauvegarde

| Type | Fréquence | Contenu | Rétention |
|------|-----------|---------|-----------|
| **Incrémentielle DB** | Quotidienne (nuit) | Dump MySQL + binlogs | 30 jours |
| **Complète système** | Hebdomadaire | DB + fichiers storage + configs | 12 semaines |
| **Hors site** | Quotidienne (sync) | Copies chiffrées vers BKP géographiquement distinct | 90 jours |
| **Archive annuelle** | Annuelle | Exercice fiscal clôturé | 10 ans (obligation comptable) |

### 6.2 Chiffrement et intégrité

- Sauvegardes chiffrées **AES-256** (clés hors serveur production)
- Vérification **restauration test** trimestrielle (commune pilote)
- Journal des opérations de backup dans le SIEM / logs centralisés

### 6.3 PRA — Plan de Reprise d'Activité

| Paramètre | Cible phase 2026 | Cible phase 2028–2030 |
|-----------|------------------|------------------------|
| **RPO** (perte de données max.) | 24 h | 1 h |
| **RTO** (remise en service) | 8 h | 2 h |
| Scénario 1 | Panne serveur API | Bascule LB → WEB02 |
| Scénario 2 | Corruption DB | Restauration BKP + binlog |
| Scénario 3 | Ransomware | Restauration hors site + isolement réseau |

Détail opérationnel : [PRA_PCA_MAMI.md](PRA_PCA_MAMI.md).

### 6.4 PCA — Plan de Continuité d'Activité

- **Mode dégradé recouvrement** : encaissement avec sync différée (APK offline V2.1+) — quittances numérotées localement, réconciliation à la reconnexion
- **Communication de crise** : procédure SG → agents → commerces
- **Priorité de service** : API recouvrement > portail vérification quittance > admin > taxi

---

## 7. Cybersécurité

| Mesure | Phase 2026 | Phase 2028–2030 |
|--------|------------|-----------------|
| **HTTPS** | TLS 1.2+ sur tous les domaines | TLS 1.3 · HSTS |
| **MFA administrateurs** | Obligatoire admin / DAF / DSI | + agents superviseurs |
| **Chiffrement sauvegardes** | Oui | Oui + rotation clés |
| **Journalisation** | `audit_logs`, journal financier Sprint 4.0 | Centralisation SIEM |
| **Audit** | Revue trimestrielle accès | Audit annuel + Trésor |
| **Gestion des rôles** | RBAC Laravel (seeders, moindre privilège) | IAM fédéré (option) |
| **Bastion d'administration** | SSH uniquement via bastion · clés · fail2ban | Bastion HA + session recording |
| **WAF / rate limiting** | Nginx + Laravel throttle | WAF dédié devant LB |
| **Secrets** | `.env` hors git · vault progressif | HashiCorp Vault ou équivalent |

Référence : [CYBERSECURITE_MAMI.md](CYBERSECURITE_MAMI.md).

---

## 8. Monitoring

### 8.1 Stack recommandée

| Outil | Usage |
|-------|-------|
| **Prometheus** | Métriques (CPU, RAM, latence API, queue depth, MySQL) |
| **Grafana** | Tableaux de bord DSI / DAF (recettes, dispo, perf) |
| **Uptime Kuma** | Sonde externe disponibilité `api.mami.ga`, `mami.ga` |
| **Laravel Telescope / logs** | Debug contrôlé (non prod) · logs structurés prod |
| **Alertmanager** | Routage alertes |

### 8.2 Alertes

| Canal | Destinataires | Exemples |
|-------|---------------|----------|
| **Email** | DSI, DAF adjoint | Disque > 80 %, queue bloquée |
| **SMS** | Astreinte DSI | API down > 5 min, DB unreachable |
| **Webhook** | SG (option) | Incident majeur recouvrement |

### 8.3 KPI infrastructure

| KPI | Seuil alerte |
|-----|--------------|
| Disponibilité API | < 99 % / mois |
| Temps réponse P95 `/api/municipality/*` | > 2 s |
| Lag réplication MySQL | > 60 s |
| Taux erreur 5xx | > 1 % |

---

## 9. Budget indicatif

*Montants **indicatifs** (hébergement cloud type OVH / Hetzner / AWS af-south ou équivalent local), hors licence, formation et prestations intégration. À affiner par appel d'offres DSI.*

### 9.1 Phase Production Initiale (2026) — annuel

| Poste | Détail | Fourchette (USD / an) |
|-------|--------|------------------------|
| Serveur API | 8 vCPU · 32 Go · 500 Go NVMe | 1 800 – 3 600 |
| Serveur DB | 8 vCPU · 32 Go · 1 To NVMe | 2 400 – 4 800 |
| Serveur BKP | 4 vCPU · 8 Go · 2 To | 600 – 1 200 |
| Bande passante / IP / DNS | mami.ga · certificats | 200 – 500 |
| Monitoring (SaaS ou self-hosted) | Uptime + métriques | 300 – 800 |
| **Total infrastructure 2026** | | **5 300 – 10 900** |

*Équivalent indicatif : **3,5 – 7 M FCFA / an** (taux ~600 FCFA/USD).*

### 9.2 Phase Extension (2027–2028) — annuel

| Poste | Fourchette (USD / an) |
|-------|------------------------|
| Load Balancer + 2× WEB + DB primary + replica | 12 000 – 22 000 |
| Redis dédié + Object Storage (1–5 To) | 2 000 – 5 000 |
| Sauvegarde renforcée + PRA test | 1 500 – 3 000 |
| Monitoring / SIEM léger | 1 000 – 2 500 |
| **Total 2027–2028** | **16 500 – 32 500** |

### 9.3 Phase Métropolitaine (2028–2030) — annuel

| Poste | Fourchette (USD / an) |
|-------|------------------------|
| 2 LB + 3 WEB + cluster DB (3 nœuds) | 35 000 – 60 000 |
| Redis Cluster + Object Storage 20+ To | 8 000 – 15 000 |
| Monitoring HA + astreinte outillage | 3 000 – 6 000 |
| Continuité (BKP geo-redondant) | 4 000 – 8 000 |
| **Total 2028–2030** | **50 000 – 89 000** |

### 9.4 Coûts transverses (toutes phases)

| Poste | Commentaire |
|-------|-------------|
| DSI / exploitation | 1–2 ETP dédiés dès 2027 |
| Formation agents | Budget annuel commune |
| Audit sécurité | Tous les 2 ans minimum |
| Évolution applicative | Sprints MAMI (hors infra pure) |

---

## 10. Conclusion

### 10.1 Vision cible

**Plateforme Municipale Unifiée MAMI** : un socle numérique unique pour Owendo puis l'agglomération librevilloise, où le recouvrement terrain validé en 2026 (QR, quittances, traçabilité) alimente directement la **gouvernance financière**, la **comptabilité** et le **reversement au Trésor Public**, dans un cadre sécurisé, sauvegardé et monitoré.

### 10.2 Feuille de route synthétique

| Année | Jalons infrastructure | Jalons métier |
|-------|----------------------|---------------|
| **2026** | Séparation API / DB / BKP · production Owendo | Recouvrement QR · DAF v1 · reversement (préparation) |
| **2027** | LB + 2 WEB · replica MySQL | Comptabilité · budget v1 |
| **2028** | Redis cluster · object storage | Multi-communes · RH · prestataires |
| **2029–2030** | HA métropolitaine · 99,5 % SLA | État civil · marchés · intégration Trésor |

### 10.3 Recommandations immédiates (Q3–Q4 2026)

1. **Provisionner** les trois serveurs phase 2026 et migrer depuis le VPS 2 vCPU / 4 Go.
2. **Industrialiser** sauvegardes quotidiennes chiffrées et test de restauration.
3. **Activer** monitoring (Uptime Kuma + métriques de base) avant montée en charge agents.
4. **Documenter** PRA/PCA avec le SG et le Trésor ([PRA_PCA_MAMI.md](PRA_PCA_MAMI.md)).
5. **Maintenir** la compatibilité applicative : chaque sprint métier (DAF, compta) doit rester déployable sans refonte infra tant que la phase 2026 n'est pas saturée.

---

*Document établi par l'équipe MAMI.ga — Infrastructure & Municipality V3.*  
*Pour visa : Maire _______________  Date _______________*  
*Pour visa : Secrétaire Général _______________  Date _______________*  
*Pour visa : DAF _______________  Date _______________*  
*Pour visa : DSI _______________  Date _______________*  
*Pour visa : Trésor Public ( représentant ) _______________  Date _______________*
