# Rapport — Correctif premier encaissement sans obligation fiscale (Sprint 3.2.7)

## Contexte terrain

| Étape | Statut |
|-------|--------|
| Scan QR commerce PANET (#5) | OK |
| Ouverture caisse | OK |
| Encaissement | **Échec** |

Tables vides pour le commerce :

- `fiscal_obligations`
- `operator_tax_assignments` (si recensement sans affectation taxe)

## Diagnostic

### `ObligationAllocationService::allocate()` (avant correctif)

1. Charge les obligations ouvertes / partielles avec `balance_due > 0`.
2. Si aucune obligation → `$remaining` reste égal au montant encaissé.
3. Lève :

```text
ValidationException
amount_xaf: Le montant dépasse le solde dû de l'opérateur.
```

Message trompeur : le problème n'est pas un surpaiement, mais **l'absence d'obligation fiscale**.

Côté Flutter, `collect_cash_screen.dart` masquait ce message derrière **« Encaissement refusé »**.

## Correctif appliqué

### Backend — `ObligationAllocationService`

| Cas | Comportement |
|-----|--------------|
| **A** — obligations ouvertes existantes | Allocation inchangée |
| **B** — aucune obligation, taxe(s) affectée(s) | Génération automatique via `FiscalObligationGeneratorService::generateForAssignment()`, puis allocation |
| **C** — aucune taxe affectée | `ValidationException` : `Aucune taxe n'est affectée à ce commerce.` |

Cas limite : taxe affectée mais sans barème actif → `Aucune taxe active applicable n'a été trouvée pour ce commerce.`

`FiscalCollectionService::collectCash()` transmet désormais l'agent (`$agent`) pour l'audit `obligation.created` lors de la génération à la volée.

**Non modifié :** workflow QR, ouverture/fermeture caisse, émission quittances, traçabilité paiement / `FieldVisit` / allocations.

### Flutter — `collect_cash_screen.dart`

Nouveau helper : `lib/core/network/api_error_message.dart`

- Extrait le premier message du champ `errors` Laravel (422).
- Fallback sur `ApiException.message` ou erreur réseau.

## Tests

### PHP — `tests/Feature/Municipality/FiscalCollectionTest.php`

| Test | Scénario |
|------|----------|
| `test_collection_with_existing_obligation_unchanged` | Cas A |
| `test_collection_without_obligation_but_with_tax_assignment_creates_initial_obligation` | Cas B |
| `test_collection_without_tax_assignment_is_rejected` | Cas C |
| `test_rejects_collection_for_inactive_operator` | Commerce inactif (message explicite) |

### Flutter — `test/core/network/api_error_message_test.dart`

Validation des messages API 422 et `ApiException`.

```bash
php artisan test --filter=FiscalCollectionTest
cd mobile/mami_client && flutter test test/core/network/api_error_message_test.dart
```

## Déploiement

1. Déployer l'API Laravel (`php artisan migrate` si migrations en attente).
2. Rebuilder l'APK client si encaissement terrain.
3. **Prérequis métier PANET :** affecter au moins une taxe active au commerce avant encaissement (sinon message explicite Cas C).

## Validation terrain PANET

1. Vérifier `operator_tax_assignments` pour le commerce #5.
2. Si vide → affecter une taxe via back-office fiscal.
3. Encaisser sans lancer `municipality:fiscal-generate` → obligation créée automatiquement à l'encaissement.
4. Confirmer quittance et paiement en base.
