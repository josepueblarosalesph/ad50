<?php

namespace App\Console\Commands;

use App\Models\Publicacion;
use Illuminate\Console\Command;

class PurgarPublicacionesEliminadas extends Command
{
    protected $signature = 'publicaciones:purgar-eliminadas';

    protected $description = 'Elimina en forma definitiva las publicaciones en papelera con más de '.Publicacion::DIAS_RETENCION_PAPELERA.' días.';

    public function handle(): int
    {
        $limite = now()->subDays(Publicacion::DIAS_RETENCION_PAPELERA);

        // forceDelete cascada a postulaciones por la FK onDelete cascade.
        $purgadas = Publicacion::onlyTrashed()
            ->where('deleted_at', '<=', $limite)
            ->get()
            ->each->forceDelete()
            ->count();

        $this->info("Publicaciones purgadas definitivamente: {$purgadas}.");

        return self::SUCCESS;
    }
}
