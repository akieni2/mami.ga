<?php

namespace Tests\Feature\Municipality;

use App\Modules\Municipality\Enums\ReceiptStatus;
use App\Modules\Municipality\Models\MunicipalReceipt;
use App\Modules\Municipality\Services\ReceiptCancellationService;
use App\Modules\Municipality\Services\ReceiptVerificationUrlBuilder;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class ReceiptVerificationTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEconomicRegistry();
    }

    public function test_public_verify_returns_valid_receipt(): void
    {
        $receipt = $this->createReceiptViaCollection();

        $this->getJson('/public/receipts/verify/'.$receipt->verification_token)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'valid')
            ->assertJsonPath('data.receipt_number', $receipt->receipt_number)
            ->assertJsonPath('data.document_hash', $receipt->document_hash);
    }

    public function test_public_verify_does_not_require_authentication(): void
    {
        $receipt = $this->createReceiptViaCollection();

        $this->getJson('/public/receipts/verify/'.$receipt->verification_token)
            ->assertOk();
    }

    public function test_public_verify_returns_not_found_for_unknown_token(): void
    {
        $this->getJson('/public/receipts/verify/00000000-0000-4000-8000-000000000000')
            ->assertNotFound()
            ->assertJsonPath('data.status', 'not_found');
    }

    public function test_public_verify_marks_annulled_receipt_as_invalid(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->createReceiptViaCollection($agent);

        app(ReceiptCancellationService::class)->annul($agent, $receipt, 'Erreur de saisie terrain');

        $this->getJson('/public/receipts/verify/'.$receipt->verification_token)
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.status', 'annulled')
            ->assertJsonPath('data.status_label', 'Annulée');
    }

    public function test_public_verify_marks_refunded_receipt_as_invalid(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->createReceiptViaCollection($agent);

        app(ReceiptCancellationService::class)->refund($agent, $receipt, 'Remboursement demandé');

        $this->getJson('/public/receipts/verify/'.$receipt->verification_token)
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.status', 'refunded');
    }

    public function test_public_verify_includes_operator_and_amount(): void
    {
        $receipt = $this->createReceiptViaCollection();
        $receipt->load('payment.operator');

        $this->getJson('/public/receipts/verify/'.$receipt->verification_token)
            ->assertOk()
            ->assertJsonPath('data.amount_xaf', (string) $receipt->payment->amount)
            ->assertJsonPath('data.operator.commercial_name', $receipt->payment->operator->commercial_name);
    }

    public function test_public_verify_html_page_renders(): void
    {
        $receipt = $this->createReceiptViaCollection();

        $this->get('/public/receipts/verify/'.$receipt->verification_token)
            ->assertOk()
            ->assertSee($receipt->receipt_number);
    }

    public function test_qr_url_matches_verification_endpoint(): void
    {
        $receipt = $this->createReceiptViaCollection();
        $url = app(ReceiptVerificationUrlBuilder::class)->build($receipt->verification_token);

        $this->assertSame($url, $receipt->receipt_qr_value);
        $this->getJson(str_replace(config('app.url'), '', $url))
            ->assertOk();
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

        return MunicipalReceipt::query()->with(['payment.operator'])->firstOrFail();
    }
}
