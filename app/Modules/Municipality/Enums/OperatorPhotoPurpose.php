<?php

namespace App\Modules\Municipality\Enums;

enum OperatorPhotoPurpose: string
{
    case Facade = 'facade';
    case TradeRegistry = 'trade_registry';
    case BusinessLicense = 'business_license';
    case MunicipalAuthorization = 'municipal_authorization';

    public function label(): string
    {
        return match ($this) {
            self::Facade => 'Façade du commerce',
            self::TradeRegistry => 'Registre de commerce',
            self::BusinessLicense => 'Patente',
            self::MunicipalAuthorization => 'Autorisation municipale',
        };
    }

    public function isRequiredOnEnrollment(): bool
    {
        return $this === self::Facade;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
