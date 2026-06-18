<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Controllers\Concerns\AuthorizesFiscalAccess;
use App\Modules\Municipality\Http\Resources\MunicipalCollectionTargetResource;
use App\Modules\Municipality\Models\MunicipalCollectionTarget;
use App\Modules\Municipality\Models\MunicipalTaxType;
use App\Modules\Municipality\Services\TargetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FiscalTargetController extends Controller
{
    use AuthorizesFiscalAccess;

    public function __construct(
        private readonly TargetService $targetService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeFiscalView($request->user());

        return MunicipalCollectionTargetResource::collection(
            $this->targetService->paginate($request->only(['tax_type_id', 'fiscal_year']))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeFiscalManage($request->user());

        $data = $request->validate([
            'tax_type_id' => ['required', 'integer', 'exists:municipal_tax_types,id'],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'target_amount_xaf' => ['required', 'numeric', 'min:0'],
        ]);

        $taxType = MunicipalTaxType::query()->findOrFail($data['tax_type_id']);
        $target = $this->targetService->upsert($request->user(), $taxType, $data);

        return (new MunicipalCollectionTargetResource($target))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, MunicipalCollectionTarget $target): MunicipalCollectionTargetResource
    {
        $this->authorizeFiscalView($request->user());

        return new MunicipalCollectionTargetResource($this->targetService->find($target->id));
    }
}
