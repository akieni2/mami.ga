<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Core\Models\Payment;
use App\Modules\Core\Models\Transaction;
use App\Modules\Municipality\Models\MunicipalPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentOrchestratorService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function createCorePayment(
        User $agent,
        MunicipalPayment $municipalPayment,
        array $metadata = [],
    ): Payment {
        $idempotencyKey = $municipalPayment->client_operation_id
            ?? (string) Str::uuid();

        $payment = Payment::query()->create([
            'payer_id' => $agent->id,
            'payee_id' => null,
            'payable_type' => 'municipal_payment',
            'payable_id' => $municipalPayment->id,
            'amount' => $municipalPayment->amount,
            'currency' => 'XAF',
            'method' => 'cash',
            'status' => 'captured',
            'idempotency_key' => $idempotencyKey,
            'metadata' => array_merge($metadata, [
                'operator_id' => $municipalPayment->operator_id,
                'cash_session_id' => $municipalPayment->cash_session_id,
                'module' => 'municipality',
            ]),
            'captured_at' => $municipalPayment->collected_at ?? now(),
        ]);

        Transaction::query()->create([
            'payment_id' => $payment->id,
            'type' => 'capture',
            'amount' => $municipalPayment->amount,
            'currency' => 'XAF',
            'status' => 'completed',
            'provider' => 'cash',
            'processed_at' => now(),
            'payload' => [
                'municipal_payment_id' => $municipalPayment->id,
            ],
        ]);

        return $payment;
    }
}
