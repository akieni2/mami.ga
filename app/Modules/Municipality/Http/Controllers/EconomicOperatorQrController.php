<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Requests\StoreFieldVisitRequest;
use App\Modules\Municipality\Http\Resources\OperatorQrScanResource;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Services\FieldVisitService;
use App\Modules\Municipality\Services\MunicipalBusinessCardService;
use App\Modules\Municipality\Services\QRCodeManagement;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class EconomicOperatorQrController extends Controller
{
    public function __construct(
        private readonly QRCodeManagement $qrCodeManagement,
        private readonly MunicipalBusinessCardService $businessCardService,
        private readonly FieldVisitService $fieldVisitService,
    ) {}

    public function showByQr(string $value): JsonResponse
    {
        $this->authorize('viewAny', EconomicOperator::class);

        $qrcode = $this->qrCodeManagement->findByValue($value);
        if ($qrcode === null) {
            return ApiResponse::error('QR commerce introuvable ou inactif.', 404);
        }

        return ApiResponse::success(
            new OperatorQrScanResource($qrcode),
            'Commerce identifié',
        );
    }

    public function downloadPng(EconomicOperator $operator): Response
    {
        $this->authorize('view', $operator);

        $qrcode = $operator->activeQrcode;
        if ($qrcode === null) {
            $qrcode = $this->qrCodeManagement->generateForOperator($operator);
        }

        $content = $this->qrCodeManagement->buildPngContent($qrcode);
        $isSvg = str_starts_with(trim($content), '<svg');

        return response($content, HttpResponse::HTTP_OK, [
            'Content-Type' => $isSvg ? 'image/svg+xml' : 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$qrcode->qr_value.'-qr.'.($isSvg ? 'svg' : 'png').'"',
        ]);
    }

    public function downloadPdf(EconomicOperator $operator): JsonResponse
    {
        $this->authorize('view', $operator);

        $qrcode = $operator->activeQrcode;
        if ($qrcode === null) {
            $qrcode = $this->qrCodeManagement->generateForOperator($operator);
        }

        return ApiResponse::success(
            $this->qrCodeManagement->buildPdfPlaceholder($qrcode),
            'Génération PDF — fondation V2.5',
            501,
        );
    }

    public function businessCard(EconomicOperator $operator): JsonResponse
    {
        $this->authorize('view', $operator);

        return ApiResponse::success(
            $this->businessCardService->preview($operator),
            'Carte professionnelle municipale — aperçu',
        );
    }

    public function storeFieldVisit(StoreFieldVisitRequest $request, EconomicOperator $operator): JsonResponse
    {
        $visit = $this->fieldVisitService->record(
            $request->user(),
            $operator,
            $request->validated(),
        );

        return ApiResponse::success([
            'id' => $visit->id,
            'visit_type' => $visit->visit_type->value,
            'visit_date' => $visit->visit_date?->toDateString(),
            'operator_id' => $visit->operator_id,
        ], 'Visite terrain enregistrée', 201);
    }
}
