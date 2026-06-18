<?php

namespace App\Modules\Municipality\Services;

class ReceiptVerificationUrlBuilder
{
    public function build(string $verificationToken): string
    {
        return rtrim((string) config('app.url'), '/').'/public/receipts/verify/'.$verificationToken;
    }
}
