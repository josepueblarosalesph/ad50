<?php

use App\Models\Postulante;
use App\Services\CompletitudPerfil;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * La completitud dejaba de reflejar la ficha: cada paso del asistente subía el
     * porcentaje a un piso fijo y terminarlo lo dejaba en 100, aunque se hubiera saltado
     * todo lo opcional. Ahora sale de los datos guardados (ver CompletitudPerfil), así
     * que las fichas ya existentes arrastran el valor viejo y se recalculan una vez.
     */
    public function up(): void
    {
        Postulante::query()->chunkById(200, function ($postulantes): void {
            foreach ($postulantes as $postulante) {
                // Sin eventos: son escrituras masivas y no vale la pena invalidar la
                // caché de facetas una vez por ficha (no cambia ningún dato del calce).
                $postulante->updateQuietly([
                    'completitud' => CompletitudPerfil::porcentaje($postulante),
                ]);
            }
        });
    }

    public function down(): void
    {
        // No hay valor anterior al que volver: el porcentaje viejo era el inflado.
    }
};
