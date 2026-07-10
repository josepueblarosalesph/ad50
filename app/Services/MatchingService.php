<?php

namespace App\Services;

use App\Models\Busqueda;
use App\Models\Postulante;
use Illuminate\Support\Str;

class MatchingService
{
    public function sincronizar(Busqueda $busqueda): void
    {
        $postulanteIds = [];

        Postulante::query()
            ->where('visible', true)
            ->with('user')
            ->each(function (Postulante $postulante) use ($busqueda, &$postulanteIds): void {
                $this->guardarCoincidencia($busqueda, $postulante);

                $postulanteIds[] = $postulante->id;
            });

        $busqueda->candidatos()->whereNotIn('postulante_id', $postulanteIds)->delete();
    }

    public function sincronizarPostulante(Postulante $postulante): void
    {
        Busqueda::query()
            ->where('estado', 'activa')
            ->each(function (Busqueda $busqueda) use ($postulante): void {
                if (! $postulante->visible) {
                    $busqueda->candidatos()->whereBelongsTo($postulante)->delete();

                    return;
                }

                $this->guardarCoincidencia($busqueda, $postulante);
            });
    }

    /**
     * @param  array<string, mixed>  $criterios
     * @return array<string, array{criterio: string, valor: string, cumple: bool}>
     */
    public function evaluar(Postulante $postulante, array $criterios): array
    {
        $experiencias = $postulante->experiencias ?? [];
        $cargos = collect($experiencias)
            ->flatMap(fn (array $experiencia): array => [$experiencia['cargo'] ?? '', $experiencia['area'] ?? ''])
            ->push($postulante->cargo_actual)
            ->filter();

        $evaluadores = [
            'cargo' => ['Cargo', fn (array $valores): bool => collect($valores)->contains(fn (string $valor): bool => $cargos->contains(fn (?string $cargo): bool => $this->coincideCargo($cargo, $valor)))],
            'carrera' => ['Carrera o título', fn (array $valores): bool => collect($valores)->contains(fn (string $valor): bool => $this->iguales($postulante->carrera, $valor))],
            'especialidad' => ['Especialidad / área', fn (array $valores): bool => collect($valores)->contains(fn (string $valor): bool => $this->iguales($postulante->especialidad, $valor))],
            'industria' => ['Industria', fn (array $valores): bool => collect($valores)->contains(fn (string $valor): bool => collect($postulante->industrias_interes ?? [])->contains(fn (?string $industria): bool => $this->iguales($industria, $valor)))],
            'ciudad' => ['Región', fn (array $valores): bool => collect($valores)->contains(fn (string $valor): bool => $this->iguales($postulante->ciudad, $valor))],
            'institucion' => ['Institución de estudio', function (string $valor) use ($postulante): bool {
                $instituciones = collect($postulante->educaciones ?? [])
                    ->pluck('institucion')
                    ->push($postulante->universidad)
                    ->filter();

                return $instituciones->contains(fn (string $institucion): bool => Str::contains(Str::lower($institucion), Str::lower($valor)));
            }],
            'empresa' => ['Empresa', function (string $valor) use ($postulante): bool {
                $empresas = collect($postulante->experiencias ?? [])
                    ->pluck('empresa')
                    ->push($postulante->empresa_actual)
                    ->filter();

                return $empresas->contains(fn (string $empresa): bool => Str::contains(Str::lower($empresa), Str::lower($valor)));
            }],
            'min_anios' => ['Experiencia mínima', fn (string $valor): bool => $postulante->anios_experiencia >= (int) $valor],
            'edad' => ['Edad', function (array $valor) use ($postulante): bool {
                if ($postulante->edad === null) {
                    return false;
                }

                return $postulante->edad >= (int) $valor['min']
                    && ($valor['max'] === null || $postulante->edad <= (int) $valor['max']);
            }],
            'palabra_clave' => ['Palabra clave', function (array $valores) use ($postulante, $cargos): bool {
                $responsabilidades = collect($postulante->experiencias ?? [])->pluck('responsabilidades');
                $texto = $cargos
                    ->concat($responsabilidades)
                    ->push($postulante->resumen_profesional)
                    ->filter()
                    ->implode(' ');

                return collect($valores)->contains(fn (string $valor): bool => Str::contains(Str::lower($texto), Str::lower($valor)));
            }],
        ];

        $detalle = [];

        foreach ($evaluadores as $clave => [$etiqueta, $evaluar]) {
            $valor = $criterios[$clave] ?? null;

            if ($valor === null || $valor === '' || $valor === [] || ($clave === 'min_anios' && (int) $valor === 0)) {
                continue;
            }

            if ($clave === 'edad') {
                $detalle[$clave] = [
                    'criterio' => $etiqueta,
                    'valor' => $this->rangoEdadLegible($valor),
                    'cumple' => $evaluar($valor),
                ];

                continue;
            }

            $esSeleccionMultiple = in_array($clave, ['cargo', 'carrera', 'especialidad', 'industria', 'ciudad', 'palabra_clave'], true);
            $valorEvaluado = $esSeleccionMultiple ? array_values(array_filter((array) $valor, filled(...))) : (string) $valor;
            $valorMostrado = $esSeleccionMultiple ? implode(', ', $valorEvaluado) : (string) $valor;

            if ($valorEvaluado === []) {
                continue;
            }

            $detalle[$clave] = [
                'criterio' => $etiqueta,
                'valor' => $clave === 'min_anios' ? $valor.' años' : $valorMostrado,
                'cumple' => $evaluar($valorEvaluado),
            ];
        }

        return $detalle;
    }

    /** @param  array{min: int, max: int|null}  $rango */
    private function rangoEdadLegible(array $rango): string
    {
        return $rango['max'] === null
            ? $rango['min'].' años o más'
            : $rango['min'].' a '.$rango['max'].' años';
    }

    private function iguales(?string $actual, string $esperado): bool
    {
        return Str::lower(trim((string) $actual)) === Str::lower(trim($esperado));
    }

    private function coincideCargo(?string $actual, string $esperado): bool
    {
        $cargo = Str::lower(trim((string) $actual));
        $criterio = Str::lower(trim($esperado));

        return $cargo === $criterio || Str::contains($cargo, $criterio);
    }

    private function guardarCoincidencia(Busqueda $busqueda, Postulante $postulante): void
    {
        $detalle = $this->evaluar($postulante, $busqueda->criterios ?? []);
        $incumpleCriterio = collect($detalle)->contains(fn (array $criterio): bool => ! $criterio['cumple']);

        if ($incumpleCriterio) {
            $busqueda->candidatos()->whereBelongsTo($postulante)->delete();

            return;
        }

        $cumplidos = collect($detalle)->where('cumple', true)->count();
        $total = count($detalle);

        $busqueda->candidatos()->updateOrCreate(
            ['postulante_id' => $postulante->id],
            [
                'match_score' => $total === 0 ? 100 : (int) round(($cumplidos / $total) * 100),
                'criterios_cumplidos' => $cumplidos,
                'criterios_totales' => $total,
                'criterios_detalle' => array_values($detalle),
                'estado_match' => $cumplidos === $total ? 'cumple' : 'parcial',
            ],
        );
    }
}
