<?php

namespace Tests\Feature\Municipality\Concerns;

use App\Models\User;
use App\Modules\Municipality\Enums\BillingPeriod;
use App\Modules\Municipality\Enums\FiscalObligationType;
use App\Modules\Municipality\Models\CashSession;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorCategory;
use App\Modules\Municipality\Models\FiscalObligation;
use App\Modules\Municipality\Models\MunicipalTaxRate;
use App\Modules\Municipality\Models\MunicipalTaxType;
use App\Modules\Municipality\Models\OperatorTaxAssignment;
use App\Modules\Municipality\Services\CashSessionService;
use App\Modules\Municipality\Services\FiscalAssignmentService;
use App\Modules\Municipality\Services\FiscalObligationGeneratorService;
use App\Modules\Municipality\Services\TaxRateService;
use App\Modules\Municipality\Services\TaxTypeService;
use Database\Seeders\EconomicOperatorCategorySeeder;
use Database\Seeders\EconomicZoneSeeder;

trait FiscalTestHelpers
{
    protected function seedEconomicRegistry(): void
    {
        $this->seed(EconomicOperatorCategorySeeder::class);
        $this->seed(EconomicZoneSeeder::class);
    }

    protected function createTaxType(User $user, string $code = 'TAX-COMMERCE', string $name = 'Taxe commerce'): MunicipalTaxType
    {
        return app(TaxTypeService::class)->create($user, [
            'code' => $code,
            'name' => $name,
            'description' => 'Test tax',
        ]);
    }

    protected function createTaxRate(User $user, MunicipalTaxType $taxType, float $amount = 15000, BillingPeriod $period = BillingPeriod::Monthly): MunicipalTaxRate
    {
        return app(TaxRateService::class)->create($user, $taxType, [
            'amount_xaf' => $amount,
            'billing_period' => $period->value,
            'valid_from' => now()->startOfYear()->toDateString(),
        ]);
    }

    protected function createOperator(User $user): EconomicOperator
    {
        $categoryId = EconomicOperatorCategory::query()->where('slug', 'boutique')->value('id');
        $territoryId = $this->territoryId();

        return EconomicOperator::query()->create([
            'public_id' => 'OWE-COM-'.str_pad((string) (EconomicOperator::query()->count() + 1), 6, '0', STR_PAD_LEFT),
            'territory_id' => $territoryId,
            'category_id' => $categoryId,
            'commercial_name' => 'Boutique Test',
            'activity_label' => 'Commerce',
            'responsible_name' => 'Test User',
            'phone' => '+24106000099',
            'latitude' => 0.3380,
            'longitude' => 9.4710,
            'gps_accuracy_m' => 5,
            'gps_captured_at' => now(),
            'registration_date' => now()->toDateString(),
            'registered_by' => $user->id,
            'is_active' => true,
        ]);
    }

    protected function assignTax(User $user, EconomicOperator $operator, MunicipalTaxType $taxType): OperatorTaxAssignment
    {
        return app(FiscalAssignmentService::class)->assign($user, $operator, $taxType);
    }

    protected function generateObligations(User $user): void
    {
        app(FiscalObligationGeneratorService::class)->generate($user);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function openCashSession(User $user, array $overrides = []): CashSession
    {
        return app(CashSessionService::class)->open($user, array_merge([
            'opening_amount_xaf' => 0,
            'latitude' => 0.3380,
            'longitude' => 9.4710,
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validCollectionPayload(
        EconomicOperator $operator,
        CashSession $session,
        float $amount = 15000,
    ): array {
        return [
            'operator_id' => $operator->id,
            'amount_xaf' => $amount,
            'cash_session_id' => $session->id,
            'latitude' => 0.3380,
            'longitude' => 9.4710,
            'gps_accuracy_m' => 10,
            'device_id' => 'test-device',
        ];
    }

    protected function createManualObligation(
        EconomicOperator $operator,
        MunicipalTaxType $taxType,
        MunicipalTaxRate $rate,
        float $amountDue,
        string $dueDate,
        string $reference,
    ): FiscalObligation {
        return FiscalObligation::query()->create([
            'operator_id' => $operator->id,
            'tax_type_id' => $taxType->id,
            'tax_rate_id' => $rate->id,
            'obligation_type' => FiscalObligationType::Tax,
            'reference' => $reference,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'amount_due' => $amountDue,
            'amount_paid' => 0,
            'balance_due' => $amountDue,
            'status' => \App\Modules\Municipality\Enums\FiscalObligationStatus::Open,
            'generated_at' => now(),
            'due_date' => $dueDate,
        ]);
    }
}
