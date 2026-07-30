<?php

namespace App\Concerns;

use App\Support\CatalogosProfesionales;

/**
 * Rango de expectativa de renta (desde–hasta), en la misma línea que edad y experiencia.
 *
 * Se trabaja en MILLONES de pesos para que el deslizador avance de a un intervalo
 * entero; el criterio guardado sí lleva los montos en pesos.
 */
trait FiltraPorRenta
{
    public int $rentaMin = 0;

    public int $rentaMax = 0;

    /** @param  array<string, mixed>  $criterios */
    protected function hidratarRenta(array $criterios): void
    {
        $limites = CatalogosProfesionales::rangoSueldo();
        $renta = $criterios['renta'] ?? [];

        // Compatibilidad con el criterio antiguo, que solo guardaba un tope.
        if ($renta === [] && filled($criterios['renta_max'] ?? null)) {
            $renta = ['min' => 0, 'max' => (int) $criterios['renta_max']];
        }

        $this->rentaMin = self::aIntervalos($renta['min'] ?? null) ?? $limites['min'];
        // Un tope nulo significa "o más": el deslizador vuelve al extremo derecho.
        $this->rentaMax = self::aIntervalos($renta['max'] ?? null) ?? $limites['max'];
    }

    /** @return array<string, list<string>> */
    protected function reglasRenta(): array
    {
        $limites = CatalogosProfesionales::rangoSueldo();

        return [
            'rentaMin' => ['required', 'integer', 'min:'.$limites['min'], 'max:'.$limites['max']],
            'rentaMax' => ['required', 'integer', 'min:'.$limites['min'], 'max:'.$limites['max'], 'gte:rentaMin'],
        ];
    }

    /**
     * Sin restricción a ninguno de los dos lados, el criterio no se guarda.
     *
     * @return array{min: int, max: int|null}|null
     */
    protected function criterioRenta(int $desde, int $hasta): ?array
    {
        $limites = CatalogosProfesionales::rangoSueldo();

        if ($desde <= $limites['min'] && $hasta >= $limites['max']) {
            return null;
        }

        return [
            'min' => $desde * CatalogosProfesionales::SUELDO_POR_INTERVALO,
            // El tope máximo del deslizador se interpreta como "o más".
            'max' => $hasta >= $limites['max'] ? null : $hasta * CatalogosProfesionales::SUELDO_POR_INTERVALO,
        ];
    }

    /** Convierte pesos guardados al intervalo del deslizador. */
    private static function aIntervalos(mixed $pesos): ?int
    {
        return $pesos === null ? null : (int) round(((int) $pesos) / CatalogosProfesionales::SUELDO_POR_INTERVALO);
    }
}
