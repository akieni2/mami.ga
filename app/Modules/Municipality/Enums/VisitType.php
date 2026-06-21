<?php

namespace App\Modules\Municipality\Enums;

enum VisitType: string
{
    case Inspection = 'inspection';
    case Verification = 'verification';
    case Collection = 'collection';
    case Awareness = 'awareness';
    case Scan = 'scan';
    case Consultation = 'consultation';
    case Payment = 'payment';
    case SessionOpen = 'session_open';
    case SessionClose = 'session_close';
    case PresenceControl = 'presence_control';
    case LicenseControl = 'license_control';
    case PatentControl = 'patent_control';
    case MunicipalControl = 'municipal_control';

    public function label(): string
    {
        return match ($this) {
            self::Inspection => 'Inspection',
            self::Verification => 'Vérification',
            self::Collection => 'Recouvrement',
            self::Awareness => 'Sensibilisation',
            self::Scan => 'Scan QR',
            self::Consultation => 'Consultation fiscale',
            self::Payment => 'Encaissement',
            self::SessionOpen => 'Ouverture caisse',
            self::SessionClose => 'Fermeture caisse',
            self::PresenceControl => 'Contrôle de présence',
            self::LicenseControl => 'Contrôle licence',
            self::PatentControl => 'Contrôle patente',
            self::MunicipalControl => 'Contrôle municipal',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
