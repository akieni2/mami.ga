<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Services\ReceiptVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PublicReceiptVerificationController extends Controller
{
    public function __construct(
        private readonly ReceiptVerificationService $verificationService,
    ) {}

    public function show(string $token): JsonResponse|View
    {
        $result = $this->verificationService->verify($token);

        if (request()->expectsJson() || request()->wantsJson()) {
            return response()->json([
                'success' => $result['valid'] ?? false,
                'data' => $result,
            ], ($result['status'] ?? '') === 'not_found' ? 404 : 200);
        }

        return view('public.receipts.verify', [
            'result' => $result,
        ]);
    }
}
