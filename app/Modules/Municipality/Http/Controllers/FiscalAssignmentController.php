<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Controllers\Concerns\AuthorizesFiscalAccess;
use App\Modules\Municipality\Http\Resources\OperatorTaxAssignmentResource;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\MunicipalTaxType;
use App\Modules\Municipality\Models\OperatorTaxAssignment;
use App\Modules\Municipality\Services\FiscalAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FiscalAssignmentController extends Controller
{
    use AuthorizesFiscalAccess;

    public function __construct(
        private readonly FiscalAssignmentService $assignmentService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeFiscalView($request->user());

        return OperatorTaxAssignmentResource::collection(
            $this->assignmentService->paginate($request->only(['operator_id', 'tax_type_id', 'is_active']))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeFiscalAssign($request->user());

        $data = $request->validate([
            'operator_id' => ['required', 'integer', 'exists:economic_operators,id'],
            'tax_type_id' => ['required', 'integer', 'exists:municipal_tax_types,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $operator = EconomicOperator::query()->findOrFail($data['operator_id']);
        $taxType = MunicipalTaxType::query()->findOrFail($data['tax_type_id']);

        $assignment = $this->assignmentService->assign($request->user(), $operator, $taxType, $data);

        return (new OperatorTaxAssignmentResource($assignment['assignment']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, OperatorTaxAssignment $assignment): OperatorTaxAssignmentResource
    {
        $this->authorizeFiscalView($request->user());

        return new OperatorTaxAssignmentResource($assignment->load(['operator', 'taxType', 'assignedBy']));
    }

    public function activate(Request $request, OperatorTaxAssignment $assignment): OperatorTaxAssignmentResource
    {
        $this->authorizeFiscalAssign($request->user());

        return new OperatorTaxAssignmentResource($this->assignmentService->activate($request->user(), $assignment));
    }

    public function deactivate(Request $request, OperatorTaxAssignment $assignment): OperatorTaxAssignmentResource
    {
        $this->authorizeFiscalAssign($request->user());

        return new OperatorTaxAssignmentResource($this->assignmentService->deactivate($request->user(), $assignment));
    }
}
