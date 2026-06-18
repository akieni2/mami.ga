<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Models\MunicipalTaxType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaxTypeService
{
    public function __construct(
        private readonly FiscalAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = MunicipalTaxType::query()
            ->with(['createdBy', 'updatedBy', 'activeRate'])
            ->orderBy('code');

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search): void {
                $q->where('code', 'like', $search)
                    ->orWhere('name', 'like', $search);
            });
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): MunicipalTaxType
    {
        return MunicipalTaxType::query()
            ->with(['rates' => fn ($q) => $q->orderByDesc('valid_from'), 'collectionTargets'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): MunicipalTaxType
    {
        return DB::transaction(function () use ($actor, $data): MunicipalTaxType {
            $taxType = MunicipalTaxType::query()->create([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->audit->log($actor, $taxType, 'municipal_tax_type', 'tax_type.created', [
                'code' => $taxType->code,
                'name' => $taxType->name,
            ]);

            return $taxType->fresh(['createdBy', 'activeRate']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, MunicipalTaxType $taxType, array $data): MunicipalTaxType
    {
        return DB::transaction(function () use ($actor, $taxType, $data): MunicipalTaxType {
            $before = $taxType->only(['name', 'description', 'is_active']);

            $taxType->fill([
                'name' => $data['name'] ?? $taxType->name,
                'description' => array_key_exists('description', $data) ? $data['description'] : $taxType->description,
                'updated_by' => $actor->id,
            ]);

            if (array_key_exists('is_active', $data)) {
                $taxType->is_active = (bool) $data['is_active'];
            }

            $taxType->save();

            $this->audit->log($actor, $taxType, 'municipal_tax_type', 'tax_type.updated', [
                'before' => $before,
                'after' => $taxType->only(['name', 'description', 'is_active']),
            ]);

            return $taxType->fresh(['createdBy', 'updatedBy', 'activeRate']);
        });
    }

    public function activate(User $actor, MunicipalTaxType $taxType): MunicipalTaxType
    {
        return $this->setActive($actor, $taxType, true);
    }

    public function deactivate(User $actor, MunicipalTaxType $taxType): MunicipalTaxType
    {
        return $this->setActive($actor, $taxType, false);
    }

    private function setActive(User $actor, MunicipalTaxType $taxType, bool $active): MunicipalTaxType
    {
        if ($taxType->is_active === $active) {
            return $taxType;
        }

        $taxType->update([
            'is_active' => $active,
            'updated_by' => $actor->id,
        ]);

        $this->audit->log($actor, $taxType, 'municipal_tax_type', $active ? 'tax_type.activated' : 'tax_type.deactivated', [
            'code' => $taxType->code,
        ]);

        return $taxType->fresh();
    }

    public function assertCodeAvailable(string $code, ?int $exceptId = null): void
    {
        $exists = MunicipalTaxType::query()
            ->where('code', strtoupper($code))
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => ['Ce code de taxe existe déjà.'],
            ]);
        }
    }
}
