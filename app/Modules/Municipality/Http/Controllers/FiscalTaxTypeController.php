<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Controllers\Concerns\AuthorizesFiscalAccess;
use App\Modules\Municipality\Http\Resources\MunicipalTaxTypeResource;
use App\Modules\Municipality\Models\MunicipalTaxType;
use App\Modules\Municipality\Services\TaxTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FiscalTaxTypeController extends Controller
{
    use AuthorizesFiscalAccess;

    public function __construct(
        private readonly TaxTypeService $taxTypeService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeFiscalView($request->user());

        $paginator = $this->taxTypeService->paginate($request->only(['is_active', 'search']));

        return MunicipalTaxTypeResource::collection($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeFiscalManage($request->user());

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-]+$/'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->taxTypeService->assertCodeAvailable($data['code']);
        $taxType = $this->taxTypeService->create($request->user(), $data);

        return (new MunicipalTaxTypeResource($taxType))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, MunicipalTaxType $taxType): MunicipalTaxTypeResource
    {
        $this->authorizeFiscalView($request->user());

        return new MunicipalTaxTypeResource($this->taxTypeService->find($taxType->id));
    }

    public function update(Request $request, MunicipalTaxType $taxType): MunicipalTaxTypeResource
    {
        $this->authorizeFiscalManage($request->user());

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $updated = $this->taxTypeService->update($request->user(), $taxType, $data);

        return new MunicipalTaxTypeResource($updated);
    }

    public function activate(Request $request, MunicipalTaxType $taxType): MunicipalTaxTypeResource
    {
        $this->authorizeFiscalManage($request->user());

        return new MunicipalTaxTypeResource($this->taxTypeService->activate($request->user(), $taxType));
    }

    public function deactivate(Request $request, MunicipalTaxType $taxType): MunicipalTaxTypeResource
    {
        $this->authorizeFiscalManage($request->user());

        return new MunicipalTaxTypeResource($this->taxTypeService->deactivate($request->user(), $taxType));
    }
}
