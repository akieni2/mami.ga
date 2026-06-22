<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Controllers\Concerns\AuthorizesFinanceAccess;
use App\Modules\Municipality\Http\Resources\TreasuryRemittanceResource;
use App\Modules\Municipality\Models\MunicipalTreasuryRemittance;
use App\Modules\Municipality\Services\TreasuryRemittanceReconciliationService;
use App\Modules\Municipality\Services\TreasuryRemittanceService;
use App\Modules\Municipality\Services\TreasuryRemittanceWorkflowService;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TreasuryRemittanceController extends Controller
{
    use AuthorizesFinanceAccess;

    public function __construct(
        private readonly TreasuryRemittanceService $remittanceService,
        private readonly TreasuryRemittanceWorkflowService $workflowService,
        private readonly TreasuryRemittanceReconciliationService $reconciliationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeRemittanceView($request->user());

        $status = $request->query('status');
        $items = collect($this->remittanceService->listRecent(is_string($status) ? $status : null))
            ->map(fn ($item) => (new TreasuryRemittanceResource($item))->resolve())
            ->values()
            ->all();

        return ApiResponse::success($items, 'Reversements Trésor Public');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeRemittanceManage($request->user());

        $data = $request->validate([
            'amount_xaf' => ['required_without:payment_ids', 'nullable', 'numeric', 'min:0.01'],
            'period_start' => ['required_with:payment_ids', 'nullable', 'date'],
            'period_end' => ['required_with:payment_ids', 'nullable', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_ids' => ['nullable', 'array'],
            'payment_ids.*' => ['integer', 'exists:municipal_payments,id'],
        ]);

        $remittance = $this->remittanceService->createDraft($request->user(), $data);

        return ApiResponse::success(
            new TreasuryRemittanceResource($remittance),
            'Brouillon de reversement créé',
            201,
        );
    }

    public function show(Request $request, MunicipalTreasuryRemittance $remittance): JsonResponse
    {
        $this->authorizeRemittanceView($request->user());

        return ApiResponse::success(
            new TreasuryRemittanceResource($this->remittanceService->find($remittance->id)),
            'Détail reversement',
        );
    }

    public function update(Request $request, MunicipalTreasuryRemittance $remittance): JsonResponse
    {
        $this->authorizeRemittanceManage($request->user());

        $data = $request->validate([
            'amount_xaf' => ['nullable', 'numeric', 'min:0.01'],
            'period_start' => ['required_with:payment_ids', 'nullable', 'date'],
            'period_end' => ['required_with:payment_ids', 'nullable', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_ids' => ['nullable', 'array'],
            'payment_ids.*' => ['integer', 'exists:municipal_payments,id'],
        ]);

        $updated = $this->remittanceService->updateDraft($request->user(), $remittance, $data);

        return ApiResponse::success(new TreasuryRemittanceResource($updated), 'Reversement mis à jour');
    }

    public function generateFromPeriod(Request $request): JsonResponse
    {
        $this->authorizeRemittanceManage($request->user());

        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $remittance = $this->remittanceService->generateFromPeriod($request->user(), $data);

        return ApiResponse::success(
            new TreasuryRemittanceResource($remittance),
            'Brouillon généré depuis les encaissements',
            201,
        );
    }

    public function reconciliationPreview(Request $request): JsonResponse
    {
        $this->authorizeRemittanceView($request->user());

        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'except_remittance_id' => ['nullable', 'integer', 'exists:municipal_treasury_remittances,id'],
        ]);

        $summary = $this->reconciliationService->buildReconciliationSummary(
            Carbon::parse($data['period_start']),
            Carbon::parse($data['period_end']),
            isset($data['except_remittance_id']) ? (int) $data['except_remittance_id'] : null,
        );

        return ApiResponse::success($summary, 'Prévisualisation réconciliation');
    }

    public function pending(Request $request): JsonResponse
    {
        $this->authorizeRemittanceView($request->user());

        $items = collect($this->workflowService->pendingForUser($request->user()))
            ->map(fn ($item) => (new TreasuryRemittanceResource($item))->resolve())
            ->values()
            ->all();

        return ApiResponse::success($items, 'Reversements en attente');
    }

    public function submitControl(Request $request, MunicipalTreasuryRemittance $remittance): JsonResponse
    {
        $this->authorizeRemittanceControl($request->user());

        $data = $request->validate(['comments' => ['nullable', 'string', 'max:2000']]);
        $updated = $this->workflowService->control($request->user(), $remittance, $data['comments'] ?? null);

        return ApiResponse::success(new TreasuryRemittanceResource($updated), 'Reversement contrôlé');
    }

    public function validateDaf(Request $request, MunicipalTreasuryRemittance $remittance): JsonResponse
    {
        $this->authorizeRemittanceDafValidate($request->user());

        $data = $request->validate(['comments' => ['nullable', 'string', 'max:2000']]);
        $updated = $this->workflowService->validateDaf($request->user(), $remittance, $data['comments'] ?? null);

        return ApiResponse::success(new TreasuryRemittanceResource($updated), 'Reversement validé DAF');
    }

    public function validateReceveur(Request $request, MunicipalTreasuryRemittance $remittance): JsonResponse
    {
        $this->authorizeRemittanceReceveurValidate($request->user());

        $data = $request->validate(['comments' => ['nullable', 'string', 'max:2000']]);
        $updated = $this->workflowService->validateReceveur($request->user(), $remittance, $data['comments'] ?? null);

        return ApiResponse::success(new TreasuryRemittanceResource($updated), 'Reversement validé receveur');
    }

    public function recordDeposit(Request $request, MunicipalTreasuryRemittance $remittance): JsonResponse
    {
        $this->authorizeRemittanceDeposit($request->user());

        $data = $request->validate([
            'slip_number' => ['required', 'string', 'max:40'],
            'bank_name' => ['required', 'string', 'max:120'],
            'deposit_reference' => ['required', 'string', 'max:80'],
            'deposited_at' => ['required', 'date'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $updated = $this->workflowService->recordDeposit($request->user(), $remittance, [
            'slip_number' => $data['slip_number'],
            'bank_name' => $data['bank_name'],
            'deposit_reference' => $data['deposit_reference'],
            'deposited_at' => Carbon::parse($data['deposited_at']),
        ], $data['comments'] ?? null);

        return ApiResponse::success(new TreasuryRemittanceResource($updated), 'Dépôt enregistré');
    }

    public function confirm(Request $request, MunicipalTreasuryRemittance $remittance): JsonResponse
    {
        $this->authorizeRemittanceConfirm($request->user());

        $data = $request->validate([
            'treasury_receipt_ref' => ['required', 'string', 'max:80'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $updated = $this->workflowService->confirm(
            $request->user(),
            $remittance,
            $data['treasury_receipt_ref'],
            $data['comments'] ?? null,
        );

        return ApiResponse::success(new TreasuryRemittanceResource($updated), 'Reversement confirmé Trésor');
    }

    public function reject(Request $request, MunicipalTreasuryRemittance $remittance): JsonResponse
    {
        $this->authorizeRemittanceReject($request->user(), $remittance);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $updated = $this->workflowService->reject(
            $request->user(),
            $remittance,
            $data['reason'],
            $data['comments'] ?? null,
        );

        return ApiResponse::success(new TreasuryRemittanceResource($updated), 'Reversement rejeté');
    }

    public function history(Request $request, MunicipalTreasuryRemittance $remittance): JsonResponse
    {
        $this->authorizeRemittanceView($request->user());

        $history = $this->remittanceService->find($remittance->id)
            ->approvals
            ->map(fn ($approval) => [
                'id' => $approval->id,
                'action' => $approval->action->value,
                'comments' => $approval->comments,
                'created_at' => $approval->created_at?->toIso8601String(),
                'performer' => [
                    'id' => $approval->performer?->id,
                    'name' => $approval->performer?->name,
                ],
            ])
            ->values()
            ->all();

        return ApiResponse::success($history, 'Historique reversement');
    }
}
