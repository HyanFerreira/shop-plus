<?php

namespace App\Console\Commands;

use App\Actions\Academic\GenerateAcademicCycle as GenerateCycle;
use Illuminate\Console\Command;

class GenerateAcademicCycle extends Command
{
    protected $signature = 'academic:cycle {--once : Executar explicitamente um ciclo manual mesmo com automação pausada}';

    protected $description = 'Gera dados fictícios adicionais, no máximo um ciclo por janela de cinco minutos';

    public function handle(GenerateCycle $generator): int
    {
        if (! config('academic-automation.enabled') && ! $this->option('once')) {
            $this->info('Automação acadêmica pausada.');

            return self::SUCCESS;
        }

        $cycle = $generator->execute();
        $this->info('Ciclo '.$cycle->cycle_key.' concluído; compra: '.$cycle->purchase_status.'.');

        return self::SUCCESS;
    }
}
