<?php

namespace App\Console\Commands;

use App\Models\Busqueda;
use Illuminate\Console\Command;

class PurgarBusquedasEliminadas extends Command
{
    protected $signature = 'busquedas:purgar-eliminadas';

    protected $description = 'Elimina en forma definitiva los procesos en papelera con más de '.Busqueda::DIAS_RETENCION_PAPELERA.' días.';

    public function handle(): int
    {
        $limite = now()->subDays(Busqueda::DIAS_RETENCION_PAPELERA);

        // forceDelete cascada a busqueda_candidato por la FK onDelete cascade.
        $purgadas = Busqueda::onlyTrashed()
            ->where('deleted_at', '<=', $limite)
            ->get()
            ->each->forceDelete()
            ->count();

        $this->info("Procesos purgados definitivamente: {$purgadas}.");

        return self::SUCCESS;
    }
}
