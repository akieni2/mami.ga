<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Models\MunicipalCollectionTarget;
use App\Modules\Municipality\Models\MunicipalTaxType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TargetService
{
    public function __construct(
        private readonly FiscalAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = MunicipalCollectionTarget::query()
            ->with(['taxType', 'createdBy'])
            ->orderByDesc('fiscal_year');

        if (! empty($filters['tax_type_id'])) {
            $query->where('tax_type_id', $filters['tax_type_id']);
        }

        if (! empty($filters['fiscal_year'])) {
            $query->where('fiscal_year', $filters['fiscal_year']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(User $actor, MunicipalTaxType $taxType, array $data): MunicipalCollectionTarget
    {
        return DB::transaction(function () use ($actor, $taxType, $data): MunicipalCollectionTarget {
            $fiscalYear = (int) $data['fiscal_year'];

            $existing = MunicipalCollectionTarget::query()
                ->where('tax_type_id', $taxType->id)
                ->where('fiscal_year', $fiscalYear)
                ->first();

            if ($existing !== null) {
                $before = ['target_amount_xaf' => (string) $existing->target_amount_xaf];
                $existing->update([
                    'target_amount_xaf' => $data['target_amount_xaf'],
                    'created_by' => $actor->id,
                ]);

                $this->audit->log($actor, $existing, 'municipal_collection_target', 'collection_target.updated', [
                    'before' => $before,
                    'after' => ['target_amount_xaf' => (string) $existing->target_amount_xaf],
                    'fiscal_year' => $fiscalYear,
                ]);

                return $existing->fresh(['taxType', 'createdBy']);
            }

            $target = MunicipalCollectionTarget::query()->create([
                'tax_type_id' => $taxType->id,
                'fiscal_year' => $fiscalYear,
                'target_amount_xaf' => $data['target_amount_xaf'],
                'created_by' => $actor->id,
            ]);

            $this->audit->log($actor, $target, 'municipal_collection_target', 'collection_target.created', [
                'tax_type_id' => $taxType->id,
                'fiscal_year' => $fiscalYear,
                'target_amount_xaf' => (string) $target->target_amount_xaf,
            ]);

            return $target->fresh(['taxType', 'createdBy']);
        });
    }

    public function find(int $id): MunicipalCollectionTarget
    {
        return MunicipalCollectionTarget::query()
            ->with(['taxType', 'createdBy'])
            ->findOrFail($id);
    }
}
