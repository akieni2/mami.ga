<?php

namespace App\Support;

use App\Modules\Core\Enums\MamiModule;

/**
 * Feature flags MAMI Super App + Taxi V2.
 */
class MamiFeatures
{
    public static function taxiV2Enabled(): bool
    {
        return (bool) config('mami.taxi_v2_enabled', false);
    }

    public static function dispatchV2Enabled(): bool
    {
        return (bool) config('mami.dispatch_v2_enabled', false);
    }

    public static function moduleEnabled(string $module): bool
    {
        if ($module === MamiModule::Taxi->value) {
            return true;
        }

        return (bool) config('mami.modules.'.$module, false);
    }

    /**
     * @return array<string, bool>
     */
    public static function modulesConfig(): array
    {
        return [
            MamiModule::Taxi->value => true,
            MamiModule::Carpool->value => self::moduleEnabled(MamiModule::Carpool->value),
            MamiModule::Transport->value => self::moduleEnabled(MamiModule::Transport->value),
            MamiModule::Commerce->value => self::moduleEnabled(MamiModule::Commerce->value),
            MamiModule::Municipality->value => self::moduleEnabled(MamiModule::Municipality->value),
        ];
    }

    /**
     * @return array<string, bool|int|float|array<string, mixed>>
     */
    public static function publicConfig(): array
    {
        return [
            'super_app_enabled' => (bool) config('mami.super_app_enabled', true),
            'taxi_v2_enabled' => self::taxiV2Enabled(),
            'dispatch_v2_enabled' => self::dispatchV2Enabled(),
            'modules' => self::modulesConfig(),
            'ride_base_price' => (float) config('mami.ride_base_price'),
            'ride_price_per_km' => (float) config('mami.ride_price_per_km'),
            'eta_average_speed_kmh' => (float) config('mami.eta_average_speed_kmh'),
        ];
    }
}
