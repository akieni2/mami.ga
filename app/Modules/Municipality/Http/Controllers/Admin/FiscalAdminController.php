<?php

namespace App\Modules\Municipality\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Enums\BillingPeriod;
use App\Modules\Municipality\Enums\FiscalObligationStatus;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\MunicipalTaxType;
use App\Modules\Municipality\Services\FiscalAssignmentService;
use App\Modules\Municipality\Services\FiscalObligationGeneratorService;
use App\Modules\Municipality\Services\TargetService;
use App\Modules\Municipality\Services\TaxRateService;
use App\Modules\Municipality\Services\TaxTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FiscalAdminController extends Controller
{
    public function __construct(
        private readonly TaxTypeService $taxTypeService,
        private readonly TaxRateService $taxRateService,
        private readonly TargetService $targetService,
        private readonly FiscalAssignmentService $assignmentService,
        private readonly FiscalObligationGeneratorService $obligationService,
    ) {}

    public function taxTypes(Request $request): View
    {
        $taxTypes = $this->taxTypeService->paginate($request->only(['is_active', 'search']), 30);

        return view('admin.municipality.fiscal.tax-types', [
            'taxTypes' => $taxTypes,
            'filters' => $request->only(['is_active', 'search']),
        ]);
    }

    public function storeTaxType(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-]+$/'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
        ]);

        $this->taxTypeService->assertCodeAvailable($data['code']);
        $this->taxTypeService->create($request->user(), $data);

        return back()->with('success', 'Type de taxe créé.');
    }

    public function updateTaxType(Request $request, MunicipalTaxType $taxType): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
        ]);

        $this->taxTypeService->update($request->user(), $taxType, $data);

        return back()->with('success', 'Type de taxe mis à jour.');
    }

    public function toggleTaxType(Request $request, MunicipalTaxType $taxType): RedirectResponse
    {
        if ($taxType->is_active) {
            $this->taxTypeService->deactivate($request->user(), $taxType);
        } else {
            $this->taxTypeService->activate($request->user(), $taxType);
        }

        return back()->with('success', 'Statut de la taxe mis à jour.');
    }

    public function rates(Request $request): View
    {
        $rates = $this->taxRateService->paginate($request->only(['tax_type_id', 'is_active']), 30);
        $taxTypes = MunicipalTaxType::query()->orderBy('code')->get();

        return view('admin.municipality.fiscal.rates', [
            'rates' => $rates,
            'taxTypes' => $taxTypes,
            'billingPeriods' => BillingPeriod::cases(),
            'filters' => $request->only(['tax_type_id', 'is_active']),
        ]);
    }

    public function storeRate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tax_type_id' => ['required', 'integer', 'exists:municipal_tax_types,id'],
            'amount_xaf' => ['required', 'numeric', 'min:0'],
            'billing_period' => ['required', 'in:'.implode(',', BillingPeriod::values())],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $taxType = MunicipalTaxType::query()->findOrFail($data['tax_type_id']);
        $this->taxRateService->create($request->user(), $taxType, $data);

        return back()->with('success', 'Taux créé.');
    }

    public function deactivateRate(Request $request, int $rate): RedirectResponse
    {
        $model = \App\Modules\Municipality\Models\MunicipalTaxRate::query()->findOrFail($rate);
        $this->taxRateService->deactivate($request->user(), $model);

        return back()->with('success', 'Taux désactivé.');
    }

    public function targets(Request $request): View
    {
        $targets = $this->targetService->paginate($request->only(['tax_type_id', 'fiscal_year']), 30);
        $taxTypes = MunicipalTaxType::query()->where('is_active', true)->orderBy('code')->get();

        return view('admin.municipality.fiscal.targets', [
            'targets' => $targets,
            'taxTypes' => $taxTypes,
            'filters' => $request->only(['tax_type_id', 'fiscal_year']),
        ]);
    }

    public function storeTarget(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tax_type_id' => ['required', 'integer', 'exists:municipal_tax_types,id'],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'target_amount_xaf' => ['required', 'numeric', 'min:0'],
        ]);

        $taxType = MunicipalTaxType::query()->findOrFail($data['tax_type_id']);
        $this->targetService->upsert($request->user(), $taxType, $data);

        return back()->with('success', 'Objectif enregistré.');
    }

    public function assignments(Request $request): View
    {
        $assignments = $this->assignmentService->paginate($request->only(['operator_id', 'tax_type_id', 'is_active']), 30);
        $taxTypes = MunicipalTaxType::query()->where('is_active', true)->orderBy('code')->get();
        $operators = EconomicOperator::query()->where('is_active', true)->orderBy('public_id')->limit(200)->get();

        return view('admin.municipality.fiscal.assignments', [
            'assignments' => $assignments,
            'taxTypes' => $taxTypes,
            'operators' => $operators,
            'filters' => $request->only(['operator_id', 'tax_type_id', 'is_active']),
        ]);
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'operator_id' => ['required', 'integer', 'exists:economic_operators,id'],
            'tax_type_id' => ['required', 'integer', 'exists:municipal_tax_types,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $operator = EconomicOperator::query()->findOrFail($data['operator_id']);
        $taxType = MunicipalTaxType::query()->findOrFail($data['tax_type_id']);
        $this->assignmentService->assign($request->user(), $operator, $taxType, $data);

        return back()->with('success', 'Taxe affectée à l\'opérateur.');
    }

    public function toggleAssignment(Request $request, int $assignment): RedirectResponse
    {
        $model = \App\Modules\Municipality\Models\OperatorTaxAssignment::query()->findOrFail($assignment);

        if ($model->is_active) {
            $this->assignmentService->deactivate($request->user(), $model);
        } else {
            $this->assignmentService->activate($request->user(), $model);
        }

        return back()->with('success', 'Affectation mise à jour.');
    }

    public function obligations(Request $request): View
    {
        $obligations = $this->obligationService->paginate($request->only(['operator_id', 'tax_type_id', 'status']), 30);
        $statuses = FiscalObligationStatus::cases();

        return view('admin.municipality.fiscal.obligations', [
            'obligations' => $obligations,
            'statuses' => $statuses,
            'filters' => $request->only(['operator_id', 'tax_type_id', 'status']),
        ]);
    }

    public function generateObligations(Request $request): RedirectResponse
    {
        $result = $this->obligationService->generate($request->user());

        $message = sprintf(
            '%d obligation(s) créée(s), %d ignorée(s).',
            $result['created'],
            $result['skipped'],
        );

        if ($result['created'] === 0) {
            $message .= ' Vérifiez qu\'il existe des affectations actives avec des taux en vigueur.';
        }

        return back()->with('success', $message);
    }
}
