<?php

namespace Tests\Feature\Municipality;

use App\Modules\Municipality\Enums\ReceiptStatus;
use App\Modules\Municipality\Models\MunicipalReceipt;
use App\Modules\Municipality\Services\ReceiptDocumentHasher;
use App\Modules\Municipality\Services\ReceiptVerificationUrlBuilder;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class ReceiptSignatureTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEconomicRegistry();
    }

    public function test_document_hash_is_sha256_hex(): void
    {
        $receipt = $this->createReceiptViaCollection();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $receipt->document_hash);
    }

    public function test_document_hash_is_deterministic_for_same_payload(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->createReceiptViaCollection($agent);
        $payment = $receipt->payment;

        $hash = app(ReceiptDocumentHasher::class)->hash($receipt, $payment);
        $this->assertSame($receipt->document_hash, $hash);
    }

    public function test_document_hash_changes_when_amount_changes(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->createReceiptViaCollection($agent);
        $payment = $receipt->payment->replicate();
        $payment->amount = (float) $payment->amount + 1;

        $original = app(ReceiptDocumentHasher::class)->hash($receipt, $receipt->payment);
        $altered = app(ReceiptDocumentHasher::class)->hash($receipt, $payment);

        $this->assertNotSame($original, $altered);
    }

    public function test_signed_at_is_set_on_emission(): void
    {
        $receipt = $this->createReceiptViaCollection();
        $this->assertNotNull($receipt->signed_at);
    }

    public function test_verification_token_is_uuid(): void
    {
        $receipt = $this->createReceiptViaCollection();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $receipt->verification_token,
        );
    }

    public function test_qr_value_is_public_verification_url(): void
    {
        $receipt = $this->createReceiptViaCollection();
        $expected = app(ReceiptVerificationUrlBuilder::class)->build($receipt->verification_token);
        $this->assertSame($expected, $receipt->receipt_qr_value);
        $this->assertStringContainsString('/public/receipts/verify/', $receipt->receipt_qr_value);
    }

    public function test_hash_appears_in_api_receipt_resource(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->createReceiptViaCollection($agent);

        Sanctum::actingAs($agent);
        $this->getJson("/api/municipality/fiscal/receipts/{$receipt->id}")
            ->assertOk()
            ->assertJsonPath('data.document_hash', $receipt->document_hash);
    }

    public function test_hash_appears_in_print_payload(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->createReceiptViaCollection($agent);

        Sanctum::actingAs($agent);
        $response = $this->getJson("/api/municipality/fiscal/receipts/{$receipt->id}")->assertOk();

        $short = substr($receipt->document_hash, 0, 16);
        $this->assertSame($short, $response->json('data.print_payload.document_hash_short'));
    }

    public function test_new_receipt_defaults_to_valid_status(): void
    {
        $receipt = $this->createReceiptViaCollection();
        $this->assertSame(ReceiptStatus::Valid, $receipt->status);
    }

    private function createReceiptViaCollection(?\App\Models\User $agent = null): MunicipalReceipt
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

        return MunicipalReceipt::query()->with('payment')->firstOrFail();
    }
}
