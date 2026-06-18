<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Enums\ReceiptDocumentFormat;
use App\Modules\Municipality\Http\Resources\MunicipalReceiptResource;
use App\Modules\Municipality\Models\MunicipalReceipt;
use App\Modules\Municipality\Services\MunicipalReceiptPdfService;
use App\Modules\Municipality\Services\ReceiptCancellationService;
use App\Modules\Municipality\Services\ReceiptReprintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MunicipalReceiptController extends Controller
{
    public function __construct(
        private readonly MunicipalReceiptPdfService $pdfService,
        private readonly ReceiptReprintService $reprintService,
        private readonly ReceiptCancellationService $cancellationService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeAgent($request);

        $query = MunicipalReceipt::query()
            ->with([
                'payment.operator',
                'payment.agent',
                'documents',
            ])
            ->orderByDesc('generated_at');

        if (! $request->user()->isAdmin()) {
            $query->whereHas('payment', fn ($q) => $q->where('agent_id', $request->user()->id));
        }

        return MunicipalReceiptResource::collection($query->paginate(30));
    }

    public function show(Request $request, MunicipalReceipt $receipt): MunicipalReceiptResource
    {
        $this->authorizeAgent($request);
        $this->assertCanView($request, $receipt);

        return new MunicipalReceiptResource(
            $receipt->load([
                'payment.operator.sector',
                'payment.agent',
                'payment.allocations.fiscalObligation.taxType',
                'documents',
            ])
        );
    }

    public function downloadPdf(Request $request, MunicipalReceipt $receipt, string $format = 'a4_pdf'): Response|JsonResponse
    {
        $this->authorizeAgent($request);
        $this->assertCanView($request, $receipt);

        $docFormat = ReceiptDocumentFormat::tryFrom($format) ?? ReceiptDocumentFormat::A4Pdf;
        $document = $this->pdfService->latestDocument($receipt, $docFormat);

        if ($document === null) {
            $document = $this->pdfService->generate($request->user(), $receipt, $docFormat);
        }

        return response($document->contents(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$receipt->receipt_number.'-'.$docFormat->value.'.pdf"',
        ]);
    }

    public function reprint(Request $request, MunicipalReceipt $receipt): MunicipalReceiptResource
    {
        $this->authorizeAgent($request);
        $this->assertCanView($request, $receipt);

        return new MunicipalReceiptResource(
            $this->reprintService->reprint($request->user(), $receipt)
        );
    }

    public function annul(Request $request, MunicipalReceipt $receipt): MunicipalReceiptResource
    {
        $this->authorizeSupervisor($request);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5'],
        ]);

        return new MunicipalReceiptResource(
            $this->cancellationService->annul($request->user(), $receipt, $data['reason'])
        );
    }

    private function authorizeAgent(Request $request): void
    {
        $user = $request->user();
        if (! $user->isAdmin()
            && ! $user->hasPermission('municipal.payment.collect')
            && ! $user->hasPermission('municipality.collections.manage')) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Accès quittances non autorisé.');
        }
    }

    private function authorizeSupervisor(Request $request): void
    {
        if (! $request->user()->isAdmin()
            && ! $request->user()->hasPermission('municipal.receipt.annul')) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Annulation non autorisée.');
        }
    }

    private function assertCanView(Request $request, MunicipalReceipt $receipt): void
    {
        if ($request->user()->isAdmin()) {
            return;
        }

        if ($receipt->payment?->agent_id !== $request->user()->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Quittance non autorisée.');
        }
    }
}
