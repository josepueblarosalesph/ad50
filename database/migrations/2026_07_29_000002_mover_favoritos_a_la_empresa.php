<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El favorito deja de colgar de la búsqueda y pasa a ser de la cuenta.
 *
 * Antes vivía como un booleano en `busqueda_candidato`, así que un mismo candidato
 * marcado en dos búsquedas eran dos marcas distintas y borrar una búsqueda se llevaba
 * sus favoritos. Ahora es una fila por (empresa, candidato): se marca una vez y
 * sobrevive a cualquier cambio en las búsquedas.
 *
 * `busqueda_id` queda solo como trazabilidad de dónde se marcó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favoritos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('postulante_id')->constrained('postulantes')->cascadeOnDelete();
            $table->foreignId('busqueda_id')->nullable()->constrained('busquedas')->nullOnDelete();
            $table->timestamps();

            $table->unique(['empresa_id', 'postulante_id']);
            $table->index('postulante_id');
        });

        $this->traspasarFavoritosExistentes();

        Schema::table('busqueda_candidato', function (Blueprint $table): void {
            $table->dropIndex(['busqueda_id', 'favorito']);
            $table->dropColumn('favorito');
        });
    }

    public function down(): void
    {
        Schema::table('busqueda_candidato', function (Blueprint $table): void {
            $table->boolean('favorito')->default(false);
            $table->index(['busqueda_id', 'favorito']);
        });

        // Se devuelve la marca a todas las coincidencias del candidato en esa empresa:
        // el dato original (en cuál búsqueda estaba marcado) ya no existe.
        foreach (DB::table('favoritos')->get() as $favorito) {
            DB::table('busqueda_candidato')
                ->whereIn('busqueda_id', DB::table('busquedas')->where('empresa_id', $favorito->empresa_id)->select('id'))
                ->where('postulante_id', $favorito->postulante_id)
                ->update(['favorito' => true]);
        }

        Schema::dropIfExists('favoritos');
    }

    /**
     * Un candidato marcado en varias búsquedas de la misma empresa produce un único
     * favorito; se conserva como origen la búsqueda donde se marcó primero.
     */
    private function traspasarFavoritosExistentes(): void
    {
        if (! Schema::hasColumn('busqueda_candidato', 'favorito')) {
            return;
        }

        $ahora = now();

        DB::table('busqueda_candidato')
            ->join('busquedas', 'busquedas.id', '=', 'busqueda_candidato.busqueda_id')
            ->whereNull('busquedas.deleted_at')
            ->where('busqueda_candidato.favorito', true)
            ->orderBy('busqueda_candidato.id')
            ->get([
                'busquedas.empresa_id',
                'busqueda_candidato.postulante_id',
                'busqueda_candidato.busqueda_id',
            ])
            ->unique(fn (object $fila): string => $fila->empresa_id.'-'.$fila->postulante_id)
            ->chunk(500)
            ->each(function ($tanda) use ($ahora): void {
                DB::table('favoritos')->insert(
                    $tanda->map(fn (object $fila): array => [
                        'empresa_id' => $fila->empresa_id,
                        'postulante_id' => $fila->postulante_id,
                        'busqueda_id' => $fila->busqueda_id,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ])->all()
                );
            });
    }
};
