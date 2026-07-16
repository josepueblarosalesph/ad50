<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_candidato', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('postulante_id')->constrained('postulantes')->cascadeOnDelete();
            $table->text('contenido');
            $table->timestamps();
            $table->unique(['empresa_id', 'postulante_id']);
        });

        // Migrar las notas que estaban ligadas a la búsqueda (pivote) hacia la empresa+postulante.
        if (Schema::hasColumn('busqueda_candidato', 'nota')) {
            $existentes = DB::table('busqueda_candidato')
                ->join('busquedas', 'busquedas.id', '=', 'busqueda_candidato.busqueda_id')
                ->whereNotNull('busqueda_candidato.nota')
                ->where('busqueda_candidato.nota', '<>', '')
                ->orderByDesc('busqueda_candidato.updated_at')
                ->get(['busquedas.empresa_id', 'busqueda_candidato.postulante_id', 'busqueda_candidato.nota', 'busqueda_candidato.updated_at']);

            $vistos = [];

            foreach ($existentes as $fila) {
                $clave = $fila->empresa_id.'-'.$fila->postulante_id;

                if (isset($vistos[$clave])) {
                    continue;
                }

                $vistos[$clave] = true;

                DB::table('notas_candidato')->insert([
                    'empresa_id' => $fila->empresa_id,
                    'postulante_id' => $fila->postulante_id,
                    'contenido' => $fila->nota,
                    'created_at' => $fila->updated_at ?? now(),
                    'updated_at' => $fila->updated_at ?? now(),
                ]);
            }

            Schema::table('busqueda_candidato', function (Blueprint $table): void {
                $table->dropColumn('nota');
            });
        }
    }

    public function down(): void
    {
        Schema::table('busqueda_candidato', function (Blueprint $table): void {
            $table->text('nota')->nullable()->after('favorito');
        });

        Schema::dropIfExists('notas_candidato');
    }
};
