<?php

namespace App\Concerns;

use App\Support\CatalogosProfesionales;

/**
 * Rango de años de experiencia de una búsqueda (desde–hasta), compartido por el
 * formulario de criterios y el panel de filtros. La escala parte en 0: si el rango
 * cubre todo el recorrido, no se filtra por este criterio.
 */
trait FiltraPorExperiencia
{
    public int $expMin = 0;

    public int $expMax = 0;

    /** @param  array<string, mixed>  $criterios */
    protected function hidratarExperiencia(array $criterios): void
    {
        $limites = CatalogosProfesionales::rangoExperiencia();
        $experiencia = $criterios['experiencia'] ?? [];

        // Compatibilidad con búsquedas antiguas que guardaban solo un mínimo (min_anios).
        if ($experiencia === [] && filled($criterios['min_anios'] ?? null)) {
            $experiencia = ['min' => (int) $criterios['min_anios'], 'max' => null];
        }

        $this->expMin = (int) ($experiencia['min'] ?? $limites['min']);
        $this->expMax = (int) ($experiencia['max'] ?? $limites['max']);
    }

    /** @return array<string, list<string>> */
    protected function reglasExperiencia(): array
    {
        $limites = CatalogosProfesionales::rangoExperiencia();

        return [
            'expMin' => ['required', 'integer', 'min:'.$limites['min'], 'max:'.$limites['max']],
            'expMax' => ['required', 'integer', 'min:'.$limites['min'], 'max:'.$limites['max'], 'gte:expMin'],
        ];
    }

    /**
     * Sin restricción a ninguno de los dos lados, el criterio no se guarda.
     *
     * @return array{min: int, max: int|null}|null
     */
    protected function criterioExperiencia(int $desde, int $hasta): ?array
    {
        $limites = CatalogosProfesionales::rangoExperiencia();

        if ($desde <= $limites['min'] && $hasta >= $limites['max']) {
            return null;
        }

        return [
            'min' => $desde,
            'max' => $hasta >= $limites['max'] ? null : $hasta,
        ];
    }
}
