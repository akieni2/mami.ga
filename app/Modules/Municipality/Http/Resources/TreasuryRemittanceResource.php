<?php

namespace App\Modules\Municipality\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Municipality\Models\MunicipalTreasuryRemittance */
class TreasuryRemittanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'amount_xaf' => (string) $this->amount_xaf,
            'reconciled_amount_xaf' => $this->reconciled_amount_xaf !== null ? (string) $this->reconciled_amount_xaf : null,
            'payment_count' => $this->payment_count,
            'cash_session_count' => $this->cash_session_count,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'slip_number' => $this->slip_number,
            'bank_name' => $this->bank_name,
            'deposit_reference' => $this->deposit_reference,
            'deposited_at' => $this->deposited_at?->toIso8601String(),
            'treasury_receipt_ref' => $this->treasury_receipt_ref,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'remitted_at' => $this->remitted_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'notes' => $this->notes,
            'accounting_batch_id' => $this->accounting_batch_id,
            'accounting_export_status' => $this->accounting_export_status?->value,
            'accounting_posted_at' => $this->accounting_posted_at?->toIso8601String(),
            'preparer' => $this->whenLoaded('preparer', fn () => [
                'id' => $this->preparer?->id,
                'name' => $this->preparer?->name,
            ]),
            'controller' => $this->whenLoaded('controller', fn () => $this->controller ? [
                'id' => $this->controller->id,
                'name' => $this->controller->name,
            ] : null),
            'daf_validator' => $this->whenLoaded('dafValidator', fn () => $this->dafValidator ? [
                'id' => $this->dafValidator->id,
                'name' => $this->dafValidator->name,
            ] : null),
            'receveur_validator' => $this->whenLoaded('receveurValidator', fn () => $this->receveurValidator ? [
                'id' => $this->receveurValidator->id,
                'name' => $this->receveurValidator->name,
            ] : null),
            'payments' => $this->whenLoaded('paymentAllocations', fn () => $this->paymentAllocations->map(fn ($allocation) => [
                'municipal_payment_id' => $allocation->municipal_payment_id,
                'amount_allocated' => (string) $allocation->amount_allocated,
                'cash_session_id' => $allocation->cash_session_id,
                'cash_session_reference' => $allocation->cashSession?->reference,
                'collected_at' => $allocation->payment?->collected_at?->toIso8601String(),
            ])->values()->all()),
            'controlled_at' => $this->controlled_at?->toIso8601String(),
            'daf_validated_at' => $this->daf_validated_at?->toIso8601String(),
            'receveur_validated_at' => $this->receveur_validated_at?->toIso8601String(),
        ];
    }
}
