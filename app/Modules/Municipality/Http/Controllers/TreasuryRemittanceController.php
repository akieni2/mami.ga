<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Controllers\Concerns\AuthorizesFinanceAccess;
use App\Modules\Municipality\Http\Resources\TreasuryRemittanceResource;
use App\Modules\Municipality\Services\TreasuryRemittanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TreasuryRemittanceController extends Controller
{
    use AuthorizesFinanceAccess;

    public function __construct(
        private readonly TreasuryRemittanceService $remittanceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeRemittanceView($request->user());

        $items = collect($this->remittanceService->listRecent(50))
            ->map(fn ($item) => (new TreasuryRemittanceResource($item))->resolve())
            ->values()
            ->all();

        return ApiResponse::success($items, 'Reversements Trésor Public (préparation)');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeRemittanceManage($request->user());

        $data = $request->validate([
            'amount_xaf' => ['required', 'numeric', 'min:0.01'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $remittance = $this->remittanceService->createDraft($request->user(), $data);

        return ApiResponse::success(
            new TreasuryRemittanceResource($remittance),
            'Brouillon de reversement créé',
            201,
        );
    }
}
