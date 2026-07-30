<?php

namespace App\Services;

use App\Support\CatalogosAdministrables;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Responde si un término de catálogo está siendo usado y dónde.
 *
 * Es lo que impide editar o eliminar un valor que ya quedó guardado en fichas,
 * publicaciones o criterios de búsqueda: renombrarlo dejaría esos registros
 * apuntando a un valor que ya no existe y el motor de calce dejaría de encontrarlos.
 */
class UsoDeTerminos
{
    /**
     * Detalle de uso de un término, por lugar.
     *
     * @return array{total: int, detalle: array<string, int>}
     */
    public function detalle(string $catalogo, string $valor): array
    {
        $detalle = [];

        foreach (CatalogosAdministrables::definicion($catalogo)['usos'] as $uso) {
            $cantidad = $this->contar($uso, $valor);

            if ($cantidad > 0) {
                // Varios usos comparten etiqueta (p. ej. dos criterios distintos): se suman.
                $detalle[$uso['etiqueta']] = ($detalle[$uso['etiqueta']] ?? 0) + $cantidad;
            }
        }

        return ['total' => array_sum($detalle), 'detalle' => $detalle];
    }

    public function estaEnUso(string $catalogo, string $valor): bool
    {
        foreach (CatalogosAdministrables::definicion($catalogo)['usos'] as $uso) {
            if ($this->contar($uso, $valor, soloExistencia: true) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cuántos registros usan el valor en un lugar concreto.
     *
     * @param  array<string, string>  $uso
     */
    private function contar(array $uso, string $valor, bool $soloExistencia = false): int
    {
        $query = DB::table($uso['tabla']);

        // Las búsquedas y publicaciones eliminadas no cuentan como uso vigente.
        if (in_array($uso['tabla'], ['busquedas', 'publicaciones'], true)) {
            $query->whereNull('deleted_at');
        }

        $this->aplicarModo($query, $uso, $valor);

        if ($soloExistencia) {
            return $query->limit(1)->exists() ? 1 : 0;
        }

        return $query->count();
    }

    /**
     * @param  array<string, string>  $uso
     */
    private function aplicarModo(Builder $query, array $uso, string $valor): void
    {
        match ($uso['modo']) {
            // Columna de texto: el valor está tal cual.
            'exacta' => $query->where($uso['columna'], $valor),

            // Lista JSON de textos: ["Minería","Salud"].
            'lista_json' => $query->whereJsonContains($uso['columna'], $valor),

            // Lista JSON de objetos: el valor vive en una clave de cada elemento.
            'objetos_json' => $query->whereJsonContains($uso['columna'], [[$uso['clave'] => $valor]]),

            // Criterio guardado dentro del JSON `criterios` de una búsqueda. Puede estar
            // como lista (selección múltiple) o como texto suelto (dato antiguo).
            'criterio' => $query->where(function (Builder $sub) use ($uso, $valor): void {
                $sub->whereJsonContains('criterios->'.$uso['columna'], $valor)
                    ->orWhere('criterios->'.$uso['columna'], $valor);
            }),

            default => null,
        };
    }
}
