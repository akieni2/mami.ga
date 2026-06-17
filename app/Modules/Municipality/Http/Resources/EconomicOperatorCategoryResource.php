<?php

namespace App\Modules\Municipality\Http\Resources;

use App\Modules\Municipality\Models\EconomicOperatorCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EconomicOperatorCategory */
class EconomicOperatorCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'icon' => $this->icon,
        ];
    }
}
