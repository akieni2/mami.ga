<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Enums\FiscalObligationStatus;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\FiscalObligation;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\OperatorTaxAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EconomicOperatorRepository
{
    public const ADMIN_PER_PAGE = 100;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(?int $registeredBy = null, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->baseQuery($filters, $registeredBy)
            ->with(['category', 'sector', 'operationalZone', 'economicZone', 'arrondissement', 'registeredBy', 'attachments'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adminPaginate(array $filters = [], int $perPage = self::ADMIN_PER_PAGE): LengthAwarePaginator
    {
        return $this->adminListQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adminExportQuery(array $filters = []): Builder
    {
        return $this->adminListQuery($filters);
    }

    public function findForAdminShow(int $id): EconomicOperator
    {
        return EconomicOperator::query()
            ->with([
                'category',
                'sector',
                'operationalZone',
                'economicZone',
                'arrondissement',
                'registeredBy',
                'attachments',
                'activeQrcode',
                'taxAssignments.taxType.activeRate',
                'municipalPayments' => fn ($q) => $q->latest('collected_at')->limit(20),
            ])
            ->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function fiscalSnapshot(EconomicOperator $operator): array
    {
        $obligations = FiscalObligation::query()
            ->with(['taxType'])
            ->where('operator_id', $operator->id)
            ->whereIn('status', [FiscalObligationStatus::Open, FiscalObligationStatus::Partial])
            ->orderBy('due_date')
            ->get();

        $amountDue = round((float) $obligations->sum('balance_due'), 2);
        $amountPaidTotal = round((float) FiscalObligation::query()
            ->where('operator_id', $operator->id)
            ->sum('amount_paid'), 2);

        $paymentsTotal = round((float) MunicipalPayment::query()
            ->where('operator_id', $operator->id)
            ->sum('amount'), 2);

        return [
            'assignments' => OperatorTaxAssignment::query()
                ->with(['taxType.activeRate'])
                ->where('operator_id', $operator->id)
                ->where('is_active', true)
                ->get(),
            'obligations' => $obligations,
            'payments_total' => $paymentsTotal,
            'amount_due' => $amountDue,
            'amount_paid' => $amountPaidTotal,
            'balance_remaining' => $amountDue,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function mapQuery(array $filters = []): Builder
    {
        return $this->baseQuery($filters)
            ->where('is_active', true)
            ->with(['category']);
    }

    /**
     * @param  list<string>  $publicIds
     * @return Collection<int, EconomicOperator>
     */
    public function findByPublicIds(array $publicIds): Collection
    {
        if ($publicIds === []) {
            return new Collection;
        }

        return EconomicOperator::query()
            ->with(['activeQrcode', 'category'])
            ->whereIn('public_id', $publicIds)
            ->get()
            ->keyBy('public_id');
    }

    public static function formatPublicId(int $sequence): string
    {
        return sprintf('OWE-COM-%08d', $sequence);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function adminListQuery(array $filters = []): Builder
    {
        return $this->baseQuery($filters)
            ->with(['category', 'sector'])
            ->select([
                'id',
                'public_id',
                'commercial_name',
                'responsible_name',
                'phone',
                'category_id',
                'sector_id',
                'is_active',
                'current_tax_status',
                'created_at',
            ])
            ->orderByDesc('created_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters = [], ?int $registeredBy = null): Builder
    {
        $query = EconomicOperator::query();

        if ($registeredBy !== null) {
            $query->where('registered_by', $registeredBy);
        }

        if (! empty($filters['tax_status'])) {
            $query->where('current_tax_status', $filters['tax_status']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['sector_id'])) {
            $query->where('sector_id', (int) $filters['sector_id']);
        }

        if (! empty($filters['economic_zone_id'])) {
            $query->where('economic_zone_id', (int) $filters['economic_zone_id']);
        }

        if (! empty($filters['public_id'])) {
            $query->where('public_id', 'like', '%'.trim((string) $filters['public_id']).'%');
        }

        if (! empty($filters['commercial_name'])) {
            $query->where('commercial_name', 'like', '%'.trim((string) $filters['commercial_name']).'%');
        }

        if (! empty($filters['responsible_name'])) {
            $query->where('responsible_name', 'like', '%'.trim((string) $filters['responsible_name']).'%');
        }

        if (! empty($filters['phone'])) {
            $query->where('phone', 'like', '%'.trim((string) $filters['phone']).'%');
        }

        if (! empty($filters['q'])) {
            $term = '%'.trim((string) $filters['q']).'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('commercial_name', 'like', $term)
                    ->orWhere('public_id', 'like', $term)
                    ->orWhere('responsible_name', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['bbox'])) {
            $parts = array_map('floatval', explode(',', (string) $filters['bbox']));
            if (count($parts) === 4) {
                [$swLng, $swLat, $neLng, $neLat] = $parts;
                $query->whereBetween('latitude', [$swLat, $neLat])
                    ->whereBetween('longitude', [$swLng, $neLng]);
            }
        }

        return $query;
    }
}
