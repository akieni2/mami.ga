<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Enums\FiscalObligationStatus;
use App\Modules\Municipality\Http\Controllers\Concerns\AuthorizesFiscalAccess;
use App\Modules\Municipality\Http\Resources\FiscalObligationResource;
use App\Modules\Municipality\Models\FiscalObligation;
use App\Modules\Municipality\Services\FiscalObligationGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FiscalObligationController extends Controller
{
    use AuthorizesFiscalAccess;

    public function __construct(
        private readonly FiscalObligationGeneratorService $obligationService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeFiscalView($request->user());

        return FiscalObligationResource::collection(
            $this->obligationService->paginate($request->only(['operator_id', 'tax_type_id', 'status']))
        );
    }

    public function show(Request $request, FiscalObligation $obligation): FiscalObligationResource
    {
        $this->authorizeFiscalView($request->user());

        return new FiscalObligationResource($this->obligationService->find($obligation->id));
    }

    public function generate(Request $request): JsonResponse
    {
        $this->authorizeFiscalManage($request->user());

        $result = $this->obligationService->generate($request->user());

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function cancel(Request $request, FiscalObligation $obligation): FiscalObligationResource
    {
        $this->authorizeFiscalManage($request->user());

        if ($obligation->status === FiscalObligationStatus::Cancelled) {
            return new FiscalObligationResource($obligation);
        }

        return new FiscalObligationResource($this->obligationService->cancel($request->user(), $obligation));
    }
}
