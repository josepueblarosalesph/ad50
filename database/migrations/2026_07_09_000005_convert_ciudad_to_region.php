<?php

use App\Support\CatalogosProfesionales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $regionPorCiudad = CatalogosProfesionales::regionPorCiudad();

        DB::table('postulantes')->whereNotNull('ciudad')->orderBy('id')->each(function (object $postulante) use ($regionPorCiudad): void {
            DB::table('postulantes')
                ->where('id', $postulante->id)
                ->update(['ciudad' => $regionPorCiudad[$postulante->ciudad] ?? null]);
        });

        DB::table('busquedas')->whereNotNull('criterios')->orderBy('id')->each(function (object $busqueda) use ($regionPorCiudad): void {
            $criterios = json_decode((string) $busqueda->criterios, true);

            if (! is_array($criterios) || blank($criterios['ciudad'] ?? null)) {
                return;
            }

            $criterios['ciudad'] = collect((array) $criterios['ciudad'])
                ->map(fn (string $ciudad): ?string => $regionPorCiudad[$ciudad] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all();

            DB::table('busquedas')->where('id', $busqueda->id)->update(['criterios' => json_encode($criterios)]);
        });
    }

    public function down(): void
    {
        // Una región agrupa varias ciudades: la ciudad original no se puede reconstruir.
    }
};
