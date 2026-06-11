<?php

namespace App\Support\Dispatch;

use Illuminate\Support\Facades\Log;

/**
 * Logs structurés dispatch P3 — filtrer avec grep [DISPATCH], [WAVE], etc.
 */
class DispatchLogger
{
    public static function dispatch(string $message): void
    {
        Log::info("[DISPATCH] {$message}");
    }

    public static function wave(string $message): void
    {
        Log::info("[WAVE] {$message}");
    }

    public static function offer(string $message): void
    {
        Log::info("[OFFER] {$message}");
    }

    public static function accept(string $message): void
    {
        Log::info("[ACCEPT] {$message}");
    }

    public static function expire(string $message): void
    {
        Log::info("[EXPIRE] {$message}");
    }

    public static function scoring(string $message): void
    {
        Log::info("[SCORING] {$message}");
    }

    public static function driverFilter(string $message): void
    {
        Log::info("[DRIVER_FILTER] {$message}");
    }
}
