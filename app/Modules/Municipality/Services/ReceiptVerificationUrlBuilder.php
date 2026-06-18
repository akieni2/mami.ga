<?php

namespace App\Modules\Municipality\Services;

class ReceiptVerificationUrlBuilder
{
    public function build(string $verificationToken): string
    {
        return rtrim((string) config('mami.urls.portal'), '/').'/public/receipts/verify/'.$verificationToken;
    }
}
