<?php

namespace App\Console\Commands;

use App\Support\SecuenciasPostgres;
use Illuminate\Console\Command;

/**
 * Realinea las secuencias de PostgreSQL con el mayor id de cada tabla.
 *
 * Se usa después de cargar datos con ids explícitos (seeders, importaciones o un
 * restore parcial), que es cuando la secuencia queda atrás y los INSERT sin id chocan
 * con filas existentes.
 */
class SincronizarSecuencias extends Command
{
    protected $signature = 'db:sincronizar-secuencias';

    protected $description = 'Realinea las secuencias de PostgreSQL con el mayor id de cada tabla.';

    public function handle(): int
    {
        SecuenciasPostgres::sincronizarTodas();

        $this->info('Secuencias sincronizadas.');

        return self::SUCCESS;
    }
}
