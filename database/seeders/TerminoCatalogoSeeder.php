<?php

namespace Database\Seeders;

use App\Models\TerminoCatalogo;
use App\Support\CatalogosAdministrables;
use App\Support\CatalogosProfesionales;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Carga en la base los términos que hoy viven como arreglos en el código.
 *
 * Es idempotente: solo agrega los que faltan, así que no pisa ni duplica lo que un
 * administrador haya editado. Se ejecuta también desde la migración que crea la tabla.
 */
class TerminoCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now();

        foreach (CatalogosAdministrables::todos() as $catalogo => $definicion) {
            $existentes = TerminoCatalogo::query()
                ->where('catalogo', $catalogo)
                ->pluck('valor')
                ->map(fn (string $valor): string => self::normalizar($valor))
                ->all();

            $filas = collect(CatalogosProfesionales::porDefecto($definicion['origen']))
                ->filter(fn (string $valor): bool => filled(trim($valor)))
                ->map(fn (string $valor): string => trim($valor))
                // Se descartan por su forma normalizada, no por el literal: el índice
                // único de la tabla lo aplica el motor con SU cotejamiento, y en
                // MariaDB/MySQL el de por defecto ignora mayúsculas y acentos. Así,
                // "Abogado procurador" y "Abogado Procurador" pasaban el unique() de PHP
                // y chocaban en la base, reventando el despliegue solo en esos motores.
                ->uniqueStrict(fn (string $valor): string => self::normalizar($valor))
                ->values()
                // El índice se guarda como orden para no perder la secuencia original.
                ->map(fn (string $valor, int $indice): array => [
                    'catalogo' => $catalogo,
                    'valor' => $valor,
                    'orden' => $indice,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ])
                ->reject(fn (array $fila): bool => in_array(self::normalizar($fila['valor']), $existentes, true));

            foreach ($filas->chunk(1000) as $tanda) {
                TerminoCatalogo::query()->insert($tanda->values()->all());
            }
        }

        CatalogosProfesionales::olvidar();
    }

    /**
     * Forma con la que dos términos se consideran el mismo: la que aplicaría el
     * cotejamiento más permisivo de los motores que usamos (minúsculas y sin acentos).
     */
    private static function normalizar(string $valor): string
    {
        return Str::ascii(mb_strtolower(trim($valor)));
    }
}
