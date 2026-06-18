<?php

namespace Tests\Feature\Municipality;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Municipality\Enums\ReceiptDocumentFormat;
use App\Modules\Municipality\Enums\ReceiptStatus;
use App\Modules\Municipality\Models\MunicipalReceipt;
use App\Modules\Municipality\Models\MunicipalReceiptDocument;
use App\Modules\Municipality\Services\MunicipalReceiptEmissionService;
use App\Modules\Municipality\Services\ReceiptDocumentHasher;
use App\Modules\Municipality\Services\ReceiptVerificationUrlBuilder;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class MunicipalReceiptPdfTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEconomicRegistry();
        Storage::fake('local');
    }

    public function test_emission_generates_a4_and_thermal_documents(): void
    {
        $receipt = $this->emitReceiptForCollection();

        $this->assertDatabaseHas('municipal_receipt_documents', [
            'municipal_receipt_id' => $receipt->id,
            'format' => ReceiptDocumentFormat::A4Pdf->value,
            'version' => 1,
        ]);
        $this->assertDatabaseHas('municipal_receipt_documents', [
            'municipal_receipt_id' => $receipt->id,
            'format' => ReceiptDocumentFormat::Thermal58mm->value,
            'version' => 1,
        ]);
    }

    public function test_pdf_files_are_stored_on_disk(): void
    {
        $receipt = $this->emitReceiptForCollection();
        $receipt->load('documents');

        foreach ($receipt->documents as $document) {
            Storage::disk('local')->assertExists($document->storage_path);
            $this->assertStringStartsWith('%PDF', $document->contents());
        }
    }

    public function test_reprint_creates_new_document_versions(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->emitReceiptForCollection($agent);

        Sanctum::actingAs($agent);
        $this->postJson("/api/municipality/fiscal/receipts/{$receipt->id}/reprint")
            ->assertOk();

        $receipt->refresh();
        $this->assertSame(1, $receipt->reprint_count);

        $a4Versions = MunicipalReceiptDocument::query()
            ->where('municipal_receipt_id', $receipt->id)
            ->where('format', ReceiptDocumentFormat::A4Pdf)
            ->orderBy('version')
            ->pluck('version')
            ->all();

        $this->assertSame([1, 2], $a4Versions);
    }

    public function test_agent_can_download_latest_pdf(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->emitReceiptForCollection($agent);

        Sanctum::actingAs($agent);
        $response = $this->get("/api/municipality/fiscal/receipts/{$receipt->id}/pdf/a4_pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_all_generated_versions_are_retained(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->emitReceiptForCollection($agent);

        Sanctum::actingAs($agent);
        $this->postJson("/api/municipality/fiscal/receipts/{$receipt->id}/reprint")->assertOk();
        $this->postJson("/api/municipality/fiscal/receipts/{$receipt->id}/reprint")->assertOk();

        $count = MunicipalReceiptDocument::query()
            ->where('municipal_receipt_id', $receipt->id)
            ->count();

        $this->assertSame(6, $count);
    }

    public function test_receipt_number_matches_owe_rcp_format(): void
    {
        $receipt = $this->emitReceiptForCollection();
        $this->assertMatchesRegularExpression('/^OWE-RCP-\d{4}-\d{6}$/', $receipt->receipt_number);
    }

    public function test_collection_response_includes_receipt_with_documents(): void
    {
        $agent = $this->fiscalManager();
        $taxType = $this->createTaxType($agent);
        $this->createTaxRate($agent, $taxType);
        $operator = $this->createOperator($agent);
        $this->assignTax($agent, $operator, $taxType);
        $this->generateObligations($agent);
        $session = $this->openCashSession($agent);

        $this->actingAs($agent);
        $response = $this->postJson(
            '/api/municipality/fiscal/collections',
            $this->validCollectionPayload($operator, $session),
        )->assertCreated();

        $response->assertJsonStructure([
            'data' => [
                'receipt' => [
                    'receipt_number',
                    'verification_url',
                    'document_hash',
                    'documents',
                    'print_payload',
                ],
            ],
        ]);
    }

    private function emitReceiptForCollection(?\App\Models\User $agent = null): MunicipalReceipt
    {
        $agent ??= $this->fiscalManager();
        $taxType = $this->createTaxType($agent);
        $this->createTaxRate($agent, $taxType);
        $operator = $this->createOperator($agent);
        $this->assignTax($agent, $operator, $taxType);
        $this->generateObligations($agent);
        $session = $this->openCashSession($agent);

        app(\App\Modules\Municipality\Services\FiscalCollectionService::class)
            ->collectCash($agent, $this->validCollectionPayload($operator, $session));

        return MunicipalReceipt::query()->with('documents')->firstOrFail();
    }
}
