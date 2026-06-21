<?php

namespace App\Modules\Core\Enums;

enum MamiRole: string
{
    case Citizen = 'citizen';
    case TaxiCustomer = 'taxi_customer';
    case TaxiDriver = 'taxi_driver';
    case CarpoolDriver = 'carpool_driver';
    case CarpoolPassenger = 'carpool_passenger';
    case TransportCustomer = 'transport_customer';
    case TransportDriver = 'transport_driver';
    case Merchant = 'merchant';
    case MunicipalAgent = 'municipal_agent';
    case MunicipalSupervisor = 'municipal_supervisor';
    case Daf = 'daf';
    case DafAdjoint = 'daf_adjoint';
    case CaissierCentral = 'caissier_central';
    case ControleurFinancier = 'controleur_financier';
    case ReceveurMunicipal = 'receveur_municipal';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::Citizen => 'Citoyen',
            self::TaxiCustomer => 'Client Taxi',
            self::TaxiDriver => 'Chauffeur Taxi',
            self::CarpoolDriver => 'Conducteur Covoiturage',
            self::CarpoolPassenger => 'Passager Covoiturage',
            self::TransportCustomer => 'Client Transport',
            self::TransportDriver => 'Transporteur',
            self::Merchant => 'Commerçant',
            self::MunicipalAgent => 'Agent Municipal',
            self::MunicipalSupervisor => 'Superviseur Municipal',
            self::Daf => 'Directeur des Affaires Financières',
            self::DafAdjoint => 'DAF adjoint',
            self::CaissierCentral => 'Caissier central',
            self::ControleurFinancier => 'Contrôleur financier',
            self::ReceveurMunicipal => 'Receveur municipal',
            self::Admin => 'Administrateur',
            self::SuperAdmin => 'Super Administrateur',
        };
    }

    public function module(): string
    {
        return match ($this) {
            self::Citizen, self::Admin, self::SuperAdmin => 'core',
            self::TaxiCustomer, self::TaxiDriver => 'taxi',
            self::CarpoolDriver, self::CarpoolPassenger => 'carpool',
            self::TransportCustomer, self::TransportDriver => 'transport',
            self::Merchant => 'commerce',
            self::MunicipalAgent, self::MunicipalSupervisor,
            self::Daf, self::DafAdjoint, self::CaissierCentral,
            self::ControleurFinancier, self::ReceveurMunicipal => 'municipality',
        };
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
