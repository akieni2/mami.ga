<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Enums\BillingPeriod;
use App\Modules\Municipality\Http\Controllers\Concerns\AuthorizesFiscalAccess;
use App\Modules\Municipality\Http\Resources\MunicipalTaxRateResource;
use App\Modules\Municipality\Models\MunicipalTaxRate;
use App\Modules\Municipality\Models\MunicipalTaxType;
use App\Modules\Municipality\Services\TaxRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FiscalTaxRateController extends Controller
{
    use AuthorizesFiscalAccess;

    public function __construct(
        private readonly TaxRateService $taxRateService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeFiscalView($request->user());

        return MunicipalTaxRateResource::collection(
            $this->taxRateService->paginate($request->only(['tax_type_id', 'is_active']))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeFiscalManage($request->user());

        $data = $request->validate([
            'tax_type_id' => ['required', 'integer', 'exists:municipal_tax_types,id'],
            'amount_xaf' => ['required', 'numeric', 'min:0'],
            'billing_period' => ['required', 'in:'.implode(',', BillingPeriod::values())],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $taxType = MunicipalTaxType::query()->findOrFail($data['tax_type_id']);
        $rate = $this->taxRateService->create($request->user(), $taxType, $data);

        return (new MunicipalTaxRateResource($rate))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, MunicipalTaxRate $rate): MunicipalTaxRateResource
    {
        $this->authorizeFiscalView($request->user());

        return new MunicipalTaxRateResource($rate->load(['taxType', 'createdBy']));
    }

    public function deactivate(Request $request, MunicipalTaxRate $rate): MunicipalTaxRateResource
    {
        $this->authorizeFiscalManage($request->user());

        return new MunicipalTaxRateResource($this->taxRateService->deactivate($request->user(), $rate));
    }
}
