<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reescribe las columnas JSON con los acentos literales en vez de escapados.
 *
 * `json_encode` escapa por defecto: "Inglés" se guarda como "Inglés". Postgres no
 * se inmuta —su operador de contención interpreta el JSON— pero **MariaDB compara el
 * texto tal cual**, así que `JSON_CONTAINS(idiomas, '"Inglés"')` no encuentra la fila.
 * En producción eso significaba que filtrar ofertas por cualquier idioma con tilde no
 * devolvía nada, y que [\App\Services\UsoDeTerminos] subcontaba dónde se usa un término
 * del catálogo (con lo que un admin podía borrar uno que sí estaba en uso).
 *
 * Los modelos ya guardan con el cast `json:unicode`; esto arregla lo que quedó escrito
 * antes. La forma literal es JSON válido y funciona en los dos motores, que es lo que
 * mantiene la compatibilidad entre el MariaDB de producción y el PostgreSQL de pruebas.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private const COLUMNAS = [
        'busquedas' => ['criterios'],
        'busqueda_candidato' => ['criterios_detalle'],
        'planes' => ['features'],
        'postulaciones' => ['respuestas'],
        'postulantes' => [
            'regiones_interes', 'industrias_interes', 'habilidades',
            'modalidad_trabajo', 'educaciones', 'idiomas', 'experiencias',
        ],
        'publicaciones' => ['competencias', 'idiomas', 'preguntas'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNAS as $tabla => $columnas) {
            if (! Schema::hasTable($tabla)) {
                continue;
            }

            foreach ($columnas as $columna) {
                if (Schema::hasColumn($tabla, $columna)) {
                    $this->reescribir($tabla, $columna);
                }
            }
        }
    }

    /**
     * No hay vuelta atrás que valga la pena: la forma literal es JSON igual de válido y
     * volver a escaparla solo reintroduciría el fallo en MariaDB.
     */
    public function down(): void {}

    private function reescribir(string $tabla, string $columna): void
    {
        DB::table($tabla)
            ->whereNotNull($columna)
            ->orderBy('id')
            ->each(function (object $fila) use ($tabla, $columna): void {
                $crudo = (string) $fila->{$columna};

                // Sin escapes no hay nada que hacer; se evita reescribir toda la tabla.
                if (! str_contains($crudo, '\u')) {
                    return;
                }

                $valor = json_decode($crudo, true);

                // Si no es JSON válido se deja como está: no es asunto de esta migración.
                if ($valor === null) {
                    return;
                }

                DB::table($tabla)->where('id', $fila->id)->update([
                    $columna => json_encode($valor, JSON_UNESCAPED_UNICODE),
                ]);
            });
    }
};
