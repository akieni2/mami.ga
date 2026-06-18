<?php

namespace App\Console\Commands;

use App\Modules\Municipality\Services\FiscalObligationGeneratorService;
use Illuminate\Console\Command;

class MunicipalityFiscalGenerateCommand extends Command
{
    protected $signature = 'municipality:fiscal-generate {--date= : Date de référence (Y-m-d)}';

    protected $description = 'Génère les obligations fiscales ouvertes selon les taux actifs (idempotent)';

    public function handle(FiscalObligationGeneratorService $generator): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))
            : now();

        $result = $generator->generate(null, $date);

        $this->info(sprintf(
            'Obligations générées : %d créée(s), %d ignorée(s) (déjà existantes ou sans taux).',
            $result['created'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
