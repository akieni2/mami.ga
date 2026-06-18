<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\PaymentMethod;
use App\Modules\Municipality\Enums\PaymentStatus;
use App\Modules\Municipality\Enums\VisitType;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\FieldVisit;
use App\Modules\Municipality\Models\MunicipalPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FiscalCollectionService
{
    public function __construct(
        private readonly CashSessionService $cashSessionService,
        private readonly ObligationAllocationService $allocationService,
        private readonly PaymentOrchestratorService $paymentOrchestrator,
        private readonly FiscalAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{municipal_payment: MunicipalPayment, allocations: mixed}
     */
    public function collectCash(User $agent, array $data): array
    {
        $this->validateGps($data);

        $operator = EconomicOperator::query()->findOrFail($data['operator_id']);

        if (! $operator->is_active || $operator->trashed()) {
            throw ValidationException::withMessages([
                'operator_id' => ['Commerce inactif — encaissement refusé.'],
            ]);
        }

        $session = $this->cashSessionService->assertSessionOpenForAgent(
            $agent,
            (int) $data['cash_session_id'],
        );

        $amount = round((float) $data['amount_xaf'], 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount_xaf' => ['Le montant doit être positif.'],
            ]);
        }

        if (! empty($data['client_operation_id'])) {
            $existing = MunicipalPayment::query()
                ->where('client_operation_id', $data['client_operation_id'])
                ->first();

            if ($existing !== null) {
                return [
                    'municipal_payment' => $existing->load(['allocations.fiscalObligation', 'corePayment']),
                    'allocations' => $existing->allocations,
                ];
            }
        }

        return DB::transaction(function () use ($agent, $operator, $session, $amount, $data): array {
            $allocations = $this->allocationService->allocate($operator, $amount);

            $municipalPayment = MunicipalPayment::query()->create([
                'operator_id' => $operator->id,
                'agent_id' => $agent->id,
                'cash_session_id' => $session->id,
                'amount' => $amount,
                'payment_method' => PaymentMethod::Cash,
                'payment_period' => now()->format('Y-m'),
                'status' => PaymentStatus::Completed,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'gps_accuracy_m' => $data['gps_accuracy_m'] ?? null,
                'device_id' => $data['device_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'collected_at' => now(),
                'client_operation_id' => $data['client_operation_id'] ?? (string) Str::uuid(),
            ]);

            $corePayment = $this->paymentOrchestrator->createCorePayment($agent, $municipalPayment, [
                'gps' => [
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'accuracy_m' => $data['gps_accuracy_m'] ?? null,
                ],
            ]);

            $municipalPayment->update(['core_payment_id' => $corePayment->id]);

            $this->allocationService->apply($municipalPayment, $allocations);

            $session->update([
                'expected_amount_xaf' => $this->cashSessionService->calculateExpectedAmount($session),
            ]);

            FieldVisit::query()->create([
                'operator_id' => $operator->id,
                'agent_id' => $agent->id,
                'cash_session_id' => $session->id,
                'municipal_payment_id' => $municipalPayment->id,
                'visit_type' => VisitType::Payment,
                'visit_date' => now()->toDateString(),
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->audit->log($agent, $municipalPayment, 'municipal_payment', 'payment.collected', [
                'operator_public_id' => $operator->public_id,
                'amount_xaf' => (string) $amount,
                'cash_session_reference' => $session->reference,
            ]);

            return [
                'municipal_payment' => $municipalPayment->fresh([
                    'allocations.fiscalObligation.taxType',
                    'corePayment',
                    'operator',
                    'cashSession',
                ]),
                'allocations' => $municipalPayment->allocations,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateGps(array $data): void
    {
        if (! isset($data['latitude'], $data['longitude'])) {
            throw ValidationException::withMessages([
                'gps' => ['La position GPS est obligatoire.'],
            ]);
        }

        $maxAccuracy = (float) config('mami.municipality_collection_max_gps_accuracy_m', 50);

        if (! isset($data['gps_accuracy_m']) || (float) $data['gps_accuracy_m'] > $maxAccuracy) {
            throw ValidationException::withMessages([
                'gps_accuracy_m' => ["Précision GPS insuffisante (max {$maxAccuracy} m)."],
            ]);
        }
    }
}
