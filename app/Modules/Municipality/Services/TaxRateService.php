<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\BillingPeriod;
use App\Modules\Municipality\Models\MunicipalTaxRate;
use App\Modules\Municipality\Models\MunicipalTaxType;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaxRateService
{
    public function __construct(
        private readonly FiscalAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = MunicipalTaxRate::query()
            ->with(['taxType', 'createdBy'])
            ->orderByDesc('valid_from');

        if (! empty($filters['tax_type_id'])) {
            $query->where('tax_type_id', $filters['tax_type_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, MunicipalTaxType $taxType, array $data): MunicipalTaxRate
    {
        return DB::transaction(function () use ($actor, $taxType, $data): MunicipalTaxRate {
            $validFrom = Carbon::parse($data['valid_from'])->toDateString();
            $billingPeriod = BillingPeriod::from($data['billing_period']);

            $this->deactivateSupersededRates($taxType, $validFrom, $actor);
            $this->assertNoOverlap($taxType->id, $validFrom, $data['valid_to'] ?? null);

            $rate = MunicipalTaxRate::query()->create([
                'tax_type_id' => $taxType->id,
                'amount_xaf' => $data['amount_xaf'],
                'billing_period' => $billingPeriod,
                'valid_from' => $validFrom,
                'valid_to' => $data['valid_to'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $actor->id,
            ]);

            $this->audit->log($actor, $rate, 'municipal_tax_rate', 'tax_rate.created', [
                'tax_type_id' => $taxType->id,
                'tax_code' => $taxType->code,
                'amount_xaf' => (string) $rate->amount_xaf,
                'billing_period' => $rate->billing_period->value,
                'valid_from' => $validFrom,
            ]);

            return $rate->fresh(['taxType', 'createdBy']);
        });
    }

    public function deactivate(User $actor, MunicipalTaxRate $rate): MunicipalTaxRate
    {
        if (! $rate->is_active) {
            return $rate;
        }

        $rate->update(['is_active' => false]);

        $this->audit->log($actor, $rate, 'municipal_tax_rate', 'tax_rate.deactivated', [
            'tax_type_id' => $rate->tax_type_id,
            'rate_id' => $rate->id,
        ]);

        return $rate->fresh();
    }

    public function resolveActiveRate(MunicipalTaxType $taxType, ?\DateTimeInterface $on = null): ?MunicipalTaxRate
    {
        $date = ($on ?? now())->format('Y-m-d');

        return MunicipalTaxRate::query()
            ->where('tax_type_id', $taxType->id)
            ->where('is_active', true)
            ->where('valid_from', '<=', $date)
            ->where(function ($query) use ($date): void {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', $date);
            })
            ->orderByDesc('valid_from')
            ->first();
    }

    private function deactivateSupersededRates(MunicipalTaxType $taxType, string $validFrom, User $actor): void
    {
        $newStart = Carbon::parse($validFrom);
        $dayBefore = $newStart->copy()->subDay()->toDateString();

        $rates = MunicipalTaxRate::query()
            ->where('tax_type_id', $taxType->id)
            ->where('is_active', true)
            ->where('valid_from', '<', $validFrom)
            ->get();

        foreach ($rates as $rate) {
            $rate->update([
                'valid_to' => $rate->valid_to === null || $rate->valid_to->format('Y-m-d') >= $validFrom
                    ? $dayBefore
                    : $rate->valid_to,
                'is_active' => false,
            ]);

            $this->audit->log($actor, $rate, 'municipal_tax_rate', 'tax_rate.superseded', [
                'superseded_by_valid_from' => $validFrom,
            ]);
        }
    }

    private function assertNoOverlap(int $taxTypeId, string $validFrom, ?string $validTo): void
    {
        $overlap = MunicipalTaxRate::query()
            ->where('tax_type_id', $taxTypeId)
            ->where('is_active', true)
            ->where('valid_from', '<=', $validTo ?? '9999-12-31')
            ->where(function ($query) use ($validFrom): void {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', $validFrom);
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'valid_from' => ['Ce taux chevauche une période de validité existante.'],
            ]);
        }
    }
}
