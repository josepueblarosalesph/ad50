<?php

namespace App\Concerns;

use App\Support\CatalogosProfesionales;

/**
 * Rango de edad de una búsqueda, compartido por el formulario de criterios y el panel de filtros.
 */
trait FiltraPorEdad
{
    public int $edadMin = 0;

    public int $edadMax = 0;

    /** @param  array<string, mixed>  $criterios */
    protected function hidratarEdad(array $criterios): void
    {
        $limites = CatalogosProfesionales::rangoEdad();
        $edad = $criterios['edad'] ?? [];

        $this->edadMin = (int) ($edad['min'] ?? $limites['min']);
        // Un tope nulo significa "o más": en el slider vuelve al extremo derecho.
        $this->edadMax = (int) ($edad['max'] ?? $limites['max']);
    }

    /** @return array<string, list<string>> */
    protected function reglasEdad(): array
    {
        $limites = CatalogosProfesionales::rangoEdad();

        return [
            'edadMin' => ['required', 'integer', 'min:'.$limites['min'], 'max:'.$limites['max']],
            'edadMax' => ['required', 'integer', 'min:'.$limites['min'], 'max:'.$limites['max'], 'gte:edadMin'],
        ];
    }

    /**
     * Sin restricción a ninguno de los dos lados, el criterio no se guarda.
     *
     * @return array{min: int, max: int|null}|null
     */
    protected function criterioEdad(int $desde, int $hasta): ?array
    {
        $limites = CatalogosProfesionales::rangoEdad();

        if ($desde <= $limites['min'] && $hasta >= $limites['max']) {
            return null;
        }

        return [
            'min' => $desde,
            'max' => $hasta >= $limites['max'] ? null : $hasta,
        ];
    }
}
